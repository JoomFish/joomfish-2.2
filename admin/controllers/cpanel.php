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
 * $Id: cpanel.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage cpanel
 *
*/


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 * The JoomFish Tasker manages the general tasks within the Joom!Fish admin interface
 *
 */
class CpanelController extends JController  {
	/**
	 * Joom!Fish Controler for the Control Panel
	 * @param array		configuration
	 * @return joomfishTasker
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask( 'show',  'display' );

		// ensure DB cache table is created and up to date
		JLoader::import( 'helpers.controllerHelper',JOOMFISH_ADMINPATH);
		JLoader::import( 'classes.JCacheStorageJFDB',JOOMFISH_ADMINPATH);
		JoomfishControllerHelper::checkDBCacheStructure();
	}

	/**
	 * Standard display control structure
	 * 
	 */
	public function display($msg=null)
	{
		$this->view =  $this->getView('cpanel');
		parent::display();
	}
	
	public function cancel()
	{
		$this->setRedirect( 'index.php?option=com_joomfish' );
	}
	
	public function usersplash() {
		$this->view = $this->getView('cpanel');
		$viewLayout	= JRequest::getCmd( 'layout', 'usersplash' );
		$this->view->setLayout($viewLayout);
		parent::display();	
	}
	
	/** This special task allows to save general config parameters outside of the
	 * standard configuration systme. It is used for example in the splash screen
	 * to change the setting for automatic presentation of the screen or not
	 * @return void
	 */
	public function saveconfig() {
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );
		
		$post = JRequest::get('post');
		$msg = '';
		$config = JComponentHelper::getParams( 'com_joomfish' );
		if($post != null && array_key_exists('params', $post)) {
			$params = $post['params']; 
			if($params != null && count($params)>0) {
				
				foreach ($params as $key => $value) {
					$config->setValue($key, $value);
				}
				
				$post['params'] = $config->toArray();
				
				$table =& JTable::getInstance('component');
				if (!$table->loadByOption( 'com_joomfish' ))
				{
					JError::raiseWarning( 500, 'Not a valid component' );
					return false;
				}
		
				$post['option'] = 'com_joomfish';
				$table->bind( $post );
		
				// pre-save checks
				if (!$table->check()) {
					JError::raiseWarning( 500, $table->getError() );
					return false;
				}
		
				// save the changes
				if (!$table->store()) {
					JError::raiseWarning( 500, $table->getError() );
					return false;
				}
				
				$msg = JText::_('The Configuration Details have been updated');
			}
		}
	}
}

?>
