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
 * $Id: jfcontent.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage jfcontent
 *
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

$mainframe->registerEvent( 'onSearch', 'plgSearchJFContent' );

JPlugin::loadLanguage( 'plg_search_jfcontent' );

/**
* Search method
*
* The sql must return the following fields that are used in a common display
* routine: href, title, section, created, text, browsernav
* @param string Target search string
* @param integer The state to search for -1=archived, 0=unpublished, 1=published [default]
* @param string A prefix for the section label, eg, 'Archived '
*/
function plgSearchJFContent( $text, $phrase='', $ordering='', $areas=null )
{
	global $mainframe;

	$registry = JFactory::getConfig();
	$lang = $registry->getValue("config.jflang");

	$db		= JFactory::getDBO();
	$user	= JFactory::getUser();

	require_once(JPATH_SITE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php');

	if (is_array( $areas )) {
		// Use main content search area
		if (!array_intersect( $areas, array_keys( plgSearchContentAreas() ) )) {
			return array();
		}
	}

	// load plugin params info
 	$plugin			= JPluginHelper::getPlugin('search', 'jfcontent');
 	$pluginParams	= new JParameter( $plugin->params );

	$sContent 		= $pluginParams->get( 'search_content', 		1 );
	$sUncategorised = $pluginParams->get( 'search_uncategorised', 	1 );
	$sArchived 		= $pluginParams->get( 'search_archived', 		1 );
	$limit 			= $pluginParams->def( 'search_limit', 		50 );
	$activeLang 	= $pluginParams->def( 'active_language_only', 0);
	
	$nullDate 		= $db->getNullDate();
	$date = JFactory::getDate();
	$now = $date->toMySQL();

	$text = trim( $text );
	if ($text == '') {
		return array();
	}

	$wheres = array();
	switch ($phrase) {
		case 'exact':
			$text		= $db->Quote( '%'.$db->getEscaped( $text, true ).'%', false );
			$where = "LOWER(jfc.value) LIKE ".$text;
			break;

		case 'all':
		case 'any':
		default:
			$words = explode( ' ', $text );
			$wheres = array();
			foreach ($words as $word) {
				$word		= $db->Quote( '%'.$db->getEscaped( $word, true ).'%', false );
				$wheres[] 	= "LOWER(jfc.value) LIKE ".$word;
			}
			$where = '(' . implode( ($phrase == 'all' ? ') AND (' : ') OR ('), $wheres ) . ')';
			break;
	}

	$morder = '';
	switch ($ordering) {
		case 'oldest':
			$order = 'a.created ASC';
			break;

		case 'popular':
			$order = 'a.hits DESC';
			break;

		case 'alpha':
			$order = 'a.title ASC';
			break;

		case 'category':
			$order = 'b.title ASC, a.title ASC';
			$morder = 'a.title ASC';
			break;

		case 'newest':
			default:
			$order = 'a.created DESC';
			break;
	}

	$rows = array();

	// search articles
	if ( $sContent && $limit > 0 )
	{
		// NB can't use concat since Joomfish won't translate sub-values
		$query = 'SELECT a.id as contid, b.id as catid,  u.id AS secid, a.title AS title, a.created AS created,'
		//. ' CONCAT(a.introtext, a.`fulltext`) AS text,'
		. ' a.introtext, a.fulltext, '
		//. ' CONCAT(CONCAT_WS( "/", u.title, b.title ), " - ", jfl.title) AS section,'
		. ' u.title as sectitle, b.title as cattitle,'
		. ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug,'
		. ' CASE WHEN CHAR_LENGTH(b.alias) THEN CONCAT_WS(":", b.id, b.alias) ELSE b.id END as catslug,'
		. ' "2" AS browsernav, '
		. ' jfl.code as jflang, jfl.title as jflname'
		. ' FROM #__content AS a'
		. ' INNER JOIN #__categories AS b ON b.id=a.catid'
		. ' INNER JOIN #__sections AS u ON u.id = a.sectionid'
		. "\n LEFT JOIN #__jf_content as jfc ON reference_id = a.id"
		. "\n LEFT JOIN #__languages as jfl ON jfc.language_id = jfl.lang_id"
		. ' WHERE ( '.$where.' )'
		. ' AND a.state = 1'
		. ' AND u.published = 1'
		. ' AND b.published = 1'
		. ' AND a.access <= '.(int) $user->get( 'aid' )
		. ' AND b.access <= '.(int) $user->get( 'aid' )
		. ' AND u.access <= '.(int) $user->get( 'aid' )
		. ' AND ( a.publish_up = '.$db->Quote($nullDate).' OR a.publish_up <= '.$db->Quote($now).' )'
		. ' AND ( a.publish_down = '.$db->Quote($nullDate).' OR a.publish_down >= '.$db->Quote($now).' )'
		. "\n AND jfc.reference_table = 'content'"
		. ( $activeLang ? "\n AND jfl.code = '$lang'" : '')
		. ' GROUP BY a.id'
		. ' ORDER BY '. $order
		;
		$db->setQuery( $query, 0, $limit );
		$list = $db->loadObjectList();
		$limit -= count($list);

		if(isset($list))
		{
			foreach($list as $key => $item)
			{
				$list[$key]->text = $item->introtext . $item->fulltext ;
				$list[$key]->section = $item->sectitle."/".$item->cattitle." - ".$item->jflname;
				$list[$key]->href = ContentHelperRoute::getArticleRoute($item->slug, $item->catslug, $item->secid);
			}
		}
		$rows[] = $list;
	}

	// search uncategorised content
	if ( $sUncategorised && $limit > 0 )
	{
		$query = 'SELECT a.id as contid, a.title AS title, a.created AS created,'
		. ' a.introtext AS text,'
		. ' "2" as browsernav, "'. $db->Quote(JText::_('Uncategorised Content')) .'" AS section,'
		. ' jfl.code as jflang, jfl.title as jflname'
		. ' FROM #__content AS a'
		. "\n LEFT JOIN #__jf_content as jfc ON reference_id = a.id"
		. "\n LEFT JOIN #__languages as jfl ON jfc.language_id = jfl.lang_id"
		. ' WHERE ('.$where.')'
		. ' AND a.state = 1'
		. ' AND a.access <= '.(int) $user->get( 'aid' )
		. ' AND a.sectionid = 0'
		. ' AND a.catid = 0'
		. ' AND ( a.publish_up = '.$db->Quote($nullDate).' OR a.publish_up <= '.$db->Quote($now).' )'
		. ' AND ( a.publish_down = '.$db->Quote($nullDate).' OR a.publish_down >= '.$db->Quote($now).' )'
		. "\n AND jfc.reference_table = 'content'"
		. ( $activeLang ? "\n AND jfl.code = '$lang'" : '')
		. ' ORDER BY '. ($morder ? $morder : $order)
		;
		$db->setQuery( $query, 0, $limit );
		$list2 = $db->loadObjectList();
		$limit -= count($list2);

		if(isset($list2))
		{
			foreach($list2 as $key => $item)
			{
				$list2[$key]->href = ContentHelperRoute::getArticleRoute($item->contid);
			}
		}

		$rows[] = $list2;
	}

	// search archived content
	if ( $sArchived && $limit > 0 )
	{
		$searchArchived = JText::_( 'Archived' );

		$query = 'SELECT a.title AS title, a.id as conti, b.id as catid,  u.id AS secid,'
		. ' a.created AS created,'
		. ' a.introtext AS text,'
		. ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug,'
		. ' CASE WHEN CHAR_LENGTH(b.alias) THEN CONCAT_WS(":", b.id, b.alias) ELSE b.id END as catslug,'
		. ' u.title as sectitle, b.title as cattitle,'
		. ' u.id AS sectionid,'
		. ' "2" AS browsernav,'
		. ' jfl.code as jflang, jfl.title as jflname'
		. ' FROM #__content AS a'
		. ' INNER JOIN #__categories AS b ON b.id=a.catid AND b.access <= ' .$user->get( 'gid' )
		. ' INNER JOIN #__sections AS u ON u.id = a.sectionid'
		. "\n LEFT JOIN #__jf_content as jfc ON reference_id = a.id"
		. "\n LEFT JOIN #__languages as jfl ON jfc.language_id = jfl.lang_id"
		. ' WHERE ( '.$where.' )'
		. ' AND a.state = -1'
		. ' AND u.published = 1'
		. ' AND b.published = 1'
		. ' AND a.access <= '.(int) $user->get( 'aid' )
		. ' AND b.access <= '.(int) $user->get( 'aid' )
		. ' AND u.access <= '.(int) $user->get( 'aid' )
		. ' AND ( a.publish_up = '.$db->Quote($nullDate).' OR a.publish_up <= '.$db->Quote($now).' )'
		. ' AND ( a.publish_down = '.$db->Quote($nullDate).' OR a.publish_down >= '.$db->Quote($now).' )'
		. "\n AND jfc.reference_table = 'content'"
		. ( $activeLang ? "\n AND jfl.code = '$lang'" : '')
		. ' ORDER BY '. $order
		;
		$db->setQuery( $query, 0, $limit );
		$list3 = $db->loadObjectList();

		if(isset($list3))
		{
			foreach($list3 as $key => $item)
			{
				$list3[$key]->section = $item->sectitle."/".$item->cattitle." - ".$item->jflname;
				$list3[$key]->href = ContentHelperRoute::getArticleRoute($item->slug, $item->catslug, $item->sectionid);
			}
		}

		$rows[] = $list3;
	}

	$results = array();
	if(count($rows))
	{
		foreach($rows as $row)
		{
			$results = array_merge($results, (array) $row);
		}
	}

	return $results;
}
