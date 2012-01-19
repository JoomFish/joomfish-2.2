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
 * $Id: intercept.jdatabasemysql.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage jfdatabase
 * @version 2.0
 *
*/


// Don't allow direct linking
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

class interceptDB extends JDatabaseMySQL {

	/**
	 * This special constructor reuses the existing resource from the existing db connecton
	 *
	 * @param unknown_type $options
	 */
	function __construct($options){
		$db =  JFactory::getDBO();
		
		// support for recovery of existing connections (Martin N. Brampton)
		if (isset($this->_options)) $this->_options = $options;

		$select		= array_key_exists('select', $options)	? $options['select']	: true;
		$database	= array_key_exists('database',$options)	? $options['database']	: '';

		// perform a number of fatality checks, then return gracefully
		if (!function_exists( 'mysql_connect' )) {
			$this->_errorNum = 1;
			$this->_errorMsg = 'The MySQL adapter "mysql" is not available.';
			return;
		}

		// connect to the server
		$this->_resource =  $db->_resource;

		// finalize initialization
		JDatabase::__construct($options);

		// select the database
		if ( $select ) {
			$this->select($database);
		}

	}

	function _getFieldCount(){
		if (!is_resource($this->_cursor)){
			// This is a serious problem since we do not have a valid db connection
			// or there is an error in the query
			$error = JError::raiseError( 500, JTEXT::_('No valid database connection:') .$this->getErrorMsg());
			return $error;
		}

		$fields = mysql_num_fields($this->_cursor);
		return $fields;
	}

	function _getFieldMetaData($i){
		$meta = mysql_fetch_field($this->_cursor, $i);
		return $meta;
	}
	

	function fillRefTableCache($cacheDir,$cacheFile){

		$pfunc = $this->_profile();

		$cacheFileContent = serialize($this->_refTables);
		JFile::write($cacheFile,$cacheFileContent);
		// clean out old cache files
		// This could be very slow for long list of old files -
		// TODO store in database instead
		$this->cleanRefTableCache($cacheDir);
		
		$pfunc = $this->_profile($pfunc);

	}

	function cleanRefTableCache($cacheDir){

		$pfunc = $this->_profile();
		
		$files =JFolder::files($cacheDir); 
		foreach ($files as $file) {
			if (($file != '.') && ($file != '..')) {
				$file = "$cacheDir/$file";
				if (JFile::exists($file) && @filemtime($file) < $this->cacheExpiry) {
					if (!JFile::delete($file)) {
						JError::raiseWarning ( 200, JText::_('problems clearing cache file ' .$file));
					}
				}
			}
		}
		
		$pfunc = $this->_profile($pfunc);

		return true;
	}

	function _logSetRefTablecache($action,$tempsql,$sql_exNos,$sqlHash){

		$pfunc = $this->_profile();

		$logfile = dirname(__FILE__)."/qalog.txt";
		$handle = fopen($logfile,"ab");
		// replace tabs and carriage returns with spaces
		fwrite($handle,"$action ");
		fwrite($handle,preg_replace("/([\t\n\r\f])/"," ",$tempsql));
		fwrite($handle," #@�^�@# ");
		fwrite($handle,preg_replace("/([\t\n\r\f])/"," ",$sql_exNos));
		fwrite($handle," #@�^�@# ");
		fwrite($handle,preg_replace("/([\t\n\r\f])/"," ",$sqlHash));
		fwrite($handle," # JF LINE END# \n");

		fclose($handle);
		
		$pfunc = $this->_profile($pfunc);

	}
	
	function setRefTables(){

		$pfunc = $this->_profile();

		// Before joomfish manager is created since we can't translate so skip this anaylsis
		$jfManager = JoomFishManager::getInstance();
		if (!$jfManager) return;

		// This could be speeded up by the use of a cache - but only of benefit is global caching is off
		$tempsql = $this->_sql;
		// only needed for selects at present - possibly add for inserts/updates later
		if (strpos(strtoupper(trim($tempsql)),"SELECT")!==0) {
			$pfunc = $this->_profile($pfunc);
			return;
		}
		
		// Ignore Joomfish translation query	
		if (strpos($tempsql,"SELECT jf_content.reference_field, jf_content.value")===0){
			$pfunc = $this->_profile($pfunc);
			return;
		}
	
		$config = JFactory::getConfig();
	
		jimport('joomla.client.helper');
		$FTPOptions = JClientHelper::getCredentials('ftp');
		// we won't use this caching if FTP layer ie enabled
		if ($jfManager->getCfg("qacaching",1) && $FTPOptions['enabled'] == 1){
			$cachepath = JPATH_CACHE;
			$cachetime = $config->getValue('config.cachetime',0);
			// remove time formats (assume all numbers are not necessay - this is experimental
			// for example table names or column names could contain numbers
			// so this version only replaces numbers not adjacent to alpha characters i.e.field2 doesn't become field
			$sql_exNos = preg_replace("/(?![a-z])(.)([0-9]+)(?![a-z]+)/i",'${1}',$tempsql);
			$sql_exNos = preg_replace("/(?![a-z]).([0-9]+)$/i",'${1}',$sql_exNos);

			if ( $config->getValue('config.debug',0)) {
				echo "<p style='background-color:bisque;border:solid 1px black'><strong>setRefTables debug:</strong><br / >"
				. "tempsql   = $tempsql<br />"
				. "sql_exNos = $sql_exNos"
				. "</p>";
			}

			$sqlHash = md5($sql_exNos );

			$this->cacheExpiry = time() - $cachetime;
			$cacheDir = "$cachepath/refTableSQL";
			if (!JFolder::exists($cacheDir)) JFolder::create($cacheDir);
			$cacheFile = "$cacheDir/$sqlHash";
			if (JFile::exists($cacheFile) &&	@filemtime($cacheFile) > $this->cacheExpiry) {
				$cacheFileContent = JFile::read($cacheFile);
				$this->_refTables = unserialize($cacheFileContent);

				if ($jfManager->getCfg("qalogging",0)){
					$this->_logSetRefTablecache("r",$tempsql,$sql_exNos,$sqlHash);
				}
		
				$pfunc = $this->_profile($pfunc);
	
				return;
			}

			if($this->_cursor===true || $this->_cursor===false) {
				if ($jfManager->getCfg("qalogging",0)){
					$this->_logSetRefTablecache("wtf",$tempsql,$sql_exNos,$sqlHash);
				}
				$this->fillRefTableCache($cacheDir,$cacheFile);
		
				$pfunc = $this->_profile($pfunc);

				return;
			}
		}

		// get column metadata
		$fields = $this->_getFieldCount();

		//print "<br> $tempsql $this->_cursor $fields";

		if ($jfManager->getCfg("qacaching",1) && $FTPOptions['enabled'] == 1){
			if ($fields<=0) {
				if ($jfManager->getCfg("qalogging",0)){
					$this->_logSetRefTablecache("w0f",$tempsql,$sql_exNos,$sqlHash);
				}
				$this->fillRefTableCache($cacheDir,$cacheFile);
		
				$pfunc = $this->_profile($pfunc);

				return;
			}
		}
		if ($fields<=0) {
		
			$pfunc = $this->_profile($pfunc);

			return;
		}
		
		$this->_refTables=array();
		$this->_refTables["fieldTablePairs"]=array();
		$this->_refTables["tableAliases"]=array();
		$this->_refTables["reverseTableAliases"]=array();
		$this->_refTables["fieldAliases"]=array();
		$this->_refTables["fieldTableAliasData"]=array();
		$this->_refTables["fieldCount"]=$fields;
		$this->_refTables["sql"]=$tempsql;
		// local variable to keep track of aliases that have already been analysed
		$tableAliases = array();
		for ($i = 0; $i < $fields; ++$i) {
			$meta = $this->_getFieldMetaData($i);
			if (!$meta) {
				echo JText::_("No information available<br />\n");
			}
			else {
				$tempTable =  $meta->table;
				// if I have already found the table alias no need to do it again!
				if (array_key_exists($tempTable,$tableAliases)){
					$value = $tableAliases[$tempTable];
				}
				// mysli only
				else if (isset($meta->orgtable)){
					$value = $meta->orgtable;
					if (isset($this->_table_prefix) && strlen($this->_table_prefix)>0 && strpos($meta->orgtable,$this->_table_prefix)===0) $value = substr($meta->orgtable, strlen( $this->_table_prefix));
					$tableAliases[$tempTable] = $value;
				}
				else {
					if (!isset($tempTable) || strlen($tempTable)==0) {
						continue;
					}
					//echo "<br>Information for column $i of ".($fields-1)." ".$meta->name." : $tempTable=";
					$tempArray=array();
					$prefix = $this->_table_prefix;
					preg_match_all("/`?$prefix(\w+)`?\s+(?:AS\s)?+`?".$tempTable."`?[,\s]/i",$this->_sql, $tempArray, PREG_PATTERN_ORDER);
					//preg_match_all("/`?$prefix(\w+)`?\s+AS\s+`?".$tempTable."`?[,\s]/i",$this->_sql, $tempArray, PREG_PATTERN_ORDER);
					if (count($tempArray)>1 && count($tempArray[1])>0) $value = $tempArray[1][0];
					else $value = null;
					if (isset($this->_table_prefix) && strlen($this->_table_prefix)>0 && strpos($tempTable,$this->_table_prefix)===0) $tempTable = substr($tempTable, strlen( $this->_table_prefix));
					$value = $value?$value:$tempTable;
					$tableAliases[$tempTable]=$value;
				}

				if ((!($value=="session" || strpos($value,"jf_")===0)) && $this->translatedContentAvailable($value)){
					/// ARGH !!! I must also look for aliases for fieldname !!
					if (isset($meta->orgname)){
						$nameValue = $meta->orgname;
					}
					else {
						$tempName = $meta->name;
						$tempArray=array();
						// This is a bad match when we have "SELECT id" at the start of the query
						preg_match_all("/`?(\w+)`?\s+(?:AS\s)?+`?".$tempName."`?[,\s]/i",$this->_sql, $tempArray, PREG_PATTERN_ORDER);
						//preg_match_all("/`?(\w+)`?\1s+AS\s+`?".$tempName."`?[,\s]/i",$this->_sql, $tempArray, PREG_PATTERN_ORDER);
						if (count($tempArray)>1 && count($tempArray[1])>0) {
							//echo "$meta->name is an alias for ".$tempArray[1][0]."<br>";
							// must ignore "SELECT id"
							if (strtolower($tempArray[1][0])=="select"){
								$nameValue = $meta->name;
							}
							else {
								$nameValue = $tempArray[1][0];
							}
						}
						else $nameValue = $meta->name;
					}
					
					if (!array_key_exists($value,$this->_refTables["tableAliases"])) $this->_refTables["tableAliases"][$value]=$meta->table;
					if (!array_key_exists($meta->table,$this->_refTables["reverseTableAliases"])) $this->_refTables["reverseTableAliases"][$meta->table]=$value;
					
					// I can't use the field name as the key since it may not be unique!
					if (!in_array($value,$this->_refTables["fieldTablePairs"])) $this->_refTables["fieldTablePairs"][]=$value;
					if (!array_key_exists($nameValue,$this->_refTables["fieldAliases"])) $this->_refTables["fieldAliases"][$meta->name]=$nameValue;

					// Put all the mapping data together so that everything is in sync and I can check fields vs aliases vs tables in one place
					$this->_refTables["fieldTableAliasData"][$i]=array("fieldNameAlias"=>$meta->name, "fieldName"=>$nameValue,"tableNameAlias"=>$meta->table,"tableName"=>$value);
					
				}

			}
		}
		if ($jfManager->getCfg("qacaching",1) && $fields>1 && $FTPOptions['enabled'] == 1){
			if ($jfManager->getCfg("qalogging",0)){
				$this->_logSetRefTablecache("wn",$tempsql,$sql_exNos,$sqlHash);
			}
			$this->fillRefTableCache($cacheDir,$cacheFile);
		}
		
		$pfunc = $this->_profile($pfunc);


	}
	
}
?>
