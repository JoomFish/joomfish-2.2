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
 * $Id: jfalternative.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage jfalternative
 *
*/

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );
JPlugin::loadLanguage( 'plg_jfalternative', JPATH_ADMINISTRATOR );

$mainframe->registerEvent('onPrepareContent', 'botJoomfishAlternative');

function botJoomfishAlternative(  &$row, &$params, $page=0 ) {

	// simple performance check to determine whether bot should process further
	if ( strpos( $row->text, 'jfalternative' ) === false ) {
		return true;
	}

	// define the regular expression for the bot
	$regex = "#{jfalternative}(.*?){/jfalternative}#s";

	preg_match($regex, $row->text, $matches);
	if (count($matches)!=2) return true;

	list($id,$table,$defaultText) = explode("|",$matches[1]);

	// only support tables at this time.
	if ($table!="content"){
		$row->text = preg_replace( $regex, '', $row->text );
		return true;
	}

	$db = JFactory::getDBO();

	$sql = "SELECT DISTINCT jfl.shortcode, jfl.iso, jfl.title, jfl.image FROM #__jf_content AS jfc, #__languages AS jfl "
	."\n WHERE reference_id=$id AND reference_table='".$table."'"
	."\n  AND jfc.language_id=jfl.lang_id AND jfc.published=1";
	$db->setQuery($sql);
	$alts = $db->loadObjectList();

	if (is_array($alts) && count($alts)>0){
		$alttext = "<div class='jf_altlanguages'>";
		$alttext .=  JText::_('NO TRANSLATION ALTERNATIVE');
		foreach ($alts as $lang) {
			$lang->shortcode = ($lang->shortcode == '') ? $lang->iso : $lang->shortcode;
			global $Itemid;
			$alttext .="&nbsp;<a href='".JRoute::_("index.php?option=com_content&view=article&catid=".$row->catid."&id=$id&lang=".$lang->shortcode)."'>";
			if ($params->get("falt_showAS","flag")=="flag"){
				if( isset($lang->image) && $lang->image!="" ) {
					$langImg = '/images/' .$lang->image;
				} else {
					$langImg = '/components/com_joomfish/images/flags/' .$lang->shortcode .".gif";
				}
				$langImg ='<img src="' .JURI::root() . $langImg. '" alt="' .$lang->name. '" title="' .$lang->name. '" border="0" />';
				$alttext .=$langImg."</a>&nbsp;";
			}
			else {
				$alttext .=$lang->name."</a>";
			}
		}
		$alttext .= "</div>";
		$row->text = preg_replace( $regex, $alttext, $row->text );
	}
	else {
		$alttext = "<div class='jf_altlanguages'>";
		$alttext .= JText::_('NO TRANSLATION AVAILABLE');
		$alttext .= "</div>";
		$row->text = preg_replace( $regex, $alttext, $row->text );
	}

	return true;
}
?>
