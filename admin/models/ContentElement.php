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
 * $Id: ContentElement.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage Models
 *
*/


// Don't allow direct linking
defined( '_JEXEC' ) or die( 'Restricted access' );

include_once(dirname(__FILE__).DS."ContentElementTable.php");

/**
 * Content element class based on the xml file
 *
 * @package joomfish
 * @subpackage administrator
 * @copyright 2003 - 2012, Think Network GmbH, Munich
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Revision: 1592 $
 * @author Alex Kempkens
 */
class ContentElement {
	private $_xmlFile;
	public $checked_out=false;
	public $Name='';
	public $Author='';
	public $Version='';
	public $Description='';
	public $PrimaryKey = "id";

	public $referenceInformation;

	/**	field (if any) that keyword	filters apply to*/
	private $_keywordFilter=null;
	private $_categoryFilter=null;
	private $_authorFilter=null;


	/** Standard constructor, which loads already standard information
	 * for easy and direct access
	*/
	public function __construct ( $xmlDoc ) {
		$this->_xmlFile = $xmlDoc;

		if( isset($this->_xmlFile) ) {
			$valueElement = $this->_xmlFile->getElementsByTagName('name')->item(0);
			$this->Name = trim($valueElement->textContent);

			$valueElement = $this->_xmlFile->getElementsByTagName('author')->item(0);
			$this->Author = trim($valueElement->textContent);

			$valueElement = $this->_xmlFile->getElementsByTagName('version')->item(0);
			$this->Version = trim($valueElement->textContent);

			$valueElement = $this->_xmlFile->getElementsByTagName('description')->item(0);
			$this->Description = trim($valueElement->textContent);
		}
	}

	/** Type of reference
	*/
	public function getReferenceType() {
		if( !isset($this->referenceInformation["type"]) && isset($this->_xmlFile) ) {
			$tableElement = $this->_xmlFile->getElementsByTagName('reference')->item(0);
			$tableName = trim($tableElement->getAttribute( 'type' ));
			$this->referenceInformation["type"] = $tableName;
		}

		return $this->referenceInformation["type"];
	}

	/**
	 * Public function to return array of filters included in contentelement file
	 */
	public function getAllFilters(){
		$allFilters = array();
		if(isset($this->_xmlFile) ) {
			$fElement = $this->_xmlFile->getElementsByTagName('translationfilters')->item(0);
			if (!isset($fElement) || !$fElement->hasChildNodes()){
				return $allFilters;
			}
			foreach ($fElement->childNodes as $child){
				$type = $child->nodeName;
				$filter = "_$type"."Filter";
				$this->$filter=$child->textContent;
				$allFilters[$type]=trim($this->$filter);
			}
		}
		return $allFilters;

	}

	/**
	 * function that returns filter string and handles getting filter info from xmlfile if needed
	 *
	 */
	public function getFilter($type){
		$filter = "_$type"."Filter";
		if( !isset($this->$filter) && isset($this->_xmlFile) ) {
			$xpath = new DOMXPath($this->_xmlFile);
			$fElement = $xpath->query('//translationfilters/'.$type);
			if (!isset($fElement)){
				$this->$filter=false;
				return $this->$filter;
			}
			$this->$filter=trim($fElement->textContent);
		}
		return $this->$filter;

	}

	/**
	 * returns translation filter keyword field (if any)
	 */
	public function getKeywordFilter() {
		return $this->_getFilter("keyword");
	}

	/**
	 *  returns category filter fieldname (if any)
	 */
	public function getCategoryFilter() {
		return $this->_getFilter("category");
	}

	/**
	 *  returns author filter fieldname (if any)
	 */
	public function getAuthorFilter() {
		return $this->_getFilter("author");
	}

	/** Name of the refering table
	*/
	public function getTableName() {
		if( !isset($this->referenceInformation["tablename"]) && isset($this->_xmlFile) ) {
			$xpath = new DOMXPath($this->_xmlFile);
			$tableElement = $xpath->query('//reference/table')->item(0);

			$tableName = trim($tableElement->getAttribute( 'name' ));
			$this->referenceInformation["tablename"] = strtolower($tableName);
		}

		return $this->referenceInformation["tablename"];
	}

	/**
	 * Name of reference id (in other words the primary key)
	 */
	public function getReferenceId() {
		if( isset($this->referenceInformation["tablename"]) && isset($this->_xmlFile) ) {
			$xpath = new DOMXPath($this->_xmlFile);
			$tableElement = $xpath->query('//reference/table')->item(0);
			$tableFields = $tableElement->getElementsByTagName('field');

			foreach ($tableFields as $field) {
				if (trim($field->getAttribute('type'))=="referenceid") {
					$refid = trim($field->getAttribute('name'));
					if ($refid!=null) return $refid;
					else return "id";
				}
			}
		}
		return "id";
	}


	/** Array of the field elements in the table
	 * @return reference to the table information
	*/
	public function & getTable() {
		if( !isset($this->referenceInformation["table"]) && isset($this->_xmlFile) ) {
			$xpath = new DOMXPath($this->_xmlFile);
			$tableElement = $xpath->query('//reference/table')->item(0);

			$this->referenceInformation["table"] = new ContentElementTable( $tableElement );
		}

		return $this->referenceInformation["table"];
	}

	/** Generating the sql statement to retrieve the information
	 * from the database
	 */
	public function createContentSQL( $idLanguage=-1, $contentid=null, $limitStart=-1, $maxRows=-1 , $filters=array()) {
		$db = JFactory::getDBO();
		$sqlFields=null;
		$where=array();
		$order=null;
		$join=null;
		$contentTable = $this->getTable();
		foreach ($filters as $filter) {
			$sqlFilter= $filter->createFilter($this);
			if ($sqlFilter!="") $where[]=$sqlFilter;
		}
		foreach( $contentTable->Fields as $tableField ) {
			// Based on the types we might want to have special names ;-)
			switch ($tableField->Type) {
				case "referenceid":
					$contentid_exist = (isset($contentid) && $contentid!=-1 );
					if( strtolower($tableField->Name) != "id" ) {
						$sqlFields[] = 'c.' .$tableField->Name. ' as id';
						if( $contentid_exist) $where[] = 'c.' .$tableField->Name. '=' .$db->Quote($contentid );
					}
					else {
						if( $contentid_exist ) $where[] = 'c.id=' .$contentid ;
					}
					$join[] = 'c.' .$tableField->Name. '=jfc.reference_id';
					break;
				case "titletext":
					if( strtolower($tableField->Name) != "title" ) {
						$sqlFields[] = 'c.' .$tableField->Name. ' as title';
					}
					$join[] = "jfc.reference_field='" .$tableField->Name. "'";
					$order[] = 'c.' .$tableField->Name;
					break;
				case "modified_date":
					if( strtolower($tableField->Name) != "modified_date" ) {
						$sqlFields[] = 'c.' .$tableField->Name. ' as modified_date';
					}
					break;
				case "checked_out_by":
					if( strtolower($tableField->Name) != "check_out" ) {
						$sqlFields[] = 'c.' .$tableField->Name. ' as check_out';
					}
					break;
			}

			// I want to have each field with his original name in the select
			// so the special fields will be only addon's!
			// Reason: To grap the data later it's more easy to refer to the original names of the XML file
			$sqlFields[] = 'c.' .$tableField->Name. '';
		}

		$sqlFields[] = "jfc.id as jfc_id";
		$sqlFields[] = "jfc.value as titleTranslation";
		$sqlFields[] = "jfc.modified as lastchanged";
		$sqlFields[] = 'jfc.published as published';
		$sqlFields[] = 'jfc.language_id';
		$sqlFields[] = 'jfl.title as language';
		$sqlFields[] = "jfc.reference_id as jfc_refid";
		$join[] = "jfc.reference_table='$contentTable->Name'";
		// Now redundant
		/*
		if( isset($contentid) && $contentid!=-1 ) {
		$where[] = 'c.id=' .$contentid;
		}
		*/
		if( isset($idLanguage) && $idLanguage!="" && $idLanguage!=-1 ) {
			if( $idLanguage=="NULL" ) {
				$where[] = "jfc.language_id IS NULL";
			} else {
				$join[] = "jfc.language_id=$idLanguage";
			}
		}

		if( $contentTable->Filter != '' ) {
			$where[] = $contentTable->Filter;
		}


		$sql = "SELECT " .implode( ', ', $sqlFields )
		. "\nFROM #__" .$contentTable->Name. ' as c'
		. "\nLEFT JOIN #__jf_content as jfc ON " .implode( ' AND ', $join )
		. "\nLEFT JOIN #__languages as jfl ON jfc.language_id=jfl.lang_id"
		. (count( $where ) ? "\nWHERE " . implode( ' AND ', $where ) : "")
		. (count( $order ) ? "\nORDER BY " . implode( ', ', $order ) : "");

		if( $limitStart!=-1 && $maxRows>0) {
			$sql .= "\nLIMIT $limitStart, $maxRows";
		}
		//echo "sql = <pre>$sql</pre><br />";

		return $sql;
	}

	/** Generating the sql statement to retrieve the orphans information from the database
	 */
	public function createOrphanSQL( $idLanguage=-1, $contentid=null, $limitStart=-1, $maxRows=-1 , $filters=array()) {


		$sqlFields=null;
		$sqlFields[] = "jfc.id as jfc_id";
		$sqlFields[] = "jfc.reference_id as jfc_refid";
		$sqlFields[] = "jfc.value as titleTranslation";
		$sqlFields[] = "jfc.modified as lastchanged";
		$sqlFields[] = 'jfc.published as published';
		$sqlFields[] = 'jfc.language_id';
		$sqlFields[] = 'jfl.title as language';
		$sqlFields[] = 'jfc.original_text as original_text';

		$where=array();
		$order=null;
		$join=null;
		$contentTable = $this->getTable();
		foreach ($filters as $filter) {
			$sqlFilter= $filter->createFilter($this);
			if ($sqlFilter!="") $where[]=$sqlFilter;
		}
		foreach( $contentTable->Fields as $tableField ) {
			// Based on the types we might want to have special names ;-)
			switch ($tableField->Type) {
				case "referenceid":
					$contentid_exist = (isset($contentid) && $contentid!=-1 );
					if( strtolower($tableField->Name) != "id" ) {
						$sqlFields[] = 'c.' .$tableField->Name. ' as id';
						if( $contentid_exist) $where[] = 'c.' .$tableField->Name. '=' .$contentid ;
					}
					else {
						if( $contentid_exist ) $where[] = 'c.id=' .$contentid ;
					}
					$join[] = 'c.' .$tableField->Name. '=jfc.reference_id ';
					$where[]  = 'c.' .$tableField->Name. ' IS NULL ';
					$sqlFields[] = 'c.' .$tableField->Name. '';
					break;
				case "titletext":
					if( strtolower($tableField->Name) != "title" ) {
						$sqlFields[] = 'c.' .$tableField->Name. ' as title';
					}
					//$join[] = "jfc.reference_field='" .$tableField->Name. "'";
					$where[] = "jfc.reference_field='" .$tableField->Name. "'";
					$sqlFields[] = 'c.' .$tableField->Name. '';
					//					$order[] = 'c.' .$tableField->Name;
					break;
			}
		}

		//$join[] = "jfc.reference_table='$contentTable->Name'";
		$where[] = "jfc.reference_table='$contentTable->Name'";
		if( !isset($idLanguage) || ($idLanguage!="" && $idLanguage!=-1 )) {
			$where[] = "jfc.language_id=$idLanguage";
		}

		$sql = "SELECT " .implode( ', ', $sqlFields )
		. "\nFROM #__jf_content as jfc"
		. "\nLEFT JOIN #__" .$contentTable->Name. ' as c ON '.implode( ' AND ', $join )
		. "\nLEFT JOIN #__languages as jfl ON jfc.language_id=jfl.lang_id"
		. (count( $where ) ? "\nWHERE " . implode( ' AND ', $where ) : "")
		. (count( $order ) ? "\nORDER BY " . implode( ', ', $order ) : "");

		if( $limitStart!=-1 ) {
			$sql .= "\nLIMIT $limitStart, $maxRows";
		}
		//echo "orphansql = $sql<br />";

		return $sql;
	}

	/** Generating the sql statement to count the information
	 */
	public function countContentSQL($idLanguage=-1, $filters=array()) {
		$contentTable = $this->getTable();
/*
		$where=null;

		// Add standard filters
		foreach ($filters as $filter) {
			$sqlFilter= $filter->createFilter($this);
			if ($sqlFilter!="") $where[]=$sqlFilter;
		}
		if( $contentTable->Filter != '' ) {
			$where[] = $contentTable->Filter;
		}

		$sql = "SELECT *"
		. "\nFROM #__" .$contentTable->Name. ' as c'
		. (count( $where ) ? "\nWHERE " . implode( ' AND ', $where ) : "");
*/
		/* Try to simplify the count queries.
		Check only on original table including the standard filters as we assume that*/

		$join=null;
		$where=null;
		$referencefield = "";
		
		foreach( $contentTable->Fields as $tableField ) {
			// Based on the types we might want to have special names ;-)
			if ( $tableField->Type == "referenceid" ) {
				$join[] = 'c.' .$tableField->Name. '=jfc.reference_id';
				$referencefield ='c.' .$tableField->Name;
			}
		}

		$sqlFields[] = "COUNT(distinct $referencefield)";
		$join[] = "jfc.reference_table='$contentTable->Name'";
		if( isset($idLanguage) && $idLanguage!=-1 ) {
			if( $idLanguage=='NULL' ) {
				$where[] = "jfc.language_id IS NULL";
			} else {
				$join[] = "jfc.language_id=$idLanguage";
			}
		}

		foreach ($filters as $filter) {
			$sqlFilter= $filter->createFilter($this);
			if ($sqlFilter!="") $where[]=$sqlFilter;
		}
		if( $contentTable->Filter != '' ) {
			$where[] = $contentTable->Filter;
		}

		$sql = "SELECT " .implode( ', ', $sqlFields )
		. "\nFROM #__" .$contentTable->Name. ' as c'
		. "\nLEFT JOIN #__jf_content as jfc ON " .implode( ' AND ', $join )
		. (count( $where ) ? "\nWHERE " . implode( ' AND ', $where ) : "");


		//echo "<pre>count-sql = $sql</pre><br />";
		return $sql;
	}

	/**
	 * Returing the number of elements corresponding with the information of the class
	 * @return total number of elements
	 */
	public function countReferences( $idLanguage=-1, $filters=array() ) {
		$db = JFactory::getDBO();

		/*
		$db->setQuery( $this->countContentSQL($idLanguage, $filters) );
		$result = $db->loadObjectList();
		echo $db->getErrorMsg();
		return count( $result );
		*/

		$db->setQuery( $this->countContentSQL( $idLanguage, $filters ) );
		$count=$db->loadResult();
		//echo "count = $count<br/>";
		return $count;
	}
	
	/**
	 * Returns the component specific information related the UI screen settings, options, ...
	 * The information allow the direct translation module to interact with the admin form and allow instant access
	 * to the specific translation screen
	 * @return	array	of admin form parameters
	 */
	public function getComponentInformation() {
		$componentInfo = array();
		if(isset($this->_xmlFile) ) {
			$xpath = new DOMXPath($this->_xmlFile);
			$componentElement = $xpath->query('//reference/component')->item(0);
			if (!isset($componentElement) || !$componentElement->hasChildNodes()){
				return $componentInfo;
			}
			$forms = $componentElement->getElementsByTagName( 'form' );
			foreach ($forms as $componentForm){
				$componentInfo[] = $componentForm->textContent;
			}
		}
		return $componentInfo;

	}
}
?>
