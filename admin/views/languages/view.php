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
 * $Id: view.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage Views
 *
*/
// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die( 'Restricted access' );

JLoader::import( 'views.default.view',JOOMFISH_ADMINPATH);
jimport( 'joomla.filesystem.file');
jimport( 'joomla.application.component.view');
jimport('joomla.html.pane');

/**
 * HTML View class for the WebLinks component
 *
 * @static
 * @package		Joomla
 * @subpackage	Weblinks
 * @since 1.0
 */
class LanguagesViewLanguages extends JoomfishViewDefault
{
	/**
	 * Control Panel display function
	 *
	 * @param template $tpl
	 */
	public function display($tpl = null)
	{
		global $mainframe;

		$document = JFactory::getDocument();
		$document->setTitle(JText::_('JOOMFISH_TITLE') . ' :: ' .JText::_('Language Title'));

		// Set toolbar items for the page
		JToolBarHelper::title( JText::_( 'Language Title' ), 'language' );
		JToolBarHelper::makeDefault ('languages.setDefault', 'Default', 'Set as frontend default language');
		JToolBarHelper::deleteList('Are you sure you want to delete the selcted items?', 'languages.remove');
		JToolBarHelper::custom( 'languages.save', 'save', 'save', 'Save',false);
		JToolBarHelper::custom( 'languages.apply', 'apply', 'apply', 'Apply',false);
		JToolBarHelper::addNew( 'languages.add' );
		JToolBarHelper::cancel('languages.cancel');
		JToolBarHelper::help( 'screen.languages', true);

		JSubMenuHelper::addEntry(JText::_('Control Panel'), 'index2.php?option=com_joomfish');
		JSubMenuHelper::addEntry(JText::_('Translation'), 'index2.php?option=com_joomfish&amp;task=translate.overview');
		JSubMenuHelper::addEntry(JText::_('Orphans'), 'index2.php?option=com_joomfish&amp;task=translate.orphans');
		JSubMenuHelper::addEntry(JText::_('Manage Translations'), 'index2.php?option=com_joomfish&amp;task=manage.overview', false);
		JSubMenuHelper::addEntry(JText::_('Statistics'), 'index2.php?option=com_joomfish&amp;task=statistics.overview', false);
		JSubMenuHelper::addEntry(JText::_('Language Configuration'), 'index2.php?option=com_joomfish&amp;task=languages.show', true);
		JSubMenuHelper::addEntry(JText::_('Content elements'), 'index2.php?option=com_joomfish&amp;task=elements.show', false);
		JSubMenuHelper::addEntry(JText::_('HELP AND HOWTO'), 'index2.php?option=com_joomfish&amp;task=help.show', false);

		$option				= JRequest::getCmd('option', 'com_joomfish');
		$filter_state		= $mainframe->getUserStateFromRequest( $option.'filter_state',		'filter_state',		'',				'word' );
		$filter_catid		= $mainframe->getUserStateFromRequest( $option.'filter_catid',		'filter_catid',		0,				'int' );
		$filter_order		= $mainframe->getUserStateFromRequest( $option.'filter_order',		'filter_order',		'lext.ordering',	'cmd' );
		$filter_order_Dir	= $mainframe->getUserStateFromRequest( $option.'filter_order_Dir',	'filter_order_Dir',	'',				'word' );
		$search				= $mainframe->getUserStateFromRequest( $option.'search',			'search',			'',				'string' );
		$search				= JString::strtolower( $search );

		$languages	= $this->get('data');
		$defaultLanguage = $this->get('defaultLanguage');

		$this->assignRef('items', $languages);
		$this->assignRef('defaultLanguage', $defaultLanguage);
		
		$jfManager = JoomFishManager::getInstance();
		$this->assignRef('overwriteGlobalConfig', $jfManager->getCfg('overwriteGlobalConfig'));
		$this->assignRef('directory_flags', $jfManager->getCfg('directory_flags'));

		// state filter
		$lists['state']	= JHTML::_('grid.state',  $filter_state );

		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order'] = $filter_order;

		// search filter
		$lists['search']= $search;

		$this->assignRef('user',		JFactory::getUser());
		$this->assignRef('lists',		$lists);

		JHTML::_('behavior.tooltip');
		parent::display($tpl);
	}

	/**
	 * Method displaying the config traslation layout
	 */
	public function translateConfig($tpl = null) {
		$document = JFactory::getDocument();
		$livesite = JURI::base();
		$document->addStyleSheet($livesite.'components/com_joomfish/assets/css/joomfish.css');
		$document->addScript($livesite.'components/com_joomfish/assets/js/joomfish.mootools.js');
		
		//$document->setTitle(JText::_('JOOMFISH_TITLE') . ' :: ' .JText::_('Language Title'));
		$paramsField = JRequest::getVar('paramsField', '');
		$this->assignRef('paramsField',$paramsField);

		parent::display($tpl);
	}

	/**
	 * Method to initialize the language depended image (flag) browser
	 * The browser is initialized with the default root path based on the Joomfish configuration
	 * @param $tpl
	 */
	public function filebrowser($tpl = null){
		$document = JFactory::getDocument();
		$livesite = JURI::base();
		$document->addStyleSheet($livesite.'components/com_joomfish/assets/css/joomfish.css');
		$document->addScript($livesite.'components/com_joomfish/assets/js/joomfish.mootools.js');
		$document->addStyleSheet(JURI::base().'components/com_media/assets/popup-imagelist.css');
		
        $jfManager = JoomFishManager::getInstance();
        $root = $jfManager->getCfg('directory_flags');
        
        $current = JRequest::getVar('current', '');
        if($current != '') {
        	$root = dirname($current);
        }
        
        // remove leading / in case it exists
        $root = preg_replace('/^\/(.*)/', "$1", $root);
        
        $flagField = JRequest::getVar('flagField', '');
        
		$folder = JRequest::getVar( 'folder', $root, 'default', 'path');
		$type = JRequest::getCmd('type', 'image');
		if(JString::trim($folder)=="") {
			$path=JPATH_SITE.DS.JPath::clean('/');
		} else {
			$path=JPATH_SITE.DS.JPath::clean($folder);
		}
		
		JPath::check($path);
		$title = JText::_('Browse language flags');
		$filter = '.jpg|png|gif|xcf|odg|bmp|jpeg';

		if (JFolder::exists($path)){
			$folderList=JFolder::folders($path);
			$filesList=JFolder::files($path, $filter);
		}

		if (!empty($folder)){
			$parent=substr($folder, 0,strrpos($folder,'/'));
		}
		else {
			$parent = '';
		}

		$this->assignRef('folders',$folderList);
		$this->assignRef('files',$filesList);
		$this->assignRef('parent',$parent);
		$this->assignRef('path',$folder);
		$this->assignRef('type',$type);
		$this->assignRef('title',$title);
		$this->assignRef('flagField', $flagField);

		parent::display($tpl);

	}
}
?>
