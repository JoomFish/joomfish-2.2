<?php
/**
 * Joom!Fish - Multi Lingual extention and translation manager for Joomla!
 * Copyright (C) 2003 - 2012, Think Network GmbH, Munich
 *
 * All rights reserved.  The Joom!Fish project is a set of extentions for
 * the content management system Joomla!. It enables Joomla!
 * to manage multi lingual sites especially in all dynamic information
 * which are stored in the database.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,USA.
 *
 * The "GNU General Public License" (GPL) is available at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -----------------------------------------------------------------------------
 * $Id: jfdatabase_inherit.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage jfdatabase
 * @version 2.0
 *
*/

// Don't allow direct linking
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

//require_once( JPATH_SITE.'/components/com_joomfish/helpers/joomfish.class.php' );
//require_once( JPATH_SITE."/administrator/components/com_joomfish/JoomfishManager.class.php");


/**
 * Multi lingual Database connector class
 *
 * This extention of the standard database class converts the output of the query automatically
 * with the actual selected language in the web site.
 *
 * @package joomfish
 * @subpackage database
 * @copyright 2003 - 2012, Think Network GmbH, Munich
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @version 1.0, 2009-01-07 $Revision: 1592 $
 * @author Geraint Edwards
*/

include_once(dirname(__FILE__)."/intercept.".strtolower(get_class(JFactory::getDBO())).".php");
class JFDatabase extends interceptDB {

	/** @var array list of multi lingual tables */
	var $_mlTableList=null;
	/** @var Internal variable to hold array of unique tablenames and mapping data*/
	var $_refTables=null;

	/** @var Internal variable to hold flag about whether setRefTables is needed - JF queries don't need it */
	var $_skipSetRefTables = false;

	var $orig_limit	= 0;
	var $orig_offset	= 0;

	/** Constructor
	*/
	function JFDatabase( $options) {
		parent::__construct($options );

		$pfunc = $this->_profile();

		$query = "select distinct reference_table from #__jf_content";
		$this->setQuery( $query );
		$this->_skipSetRefTables = true;
		$this->_mlTableList = $this->loadResultArray(0,false);
		$this->_skipSetRefTables = false;
		if( !$this->_mlTableList ){
			if ($this->getErrorNum()>0){
				JError::raiseWarning( 200, JTEXT::_('No valid table list:') .$this->getErrorMsg());
			}
		}

		$pfunc = $this->_profile($pfunc);
	}

	var $profileData = array();

	function _profile($func = "", $forcestart=false){
		if (!$this->_debug) return "";
		// start of function
		if ($func==="" || $forcestart){
			if (!$forcestart){
				$backtrace = debug_backtrace();
				if (count($backtrace)>1){
					if (array_key_exists("class",$backtrace[1])){
						$func = $backtrace[1]["class"]."::".$backtrace[1]["function"];
					}
					else {
						$func = $backtrace[1]["function"];
					}
				}
			}
			if (!array_key_exists($func,$this->profileData)){
				$this->profileData[$func]=array("total"=>0, "count"=>0);
			}
			if (!array_key_exists("start",$this->profileData[$func])) {
				$this->profileData[$func]["start"]=array();
			}
			list ($usec,$sec) = explode(" ", microtime());
			$this->profileData[$func]["start"][] = floatval($usec)+floatval($sec);
			$this->profileData[$func]["count"]++;
			return $func;
		}
		else {
			if (!array_key_exists($func,$this->profileData)){
				exit("JFProfile start not found for function $func");
			}
			list ($usec,$sec) = explode(" ", microtime());
			$laststart = array_pop($this->profileData[$func]["start"]);
			$this->profileData[$func]["total"] += (floatval($usec)+floatval($sec)) - $laststart;
		}
	}

	/**
	 * Public function to test if table has translated content available
	 *
	 * @param string $table : tablename to test
	 */
	function translatedContentAvailable($table){
		return in_array( $table, $this->_mlTableList) || $table=="content";
	}

	/**
	 * Description
	 *
	 * @access public
	 * @return int The number of rows returned from the most recent query.
	 */
	function getNumRows( $cur=null, $translate=true, $language=null )
	{
		$count = parent::getNumRows($cur);
		if (!$translate) return $count;

		// setup Joomfish plugins
		$dispatcher	   = JDispatcher::getInstance();
		JPluginHelper::importPlugin('joomfish');

		// must allow fall back for contnent table localisation to work
		$allowfallback = true;
		$refTablePrimaryKey = "";
		$reference_table = "";
		$ids="";
		$jfm = JoomFishManager::getInstance();
		$this->_setLanguage($language);
		$registry = JFactory::getConfig();
		$defaultLang = $registry->getValue("config.defaultlang");
		if ($defaultLang == $language){
			$rows = array($count);	
			$dispatcher->trigger('onBeforeTranslation', array (&$rows, &$ids, $reference_table, $language, $refTablePrimaryKey, $this->_getRefTables(), $this->_sql, $allowfallback));
			$count = $rows[0];
			return $count;
		}

		$rows = array($count);
		
		$dispatcher->trigger('onBeforeTranslation', array (&$rows, &$ids, $reference_table, $language, $refTablePrimaryKey, $this->_getRefTables(), $this->_sql, $allowfallback));
		
		$dispatcher->trigger('onAfterTranslation', array (&$rows, &$ids, $reference_table, $language, $refTablePrimaryKey, $this->_getRefTables(), $this->_sql, $allowfallback));
		$count = $rows[0];
		return $count;
	}

	/**
	* Overwritten method to loads the first field of the first row returned by the query.
	*
	* @return The value returned in the query or null if the query failed.
	*/
	function loadResult( $translate=true, $language=null ) {
		if (!$translate){
			$this->_skipSetRefTables=true;
			$result = parent::loadResult();
			$this->_skipSetRefTables=false;
			return $result;
		}
		$result=null;
		$ret=null;

		$result = $this->_loadObject( $translate, $language );

		$pfunc = $this->_profile();

		if( $result != null ) {
			$fields = get_object_vars( $result );
			$field = each($fields);
			$ret = $field[1];
		}

		$pfunc = $this->_profile($pfunc);

		return $ret;
	}

	/**
	 * Overwritten Load an array of single field results into an array
	 *
	 * @access	public
	 */
	function loadResultArray($numinarray = 0,  $translate=true, $language=null){
		if (!$translate){
			return parent::loadResultArray($numinarray);
		}
		$results=array();
		$ret=array();
		$results = $this->loadObjectList( '', $translate, $language );

		$pfunc = $this->_profile();

		if( $results != null && count($results)>0) {
			foreach ($results as $row) {
				$fields = get_object_vars( $row );
				$keycount = 0;
				foreach ($fields as $k=>$v) {
					if ($keycount==$numinarray){
						$key = $k;
						break;
					}
				}
				$ret[] = $fields[$key];
			}
		}

		$pfunc = $this->_profile($pfunc);

		return $ret;
	}

	/**
	* Overwritten Fetch a result row as an associative array
	*
	* @access	public
	* @return array
	*/
	function loadAssoc( $translate=true, $language=null) {
		if (!$translate){
			return parent::loadResult();
		}
		$result=null;
		$result = $this->_loadObject( $translate, $language );

		$pfunc = $this->_profile();

		if( $result != null ) {
			$fields = get_object_vars( $result );
			$pfunc = $this->_profile($pfunc);
			return $fields;
		}
		return $result;
	}

	/**
	* Overwritten Load a assoc list of database rows
	*
	* @access	public
	* @param string The field name of a primary key
	* @return array If <var>key</var> is empty as sequential list of returned records.
	*/
	function loadAssocList( $key='', $translate=true, $language=null )
	{
		if (!$translate){
			return parent::loadAssocList($key);
		}
		$result=null;
		$rows = $this->loadObjectList($key, $translate, $language );

		$pfunc = $this->_profile();
		$results = array();
		if( $rows != null ) {
			foreach ($rows as $row) {
				if ($key!=""){
					$results[$row->$key] = get_object_vars( $row );
				}
				else {
					$results[] = get_object_vars( $row );
				}
			}
			$pfunc = $this->_profile($pfunc);
		}
		return $results;
	}

	/**
	* This global function loads the first row of a query into an object
	*/
	function loadObject( $translate=true, $language=null ) {
		$objects = $this->loadObjectList("",$translate,$language);
		if (!is_null($objects) && count($objects)>0){
			return $objects[0];
		}
		return null;
	}

	/**
	 * private function to handle the requirement to call different loadObject version based on class
	 *
	 * @param boolran $translate
	 * @param string $language
	 */
	function _loadObject( $translate=true, $language=null ) {
		return $this->loadObject();
	}

	/**
	* Load a list of database objects
	* @param string The field name of a primary key
	* @return array If <var>key</var> is empty as sequential list of returned TRANSLATED records.
	* If <var>key</var> is not empty then the returned array is indexed by the value
	* the database key.  Returns <var>null</var> if the query fails.
	*/
	function loadObjectList( $key='', $translate=true, $language=null ) {
		$jfManager = JoomFishManager::getInstance();

		if (!$translate) {
			$this->_skipSetRefTables=true;
			$result = parent::loadObjectList( $key );
			$this->_skipSetRefTables=false;
			return $result;
		}

		$result = parent::loadObjectList( $key );

		if( isset($jfManager)) {
			$this->_setLanguage($language);
		}

		// TODO check the impact of this on frontend translation
		// It does stop Joomfish plugins from working on missing translations e.g. regional content so disable for now
		// Don't do it for now since translation caching is so effective
		/*
		$registry = JFactory::getConfig();
		$defaultLang = $registry->getValue("config.defaultlang");
		if ($defaultLang == $language){
		$translate = false;
		}
		*/

		if( isset($jfManager)) {
			$doTranslate=false;
			$tables =$this->_getRefTables();
			if ($tables == null) return $result; // an unstranslatable query to return result as is
			// if we don't have "fieldTablePairs" then we can't translate
			if (!array_key_exists("fieldTablePairs",$tables)){
				return $result;
			}
			foreach ($tables["fieldTablePairs"] as $i=>$table) {
				if ($this->translatedContentAvailable($table)) {
					$doTranslate=true;
					break;
				}
			}
			if ($doTranslate ) {
				$pfunc = $this->_profile();
				if ($jfManager->getCfg("transcaching",1)){
					// cache the results
					// TODO call based on config
					//$cache = JFactory::getCache('jfquery');
					$cache = $jfManager->getCache($language);
					$this->orig_limit	= $this->_limit;
					$this->orig_offset	= $this->_offset;
					$result = $cache->get( array("JoomFish", 'translateListCached'), array($result, $language, $this->_getRefTables() ));
					$this->orig_limit	= 0;
					$this->orig_offset	= 0;
				}
				else {
					$this->orig_limit	= $this->_limit;
					$this->orig_offset	= $this->_offset;
					JoomFish::translateList( $result, $language, $this->_getRefTables() );
					$this->orig_limit	= 0;
					$this->orig_offset	= 0;
				}
				$pfunc = $this->_profile($pfunc);
			}
		}
		return $result;
	}


	/**
	 * Overwritten
	 *
	 * @access	public
	 * @return The first row of the query.
	 */
	function loadRow( $translate=true, $language=null)
	{
		if (!$translate){
			return parent::loadRow();
		}
		$result=null;
		$result = $this->_loadObject( $translate, $language );

		$pfunc = $this->_profile();

		$row = array();
		if( $result != null ) {
			$fields = get_object_vars( $result );
			foreach ($fields as $val) {
				$row[] = $val;
			}
			return $row;
		}
		return $row;
	}

	/**
	* Overwritten Load a list of database rows (numeric column indexing)
	*
	* @access public
	* @param string The field name of a primary key
	* @return array If <var>key</var> is empty as sequential list of returned records.
	* If <var>key</var> is not empty then the returned array is indexed by the value
	* the database key.  Returns <var>null</var> if the query fails.
	*/
	function loadRowList( $key=null , $translate=true, $language=null)
	{
		if (!$translate){
			return parent::loadRowList($key);
		}
		$results=array();
		if (is_null($key)) $key="";
		$rows = $this->loadObjectList($key, $translate, $language );

		$pfunc = $this->_profile();

		$row = array();
		if( $rows != null ) {
			foreach ($rows as $row) {
				$fields = get_object_vars( $row );
				$result = array();
				foreach ($fields as $val) {
					$result[] = $val;
				}
				if ($key!="") {
					$results[$row->$key] = $result;
				}
				else {
					$results[] = $result;
				}
			}
		}
		$pfunc = $this->_profile($pfunc);
		return $results;
	}

	/**
	* Overwritten insert function to enable storage of material created in non-default language.
	* Note that this creates a translation which is identical to the original - when we update
	* the original in the default language we then keep the translation (although it will appread out of date!).
	*
	* @param	string	table name
	* @param	object	instance with information to store
	* @param	string	primary key name of table
	* @param	boolean	debug info printed or not
	* @param	boolean	passthru without storing information in a translation table
	*/
	function insertObject( $table, &$object, $keyName = NULL, $verbose=false , $passthru=false) {
		$jfManager = JoomFishManager::getInstance();
		if( isset($jfManager)) {
			$this->_setLanguage($language);
		}
		$conf	= JFactory::getConfig();
		$default_lang	= $conf->getValue('config.defaultlang');

		// if the currect language is the site default language the translations will not be updated!
		$passthru = $language == $default_lang;

		if( !$passthru && isset($jfManager)) {
			//Must insert parent first to get reference id !
			$parentInsertReturn = parent::insertObject( $table, $object, $keyName, $verbose);

			$pfunc = $this->_profile();

			$actContentObject=null;
			if( isset($table) && $table!="" ) {
				$tableName = preg_replace( '/^#__/', '', $table);
				if ($table != "#__jf_content" ){
					$contentElement = $jfManager->getContentElement( $tableName );
					if( isset( $contentElement ) ) {
						include_once(JPATH_ADMINISTRATOR."/components/com_joomfish/models/ContentObject.php");
						$actContentObject = new ContentObject( $jfManager->getLanguageID($language), $contentElement );
						if( isset( $object->$keyName ) ) {
							$actContentObject->loadFromContentID( $object->$keyName );
							$actContentObject->updateMLContent( $object );
							if( isset( $object->state ) ) {
								$actContentObject->published = ($object->state == 1) ? true : false;
							} else if ( isset( $object->published ) ) {
								$actContentObject->published = ($object->published == 1) ? true : false;
							}
							if ($actContentObject->published){
								if ( $jfManager->getCfg("frontEndPublish")){
									$user = JFactory::getUser();
									$access = new stdClass();
									$access->canPublish =  $user->authorize('com_content', 'publish', 'content', 'all');
									if ($access->canPublish) $actContentObject->setPublished($actContentObject->published);
								}
							}
							$actContentObject->store();

							if ($jfManager->getCfg("transcaching",1)){
								// clean the cache!
								$cache = $jfManager->getCache($language);
								$cache->clean();
							}
						}
					}
				}
				//}
			}

			$pfunc = $this->_profile($pfunc);

			return $parentInsertReturn;
		}
		else {
			return parent::insertObject( $table, $object, $keyName, $verbose);
		}
	}

	/**
	* Overwritten update function to enable storage of translated information.
	* Based on the configuration in the content elements the automatic storage of
	* information is activated or not. It is important that this upgraded method will ensure
	* that all information will be written into the translation table. Only in the case that the
	* default language is choosen the information will be updated directly within the original tables.
	* To make sure that all other information will be written into the tables as expected the
	* statements will be manipulated as needed.
	*
	* @param	string	table name
	* @param	object	instance with information to store
	* @param	string	primary key name of table
	* @param	boolean	update fields with null or not
	* @param	boolean	passthru without storing information in a translation table
	*/
	function updateObject( $table, &$object, $keyName, $updateNulls=true, $passthru=false ) {

		$pfunc = $this->_profile();

		$jfManager = JoomFishManager::getInstance();

		if( isset($jfManager)) {
			$this->_setLanguage($language);
		}
		$conf	= JFactory::getConfig();
		$default_lang	= $conf->getValue('config.defaultlang');

		// check if marked langage of content is the detaul language:
		if ($table=='#__content' && isset($object->id) &&$object->id>0 ){
			$test = JTable::getInstance("Content");
			$test->load($object->id);
			if ($test){
				jimport('joomla.html.parameter');
				$testparams = new JParameter($test->attribs);
				$testlang = $testparams->get('language',false);
				if ($testlang == $language){
					// no translation should be created
					$language = $default_lang;
				}
			}
		}
		// if the currect language is the site default language the translations will not be updated!
		$passthru = $language == $default_lang;

		if( !$passthru && isset($jfManager)) {
			$actContentObject=null;
			if( isset($table) && $table!="") {
				$tableName = preg_replace( '/^#__/', '', $table);
				if ($table != "#__jf_content" ){
					$contentElement = $jfManager->getContentElement( $tableName );
					if( isset( $contentElement ) ) {
						include_once(JPATH_ADMINISTRATOR."/components/com_joomfish/models/ContentObject.php");
						$actContentObject = new ContentObject( $jfManager->getLanguageID($language), $contentElement );
						if( isset( $object->$keyName ) ) {
							$actContentObject->loadFromContentID( $object->$keyName );
							$actContentObject->updateMLContent( $object );
							if( isset( $object->state ) ) {
								$actContentObject->published = ($object->state == 1) ? true : false;
							} else if ( isset( $object->published ) ) {
								$actContentObject->published = ($object->published == 1) ? true : false;
							}
							if ( $jfManager->getCfg("frontEndPublish")){
								$user = JFactory::getUser();
								$access = new stdClass();
								$access->canPublish =  $user->authorize('com_content', 'publish', 'content', 'all');
								if ($access->canPublish) $actContentObject->setPublished($actContentObject->published);
							}

							$actContentObject->store();

							if ($jfManager->getCfg("transcaching",1)){
								// clean the cache!
								$cache = $jfManager->getCache($language);
								$cache->clean();
							}
						}
					}
				}
			}

			$pfunc = $this->_profile($pfunc);

			return parent::updateObject( $table, $object, $keyName, $updateNulls );

		} else {
			return parent::updateObject( $table, $object, $keyName, $updateNulls );
		}
	}

	/**
	 *  Internal function to determit the table name from an sql query
	 *
	 *  This is now deprecated
	 *
	 * @return	string	table name
	 */
	function _getTableName() {

		$pfunc = $this->_profile();

		$conf	= JFactory::getConfig();
		$dbprefix 	= $conf->getValue('config.dbprefix');

		$posFrom = strpos( strtoupper($this->_sql), 'FROM ') + 5; // after 'FROM '
		$posWhere = strpos( strtoupper($this->_sql), 'WHERE ');
		$table = substr( $this->_sql, $posFrom, $posWhere - $posFrom);
		if( strpos( $table, ' ' ) !== false ) {
			$table = substr( $table, 0, strpos( $table, ' ' ) );
		}
		if (isset($dbprefix) && strlen($dbprefix)>0) $table = preg_replace( '/'.$dbprefix.'/', '', $table);
		$table = preg_replace( "/\n/", "", $table) ;

		$pfunc = $this->_profile($pfunc);

		return $table;
	}

	/**
	 * Override query in order to extract ref tables data
	 *
	 * @return n/a
	 */
	function query() {
		$success= parent::query();
		if ($success && !$this->_skipSetRefTables){
			$this->setRefTables();
		}
		return $this->_cursor;
	}

	/** Internal function to return reference table names from an sql query
	 *
	 * @return	string	table name
	 */
	function _getRefTables(){
		return $this->_refTables;
	}

	/**
	 * Private function to get the language for a specific translation
	 *
	 */
	function _setLanguage( & $language){

		$pfunc = $this->_profile();

		// first priority to passed in language
		if (!is_null($language) && $language!=''){
			return;
		}
		// second priority to language for build route function in other language
		// ie so that module can translate the SEF URL
		$registry = JFactory::getConfig();
		$sefLang = $registry->getValue("joomfish.sef_lang", false);
		if ($sefLang){
			//$jfLang = TableJFLanguage::createByShortcode($sefLang, false);
			$language = $sefLang;
		}
		else {
			$jlang = JFactory::getLanguage();
			$language = $jlang->getTag();
		}

		$pfunc = $this->_profile($pfunc);


	}

	/**
	 * Returns a reference to the global Database object, only creating it
	 * if it doesn't already exist. And keeps sure that there is only one
	 * instace for a specific combination of the JDatabase signature
	 *
	 * @param string  Database driver
	 * @param string Database host
	 * @param string Database user name
	 * @param string Database user password
	 * @param string Database name
	 * @param string Common prefix for all tables
	 * @return database A database object
	 * @since 1.5
	*/
	function &getInstance( $driver='mysql', $host='localhost', $user, $pass, $db='', $table_prefix='' )
	{
		$signature = serialize(array($driver, $host, $user, $pass, $db, $table_prefix));
		$database = JDatabase::_getStaticInstance($signature,'JFDatabase',true);

		return $database;
	}


	/**
	 * The following methods are not yet implemented in Joomfish (or may not be possible)
	 */
	// function queryBatch( $abort_on_error=true, $p_transaction_safe = false)
}

class JFLegacyDatabase extends JFDatabase
{
	function JFLegacyDatabase($options){
		parent::JFDatabase( $options);
	}

	function loadObject( &$object, $translate=true, $language=null )
	{
		if (!is_null($object) && is_object($object)){
			$res = parent::loadObject($translate, $language);
			foreach (get_object_vars($res) as $key=>$val) {
				$object->$key = $val;
			}
			return true;
		}
		else {
			$object  = parent::loadObject($translate, $language);
			return $object;
		}
	}

	/**
	 * private function to handle the requirement to call different loadObject version based on class
	 *
	 * @param boolran $translate
	 * @param string $language
	 */
	function _loadObject( $translate=true, $language=null ) {
		return parent::loadObject( $translate, $language );
	}


	function __destruct()
	{
		// I must have a specific destructor otherwise session data may not get written
		// One example of this is system messages not being cleared from the stack
	}

}