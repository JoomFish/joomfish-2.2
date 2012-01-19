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

/**
 * HTML View class for the WebLinks component
 *
 * @static
 * @package		Joomla
 * @subpackage	Weblinks
 * @since 1.0
 */
class ElementsViewElements extends JoomfishViewDefault 
{
	function display($tpl = null)
	{
		$document = JFactory::getDocument();
		// browser title
		$document->setTitle(JText::_('JOOMFISH_TITLE') . ' :: ' .JText::_('Content elements'));
		// set page title
		JToolBarHelper::title( JText::_( 'Content elements' ), 'extension' );
		
		$layout = $this->getLayout();
		if (method_exists($this,$layout)){
			$this->$layout($tpl);
		} else {
			$this->overview($tpl);
		}
		parent::display($tpl);
	}

	function overview($tpl = null) {
		// Set toolbar items for the page
		JToolBarHelper::custom("elements.installer","archive","archive", JText::_( 'INSTALL' ),false);
		JToolBarHelper::custom("elements.detail","preview","preivew", JText::_( 'DETAIL' ),true);
		JToolBarHelper::deleteList(JText::_("ARE YOU SURE YOU WANT TO DELETE THIS CE FILE"), "elements.remove");
		JToolBarHelper::custom( 'cpanel.show', 'joomfish', 'joomfish', 'CONTROL PANEL' , false );
		JToolBarHelper::help( 'screen.elements', true);

		JSubMenuHelper::addEntry(JText::_('Control Panel'), 'index2.php?option=com_joomfish');
		JSubMenuHelper::addEntry(JText::_('Translation'), 'index2.php?option=com_joomfish&amp;task=translate.overview');
		JSubMenuHelper::addEntry(JText::_('Orphans'), 'index2.php?option=com_joomfish&amp;task=translate.orphans');
		JSubMenuHelper::addEntry(JText::_('Manage Translations'), 'index2.php?option=com_joomfish&amp;task=manage.overview', false);
		JSubMenuHelper::addEntry(JText::_('Statistics'), 'index2.php?option=com_joomfish&amp;task=statistics.overview', false);
		JSubMenuHelper::addEntry(JText::_('Language Configuration'), 'index2.php?option=com_joomfish&amp;task=languages.show', false);
		JSubMenuHelper::addEntry(JText::_('Content elements'), 'index2.php?option=com_joomfish&amp;task=elements.show', true);
		JSubMenuHelper::addEntry(JText::_('HELP AND HOWTO'), 'index2.php?option=com_joomfish&amp;task=help.show', false);
	}
	
	function edit($tpl = null)
	{
		// Set toolbar items for the page
		JToolBarHelper::back();
		JToolBarHelper::custom( 'cpanel.show', 'joomfish', 'joomfish', 'CONTROL PANEL' , false );
		JToolBarHelper::help( 'screen.elements', true);

		// hide the sub menu
		$this->_hideSubmenu();		
	}	

	function installer($tpl = null)
	{
		// browser title
		$document = JFactory::getDocument();
		$document->setTitle(JText::_('JOOMFISH_TITLE') . ' :: ' .JText::_('CONTENT ELEMENT INSTALLER'));
		
		// set page title
		JToolBarHelper::title( JText::_('JOOMFISH_TITLE') .' :: '. JText::_( 'CONTENT ELEMENT INSTALLER' ), 'fish' );

		// Set toolbar items for the page
		JToolBarHelper::custom( 'elements.show', 'back', 'back', JText::_( 'Back' ), false );
		JToolBarHelper::deleteList(JText::_("ARE YOU SURE YOU WANT TO DELETE THIS CE FILE"), "elements.remove_install");
		JToolBarHelper::custom( 'cpanel.show', 'joomfish', 'joomfish', 'CONTROL PANEL' , false );
		JToolBarHelper::help( 'screen.elements', true);

		// hide the sub menu
		$this->_hideSubmenu();
	}	
}
