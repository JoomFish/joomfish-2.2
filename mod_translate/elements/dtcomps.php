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
 * $Id: dtcomps.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage mod_translate
 *
*/

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die( 'Restricted access' );

class JElementDtcomps extends JElement
{

	function fetchElement($name, $value, &$node, $control_name)
	{

		if (!is_array($value)){
			$value = array();
			$value[]="com_content#content#cid#task#!edit";
			$value[]="com_frontpage#content#cid#task#!edit";
			$value[]="com_sections#sections#cid#task#!edit";
			$value[]="com_categories#categories#cid#task#!edit";
			$value[]="com_contact#contact_details#cid#!edit";
			$value[]="com_menus#menu#cid#task#!edit";
			$value[]="com_modules#modules#cid#task#!edit#client#!1";
			$value[]="com_newsfeeds#newsfeeds#cid#task#!edit";
			$value[]="com_poll#polls#cid#task#!edit";
		}
		$html ="";
		for ($i=0;$i<count($value)+10;$i++) {
			$val = "";
			if ($i<count($value)){
				$val = $value[$i];
				if ($val=="") continue;
			}
			$html .= "<div>";
			$html .= "<input type='text' size='50' maxsize='100'  name='".$control_name.'['.$name.'][]'."' value='".$val."' />";
			$html .= "</div>";
		}

		return $html;

	}
}
