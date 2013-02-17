<?php
/**
 * Joom!Fish - Multi Lingual extention and translation manager for Joomla!
 * Copyright (C) 2003 - 2013, Think Network GmbH, Munich
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
 * $Id: language.config.php 1251 2009-01-07 06:29:53Z apostolov $
 * @package joomfish
 * @subpackage language.config
 *
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

/**
 * Configutation file to control site language specific config 
 */

$jf_siteconfig = array();

$jf_siteconfig["Site Settings"]=array(
"offline_message" => array('Offline Message','TIPSETYOURSITEISOFFLINE','textarea'),
"sitename" => array('Site Name','TIPSITENAME','text'),);

$jf_siteconfig["Metadata Settings"]=array(
"MetaDesc" =>array('Global Site Meta Description','TIPSITENAME','textarea'),
"MetaKeys" =>array('Global Site Meta Keywords','TIPSITENAME','textarea'),);

$jf_siteconfig["System Settings"]=array(
"helpurl" => array('Help Server','TIPSITENAME','text'),
"offset" => array('Time Zone','TIPDATETIMEDISPLAY','text'),);


$jf_siteconfig["Mail Settings"]=array(
"mailfrom" => array('Mail From','TIPSITENAME','text'),
"fromname" => array('From Name','TIPSITENAME','text'),);
