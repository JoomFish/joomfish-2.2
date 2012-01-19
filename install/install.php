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
 * @version		$Id: install.php 1594 2012-01-20 17:42:33Z akede $
 * @package		joomfish
 * @copyright	2003 - 2012, Think Network GmbH, Munich
 * @license		GNU General Public License
 * 
 * This is the special installer addon created by Andrew Eddie and the team of jXtended.
 * We thank for this cool idea of extending the installation process easily
 * copyright	2005-2008 New Life in IT Pty Ltd.  All rights reserved.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once( JPATH_SITE .DS. 'components' .DS. 'com_joomfish' .DS. 'helpers' .DS. 'defines.php' );
JPlugin::loadLanguage( 'com_joomfish', JPATH_ADMINISTRATOR );

//$nPaths = $this->_paths;
$status = new JObject();
$status->modules = array();
$status->plugins = array();
$status->upgrade = array();


/***********************************************************************************************
* Check for upgrade need and offer upgrade link
***********************************************************************************************/
$query = "SHOW COLUMNS FROM #__languages";
$db->setQuery($query);
$cols = $db->loadResultArray();
if(in_array('iso', $cols)) {
	// We identify the old language table based on specifc fields
	// Now let's set a config value so that we can remember!!!
	if($this->parent->parseSQLFiles($this->manifest->getElementByPath('upgrade/sql'))) {
			$status->upgrade[] = array('table'=>JText::_('JF_UPGRADE_STRUCTURE'),'status'=>'successful');
	} else {
		$status->upgrade[] = array('table'=>JText::_('JF_UPGRADE_STRUCTURE'),'status'=>'failed');
	}
	
} else {
	if(in_array('lang_id', $cols)) {
		// we identified a new language table or 1.6 table
		$query = "show tables";
		$db->setQuery($query);
		$views = $db->loadResultArray();
		if($views!=null && array_search($db->getPrefix() .'jf_languages', $views)) {
			$status->upgrade[] = array('table'=>JText::_('JF_VALID_STRUCTURE'),'status'=>'valid');
		} else {
			$query = "CREATE VIEW `#__jf_languages` AS select `l`.`lang_id` AS `lang_id`,`l`.`lang_code` AS `lang_code`,`l`.`title` AS `title`,`l`.`title_native` AS `title_native`,`l`.`sef` AS `sef`,`l`.`description` AS `description`,`l`.`published` AS `published`,`l`.`image` AS `image`,`lext`.`image_ext` AS `image_ext`,`lext`.`fallback_code` AS `fallback_code`,`lext`.`params` AS `params`,`lext`.`ordering` AS `ordering` from `#__languages` `l` left outer join `#__jf_languages_ext` `lext` on `l`.`lang_id` = `lext`.`lang_id` order by `lext`.`ordering`;";
			$db->setQuery($query);
			if(!$db->query()) {
				$status->upgrade[] = array('table'=>JText::_('JF_VALID_STRUCTURE'),'status'=>'failed');
				$this->parent->abort(JText::_('Component').' '.JText::_('Install').': '.JText::_('Database integration failed'));
				return false;
			} else {
				$status->upgrade[] = array('table'=>JText::_('JF_VALID_STRUCTURE'),'status'=>'upgraded');
			}
		}
		
	}
}

$sql = "show index from #__jf_content";// where key_name = 'jfContent'";
$db->setQuery($sql);
$data = $db->loadObjectList("Key_name");
if (!isset($data['jfContent'])){
	$sql = "ALTER TABLE `#__jf_content` ADD INDEX `combo` ( `reference_id` , `reference_field` , `reference_table` )" ;
	$db->setQuery($sql);
	$db->query();
	
	$sql = "ALTER TABLE `#__jf_content` ADD INDEX `jfContent` ( `language_id` , `reference_id` , `reference_table` )" ;
	$db->setQuery($sql);
	$db->query();

	$sql = "ALTER TABLE `#__jf_content` ADD INDEX `jfContentLanguage` (`reference_id`, `reference_field`, `reference_table`, `language_id`)" ;
	$db->setQuery($sql);
	$db->query();
	
}

/***********************************************************************************************
* MODULE INSTALLATION SECTION
***********************************************************************************************/

$modules = $this->manifest->getElementByPath('modules');
if (is_a($modules, 'JSimpleXMLElement') && count($modules->children())) {

	foreach ($modules->children() as $module)
	{
		$mname		= $module->attributes('module');
		$mclient	= JApplicationHelper::getClientInfo($module->attributes('client'), true);

		// Set the installation path
		if (!empty ($mname)) {
			$this->parent->setPath('extension_root', $mclient->path.DS.'modules'.DS.$mname);
		} else {
			$this->parent->abort(JText::_('Module').' '.JText::_('Install').': '.JText::_('No module file specified'));
			return false;
		}

		/*
		* If the module directory already exists, then we will assume that the
		* module is already installed or another module is using that directory.
		*/
		if (file_exists($this->parent->getPath('extension_root'))&&!$this->parent->getOverwrite()) {
			$this->parent->abort(JText::_('Module').' '.JText::_('Install').': '.JText::_('Another module is already using directory').': "'.$this->parent->getPath('extension_root').'"');
			return false;
		}

		// If the module directory does not exist, lets create it
		$created = false;
		if (!file_exists($this->parent->getPath('extension_root'))) {
			if (!$created = JFolder::create($this->parent->getPath('extension_root'))) {
				$this->parent->abort(JText::_('Module').' '.JText::_('Install').': '.JText::_('Failed to create directory').': "'.$this->parent->getPath('extension_root').'"');
				return false;
			}
		}

		/*
		* Since we created the module directory and will want to remove it if
		* we have to roll back the installation, lets add it to the
		* installation step stack
		*/
		if ($created) {
			$this->parent->pushStep(array ('type' => 'folder', 'path' => $this->parent->getPath('extension_root')));
		}

		// Copy all necessary files
		$element = $module->getElementByPath('files');
		if ($this->parent->parseFiles($element, -1) === false) {
			// Install failed, roll back changes
			$this->parent->abort();
			return false;
		}

		// Copy language files
		$element = $module->getElementByPath('languages');
		if ($this->parent->parseLanguages($element, $mclient->id) === false) {
			// Install failed, roll back changes
			$this->parent->abort();
			return false;
		}

		// Copy media files
		$element = $module->getElementByPath('media');
		if ($this->parent->parseMedia($element, $mclient->id) === false) {
			// Install failed, roll back changes
			$this->parent->abort();
			return false;
		}

		$mtitle		= $module->attributes('title');
		$mposition	= $module->attributes('position');
		$morder		= $module->attributes('order');

		if ($mtitle && $mposition) {
			// if module already installed do not create a new instance
			$db = JFactory::getDBO();
			$query = 'SELECT `id` FROM `#__modules` WHERE module = '.$db->Quote( $mname);
			$db->setQuery($query);
			if (!$db->Query()) {
				// Install failed, roll back changes
				$this->parent->abort(JText::_('Module').' '.JText::_('Install').': '.$db->stderr(true));
				return false;
			}
			$id = $db->loadResult();

			if (!$id){
				$row =  JTable::getInstance('module');
				$row->title		= $mtitle;
				$row->ordering	= $morder;
				$row->position	= $mposition;
				$row->showtitle	= 0;
				$row->iscore	= 0;
				$row->access	= ($mclient->id) == 1 ? 2 : 0;
				$row->client_id	= $mclient->id;
				$row->module	= $mname;
				$row->published	= 1;
				$row->params	= '';

				if (!$row->store()) {
					// Install failed, roll back changes
					$this->parent->abort(JText::_('Module').' '.JText::_('Install').': '.$db->stderr(true));
					return false;
				}
				
				// Make visible evertywhere if site module
				if ($mclient->id==0){
					$query = 'REPLACE INTO `#__modules_menu` (moduleid,menuid) values ('.$db->Quote( $row->id).',0)';
					$db->setQuery($query);
					if (!$db->query()) {
						// Install failed, roll back changes
						$this->parent->abort(JText::_('Module').' '.JText::_('Install').': '.$db->stderr(true));
						return false;
					}
				}


			}


		}

		$status->modules[] = array('name'=>$mname,'client'=>$mclient->name);
	}
}


/***********************************************************************************************
* PLUGIN INSTALLATION SECTION
***********************************************************************************************/

$plugins = $this->manifest->getElementByPath('plugins');
if (is_a($plugins, 'JSimpleXMLElement') && count($plugins->children())) {

	foreach ($plugins->children() as $plugin)
	{
		$pname		= $plugin->attributes('plugin');
		$pgroup		= $plugin->attributes('group');
		$porder		= $plugin->attributes('order');

		// Set the installation path
		if (!empty($pname) && !empty($pgroup)) {
			$this->parent->setPath('extension_root', JPATH_ROOT.DS.'plugins'.DS.$pgroup);
		} else {
			$this->parent->abort(JText::_('Plugin').' '.JText::_('Install').': '.JText::_('No plugin file specified'));
			return false;
		}

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Filesystem Processing Section
		 * ---------------------------------------------------------------------------------------------
		 */

		// If the plugin directory does not exist, lets create it
		$created = false;
		if (!file_exists($this->parent->getPath('extension_root'))) {
			if (!$created = JFolder::create($this->parent->getPath('extension_root'))) {
				$this->parent->abort(JText::_('Plugin').' '.JText::_('Install').': '.JText::_('Failed to create directory').': "'.$this->parent->getPath('extension_root').'"');
				return false;
			}
		}

		/*
		* If we created the plugin directory and will want to remove it if we
		* have to roll back the installation, lets add it to the installation
		* step stack
		*/
		if ($created) {
			$this->parent->pushStep(array ('type' => 'folder', 'path' => $this->parent->getPath('extension_root')));
		}

		// Copy all necessary files
		$element = $plugin->getElementByPath('files');
		if ($this->parent->parseFiles($element, -1) === false) {
			// Install failed, roll back changes
			$this->parent->abort();
			return false;
		}

		// Copy all necessary files
		$element = $plugin->getElementByPath('languages');
		if ($this->parent->parseLanguages($element, 1) === false) {
			// Install failed, roll back changes
			$this->parent->abort();
			return false;
		}

		// Copy media files
		$element = $plugin->getElementByPath('media');
		if ($this->parent->parseMedia($element, 1) === false) {
			// Install failed, roll back changes
			$this->parent->abort();
			return false;
		}

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Database Processing Section
		 * ---------------------------------------------------------------------------------------------
		 */
		$db = JFactory::getDBO();

		// Check to see if a plugin by the same name is already installed
		$query = 'SELECT `id`' .
		' FROM `#__plugins`' .
		' WHERE folder = '.$db->Quote($pgroup) .
		' AND element = '.$db->Quote($pname);
		$db->setQuery($query);
		if (!$db->Query()) {
			// Install failed, roll back changes
			$this->parent->abort(JText::_('Plugin').' '.JText::_('Install').': '.$db->stderr(true));
			return false;
		}
		$id = $db->loadResult();

		// Was there a plugin already installed with the same name?
		if ($id) {

			if (!$this->parent->getOverwrite())
			{
				// Install failed, roll back changes
				$this->parent->abort(JText::_('Plugin').' '.JText::_('Install').': '.JText::_('Plugin').' "'.$pname.'" '.JText::_('already exists!'));
				return false;
			}

		} else {
			$row = JTable::getInstance('plugin');
			$row->name = JText::_(ucfirst($pgroup)).' - '.JText::_(ucfirst($pname));
			$row->ordering = $porder;
			$row->folder = $pgroup;
			$row->iscore = 0;
			$row->access = 0;
			$row->client_id = 0;
			$row->element = $pname;
			$row->published = 1;
			$row->params = '';

			if (!$row->store()) {
				// Install failed, roll back changes
				$this->parent->abort(JText::_('Plugin').' '.JText::_('Install').': '.$db->stderr(true));
				return false;
			}
		}

		$status->plugins[] = array('name'=>$pname,'group'=>$pgroup);
	}
}

/***********************************************************************************************
* SETUP DEFAULTS
***********************************************************************************************/
// Check to see if a plugin by the same name is already installed
$query = 'SELECT `id`' .
' FROM `#__components`' .
' WHERE parent = 0 and name=' .$db->Quote('Joom!Fish').
' AND parent = 0';
$db->setQuery($query);
$componentID = $db->loadResult();

if(!is_null($componentID) && $componentID > 0) {
	$query = 'UPDATE #__components SET params = '
		. $db->Quote("noTranslation=0\n"
		. "defaultText=\n"
		. "overwriteGlobalConfig=1\n"
		. "directory_flags=/media/com_joomfish/default\n"
		. "storageOfOriginal=md5\n"
		. "frontEndPublish=1\n"
		. "frontEndPreview=1\n"
		. "showDefaultLanguageAdmin=0\n"
		. "copyparams=1\n"
		. "transcaching=0\n"
		. "cachelife=180\n"
		. "qacaching=1\n"
		. "qalogging=0\n"
		. "usersplash=1\n")
		. 'WHERE id = ' . $componentID;
	$db->setQuery($query);
		
	if (!$db->Query()) {
		// Install failed, roll back changes
		$this->parent->abort(JText::_('Plugin').' '.JText::_('Install').': '.$db->stderr(true));
		return false;
	}
}

/**
 * ---------------------------------------------------------------------------------------------
 * Verify installed languages in the system and update language table
 * ---------------------------------------------------------------------------------------------
 */

$query = "SELECT * FROM #__languages";
$db->setQuery($query);
$cols = $db->loadResultArray();
if(!is_null($cols) && count($cols) == 0) {
	// No languages installed at all - let's use all exisiting frontend languages as default
	// Read the languages dir to find new installed languages
	// This method returns a list of JLanguage objects with the related inforamtion
	$systemLanguages = JLanguage::getKnownLanguages(JPATH_SITE);
	$lang= JFactory::getLanguage();
	
	foreach ($systemLanguages as $jLanguage) {
		jimport('joomla.database.table');
		JLoader::register('TableJFLanguage', JOOMFISH_ADMINPATH .DS. 'tables' .DS. 'JFLanguage.php' );
		$jfLanguage = & JTable::getInstance('JFLanguage', 'Table' );
		$jfLanguage->bindFromJLanguage($jLanguage);
		if($jfLanguage->get('lang_code') == $lang->getTag()) {
			$jfLanguage->set('published', true);
		}
		if(!$jfLanguage->store()) {
			$status->upgrade[] = array('table'=>JText::_('JF_FAILED_STORE_NEW_LANGUAGES'),'status'=>'failed');
		}
	}
}


/***********************************************************************************************
* OUTPUT TO SCREEN
***********************************************************************************************/
$rows = 0;
?>
<img src="components/com_joomfish/assets/images/joomfish_slogan.png" width="138" height="50" alt="Joom!Fish Multilingual Content Manager" align="right" />

<h2>Joom!Fish Installation</h2>
<table class="adminlist">
	<thead>
		<tr>
			<th class="title" colspan="2"><?php echo JText::_('Extension'); ?></th>
			<th><?php echo JText::_('Status'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="3"></td>
		</tr>
	</tfoot>
	<tbody>
		<tr class="row0">
			<td class="key" colspan="2"><?php echo 'Joom!Fish '.JText::_('Component'); ?></td>
			<td><strong><?php echo JText::_('Installed'); ?></strong></td>
		</tr>
<?php if(count($status->upgrade)) :
	$upgsuccess = true;
	foreach ($status->upgrade as $upgrade) :
		$upgsuccess &= $upgrade['status']=='failed' ? false : true;
	?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key" colspan="2"><?php echo $upgrade['table']; ?></td>
			<td><strong><?php echo JText::_($upgrade['status']); ?></strong></td>
		</tr>
<?php endforeach;
	if(!$upgsuccess) {
		$mainframe = &JFactory::getApplication();
		$mainframe->enqueueMessage(JText::_('JF_UPGRADE_WARNING'), 'error');
	}
endif; ?>
<?php if (count($status->modules)) : ?>
		<tr>
			<th><?php echo JText::_('Module'); ?></th>
			<th><?php echo JText::_('Client'); ?></th>
			<th></th>
		</tr>
	<?php foreach ($status->modules as $module) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo $module['name']; ?></td>
			<td class="key"><?php echo ucfirst($module['client']); ?></td>
			<td><strong><?php echo JText::_('Installed'); ?></strong></td>
		</tr>
	<?php endforeach;
	endif;
if (count($status->plugins)) : ?>
		<tr>
			<th><?php echo JText::_('Plugin'); ?></th>
			<th><?php echo JText::_('Group'); ?></th>
			<th></th>
		</tr>
	<?php foreach ($status->plugins as $plugin) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo ucfirst($plugin['name']); ?></td>
			<td class="key"><?php echo ucfirst($plugin['group']); ?></td>
			<td><strong><?php echo JText::_('Installed'); ?></strong></td>
		</tr>
	<?php endforeach;
endif; ?>
	</tbody>
</table>
