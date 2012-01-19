<?php
/**
 * Joom!Fish - Multi Lingual extention and translation manager for Joomla!
 * Copyright (C) 2003 - 2012 Think Network GmbH, Munich
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
 * $Id: jfcategories.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage jfcategories
 *
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

$mainframe->registerEvent( 'onSearch', 'plgSearchJFCategories' );

JPlugin::loadLanguage( 'plg_search_jfcategories' );

/**
 * Categories Search method
 *
 * The sql must return the following fields that are
 * used in a common display routine: href, title, section, created, text,
 * browsernav
 * @param string Target search string
 * @param string mathcing option, exact|any|all
 * @param string ordering option, newest|oldest|popular|alpha|category
 * @param mixed An array if restricted to areas, null if search all
 */
function plgSearchJFCategories( $text, $phrase='', $ordering='', $areas=null )
{
	$db		= JFactory::getDBO();
	$user	= JFactory::getUser();

	$registry = JFactory::getConfig();
	$lang = $registry->getValue("config.jflang");

	require_once(JPATH_SITE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php');

	if (is_array( $areas )) {
		if (!array_intersect( $areas, array_keys( plgSearchCategoryAreas() ) )) {
			return array();
		}
	}

	// load plugin params info
 	$plugin = JPluginHelper::getPlugin('search', 'jfcategories');
 	$pluginParams = new JParameter( $plugin->params );

	$limit = $pluginParams->def( 'search_limit', 50 );
	$activeLang 	= $pluginParams->def( 'active_language_only', 0);

	$text = trim( $text );
	if ( $text == '' ) {
		return array();
	}

	switch ( $ordering ) {
		case 'alpha':
			$order = 'a.name ASC';
			break;

		case 'category':
		case 'popular':
		case 'newest':
		case 'oldest':
		default:
			$order = 'a.name DESC';
	}

	$text	= $db->Quote( '%'.$db->getEscaped( $text, true ).'%', false );
	$query	= 'SELECT a.title, a.description AS text, "" AS created,'
	. ' "2" AS browsernav,'
	. ' s.id AS secid, a.id AS catid,'
	. ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug,'
	. ' jfl.code as jflang, jfl.title as jflname'
	. ' FROM #__categories AS a'
	. ' INNER JOIN #__sections AS s ON s.id = a.section'
		. "\n LEFT JOIN #__jf_content as jfc ON reference_id = a.id"
		. "\n LEFT JOIN #__languages as jfl ON jfc.language_id = jfl.lang_id"
	. ' WHERE  jfc.value LIKE '.$text
	. ' AND a.published = 1'
	. ' AND s.published = 1'
	. ' AND a.access <= '.(int) $user->get('aid')
	. ' AND s.access <= '.(int) $user->get('aid')
		. "\n AND jfc.reference_table = 'categories'"
		. ( $activeLang ? "\n AND jfl.code = '$lang'" : '')
	. ' GROUP BY a.id'
	. ' ORDER BY '. $order
	;
	$db->setQuery( $query, 0, $limit );
	
	$rows = $db->loadObjectList();

	$count = count( $rows );
	for ( $i = 0; $i < $count; $i++ ) {
		$rows[$i]->href = ContentHelperRoute::getCategoryRoute($rows[$i]->slug, $rows[$i]->secid);
		$rows[$i]->section 	= JText::_( 'Category' )." - ".$rows[$i]->jflname;
	}

	return $rows;
}
