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
 * $Id: fckeditor.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage Views
 *
*/
// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die( 'Restricted access' );
?>
<script language="javascript" type="text/javascript">
function copyToClipboard(value, action) {
	try {
		if (document.getElementById) {
			innerHTML="";
			if (action=="copy") {
				srcEl = document.getElementById("original_value_"+value);
				innerHTML = srcEl.innerHTML;
			}
			if ( typeof(FCKeditorAPI)=="object") {
				var oEditor = FCKeditorAPI.GetInstance("refField_"+value) ;
				if ( oEditor.EditMode == FCK_EDITMODE_WYSIWYG )
				{
					// Insert the desired HTML.
					oEditor.InsertHtml(innerHTML) ;
				}
				else	alert( 'Please switch to WYSIWYG mode.' ) ;

			}
			else {
				if (window.clipboardData){
					window.clipboardData.setData("Text",innerHTML);
					alert("<?php echo preg_replace( '/<br />/', '\n', JText::_('CLIPBOARD_COPIED') );?>");
				}
				else {
					srcEl = document.getElementById("text_origText_"+value);
					srcEl.value = innerHTML;
					srcEl.select();
					alert("<?php echo preg_replace( '/<br />/', '\n', JText::_('CLIPBOARD_COPY'));?>");
				}
			}
		}
	}
	catch(e){
		alert("<?php echo preg_replace( '/<br />/', '\n', JText::_('CLIPBOARD_NOSUPPORT'));?>");
	}
}
function translationWriteValue(field, value){
	try {
		if ( typeof(FCKeditorAPI)=="object") {
			var oEditor = FCKeditorAPI.GetInstance("refField_"+field) ;
			if ( oEditor.EditMode == FCK_EDITMODE_WYSIWYG )
			{
				// Insert the desired HTML.
				oEditor.InsertHtml(value) ;
			}
			else	alert( 'Please switch to WYSIWYG mode.' ) ;

		}
		else {
			if (window.clipboardData){
				window.clipboardData.setData("Text",value);
				alert("<?php echo preg_replace( '/<br />/', '\n', JText::_('CLIPBOARD_COPIED') );?>");
			}
			else {
				srcEl = document.getElementById("text_origText_"+field);
				srcEl.value = value;
				srcEl.select();
				alert("<?php echo preg_replace( '/<br />/', '\n', JText::_('CLIPBOARD_COPY'));?>");
			}
		}
	}
	catch(e){
		alert("<?php echo preg_replace( '/<br />/', '\n', JText::_('CLIPBOARD_NOSUPPORT'));?>");
	}
}
</script>
