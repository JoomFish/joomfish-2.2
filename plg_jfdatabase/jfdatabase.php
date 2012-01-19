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
 * $Id: jfdatabase.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage jfdatabase
 * @version 2.0
 *
*/

/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Restricted access' );

// In PHP5 this should be a instance_of check
// Currently Joom!Fish does not need to be active in Administrator
// This might be an extended version
if($mainframe->isAdmin()) {
	return;
}

// Joom!Fish bot get's only activated if essential files are missing
//if ( !file_exists( JPATH_PLUGINS .DS. 'system' .DS. 'jfdatabase' .DS. 'jfdatabase.class.php' )) {
jimport('joomla.filesystem.file');
if ( !JFile::exists( JPATH_PLUGINS .DS. 'system' .DS. 'jfdatabase' .DS. 'jfdatabase_inherit.php' )) {
	JError::raiseNotice('no_jf_plugin', JText::_('Joom!Fish plugin not installed correctly. Plugin not executed') .' (jfdb)');
	return;
}

jimport('joomla.filesystem.file');
if(JFile::exists(JPATH_SITE .DS. 'components' .DS. 'com_joomfish' .DS. 'helpers' .DS. 'defines.php')) {
	require_once( JPATH_SITE .DS. 'components' .DS. 'com_joomfish' .DS. 'helpers' .DS. 'defines.php' );
	JLoader::register('JoomfishManager', JOOMFISH_ADMINPATH .DS. 'classes' .DS. 'JoomfishManager.class.php' );
	JLoader::register('JoomFishVersion', JOOMFISH_ADMINPATH .DS. 'version.php' );
	JLoader::register('JoomFish', JOOMFISH_PATH .DS. 'helpers' .DS. 'joomfish.class.php' );	
} else {
	JError::raiseNotice('no_jf_extension', JText::_('Joom!Fish extension not installed correctly. Plugin not executed') .' (define)');
	return;
}

/**
* Exchange of the database abstraction layer for multi lingual translations.
*/
class plgSystemJFDatabase extends JPlugin{
	/**
	 * stored configuration from plugin
	 *
	 * @var object configuration information
	 */
	var $_config = null;

	function plgSystemJFDatabase(& $subject, $config)
	{
		global $mainframe;
		if ($mainframe->isAdmin()) {
			// This plugin is only relevant for use within the frontend!
			return;
		}
		parent::__construct($subject, $config);

		// put params in registry so I have easy access to them later
		$conf = JFactory::getConfig();
		$conf->setValue("jfdatabase.params",$this->params);

		$this->_config = array(
		'adapter' 	=> $this->params->get('dbadapter', "inheritor")
		);

		if(defined('JOOMFISH_PATH')) {
			$this->_jfInitialize();
		} else {
			JError::raiseNotice('no_jf_component', JText::_('Joom!Fish component not installed correctly. Plugin not executed') .' (init)');
		}
	}

	/**
	 * During this event we setup the database and link it to the Joomla! ressources for future use
	 * @return void
	 */
	function onAfterInitialise()
	{
		global $mainframe;
		if ($mainframe->isAdmin()) {
			// This plugin is only relevant for use within the frontend!
			return;
		}
		$this->_setupJFDatabase();
	}

	function onAfterRender()
	{
		$db = JFactory::getDBO();
		if (!is_array($db->profileData))	{
			return;
		}
		$buffer = JResponse::getBody();
		$info = "";
		$info .=  "<div style='font-size:11px'>";
		uasort($db->profileData,array($this,"sortprofile"));
		foreach ($db->profileData as $func=>$data) {
			$info .=  "$func = ".round($data["total"],4)." (".$data["count"].")<br />";
		}
		$info .=  "</div>";
		$buffer = str_replace("JFTimings",$info,$buffer);
		JResponse::setBody($buffer);
	}

	function sortprofile($a,$b){
		return $a["total"]>=$b["total"]?-1:1;
	}
	
	/**
	 * Setup for the Joom!Fish database connectors, overwriting the original instances of Joomla!
	 * Which connector is used and which technique is based on the extension configuration
	 * @return void
	 */
	function _setupJFDatabase(){
		if (file_exists( dirname(__FILE__).DS.'jfdatabase'.DS.'jfdatabase_inherit.php' )) {
			require_once( dirname(__FILE__).DS.'jfdatabase'.DS.'jfdatabase_inherit.php' );

			$conf = JFactory::getConfig();

			$host 		= $conf->getValue('config.host');
			$user 		= $conf->getValue('config.user');
			$password 	= $conf->getValue('config.password');
			$db   		= $conf->getValue('config.db');
			$dbprefix 	= $conf->getValue('config.dbprefix');
			$dbtype 	= $conf->getValue('config.dbtype');
			$debug 		= $conf->getValue('config.debug');
			$driver 	= $conf->getValue('config.dbtype');

			$options = array("driver"=>$driver, "host"=>$host, "user"=>$user, "password"=>$password, "database"=>$db, "prefix"=>$dbprefix,"select"=>true);

			$db = & JFactory::getDBO();
			$db = new JFDatabase($options);
			$debug = $conf->getValue('config.debug');
			$db->debug($debug);

			if ($db->getErrorNum() > 2) {
				JError::raiseError('joomla.library:'.$db->getErrorNum(), 'JDatabase::getInstance: Could not connect to database <br/>' . $db->getErrorMsg() );
			}

			$conf->setValue('config.mbf_content', 1 );
			$conf->setValue('config.multilingual_support', 1 );

			// legacy mode only
			// check on legacy mode on/off by testing existence of $database global
			if (defined('_JLEGACY')  && array_key_exists('database',$GLOBALS)){
				$GLOBALS['database'] = new JFLegacyDatabase($options);
				$GLOBALS['database']->debug($conf->getValue('config.debug'));
			}
		}

	}

	/** This function initialize the Joom!Fish manager in order to have
	  * easy access and prepare certain information.
	  * @access private
	  */
	function _jfInitialize ( ) {
	}
}
