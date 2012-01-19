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
 * $Id: default_jfnews.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage Views
 *
*/
defined('_JEXEC') or die('Restricted access');

$output = '';
 //$panelStates = $this->get('panelStates');
$sysInfo =  $this->panelStates['system'];
// this already includes administrator
$live_site = JURI::base();
?>
<div style="padding: 5px;">
	<?php echo JText::_('INFORMATION_TEXT');?>
	<?php echo JText::_('INFORMATION_CLUB_TITLE');?>
	<a class="jfclublogo" href="<?php echo JText::_('INFORMATION_CLUB_LINK');?>" target="_blank"><img src="<?php echo JURI::root();?>/administrator/components/com_joomfish/assets/images/joomfishclub.png" border="0" alt="join the Joom!Fish Club" /></a>

	<?php echo JText::sprintf('INFORMATION_CLUB', $sysInfo['translations'], sprintf('%01.2f', intval($sysInfo['translations']) *0.10));?>

	<?php echo JText::_('INFORMATION_SERVICE_TITLE');?>
	<?php echo JText::_('INFORMATION_SERVICE');?>
</div>