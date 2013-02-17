/**
 * Joom!Fish - Multi Lingual extention and translation manager for Joomla!
 * Copyright (C) 2003 - 2013, Think Network GmbH, Munich
 * Based on some ideas of Copyright (c) 2006 - 2011 JoomlaWorks, a business unit of Nuevvo Webware Ltd. All rights reserved.

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
 * $Id: view.php 1466 2010-04-12 04:18:59Z akede $
 * @package joomfish
 * @subpackage js
 *
*/
window.addEvent('domready', function(){

	// Toggler
	if($('jfToggleSidebar')){	
		$('jfToggleSidebar').addEvent('click', function(){
			$('adminJFSidebar').setStyle('display', $('adminJFSidebar').getStyle('display') != 'none' ? 'none' : '')
		});
	}
	
	// File browser
	$$('.flagFile').addEvent('click', function(e){
		e = new Event(e).stop();
		var flagFieldId=this.getProperty('id');
		var parts = flagFieldId.split('-');
		var flagField = parts[1];
		
		parent.$$('img[id=flagImage'+flagField+']').setProperty('src', this.getProperty('href'));
		parent.$$('input[id=flagValue'+flagField+']').setProperty('value', this.getProperty('href'));
		parent.$('sbox-window').close();
	});
	
	//
	// Save configuration to params
	// Reorganize the translations into the format <key>=<value> and write them back to the params field
	//
	$$('button[id=saveConfigTranslation]').addEvent('click', function(e){
		e = new Event(e).stop();
		
		var paramsField=window.$('paramsfield');
		var paramsFieldID=paramsField.getProperty('value');
		
		var paramValues=new Array();
		window.$$('.translation').each(function(translationField,i) {
			var paramValue = translationField.getProperty('value');
			var paramKey = translationField.getProperty('name');
			var parts = paramKey.split('-');
			paramKey = parts[1];
			
			paramValues[i] = paramKey+'='+paramValue;
		})
		
		var param = paramValues.join('\n');
		parent.$$('input[id='+paramsFieldID+']').setProperty('value', param);
		parent.$('sbox-window').close();
	});
	
	//
	// Flip the user splash video and start it
	//
	// Toggler
	if($('jfsplashwelcome')){	
		$('btn-startvideo').addEvent('click', function(){
			$('jfsplashwelcome').setStyle('display', 'none');
			$('jfsplashvideo').setStyle('display', 'block');
		});
	}
	
	// Close Button of splash screen
	$$('input#splash-btn-close').addEvent('click', function(e){
		e = new Event(e).stop();
		parent.$('sbox-window').close();
	});
	
	// Change the usersplash state in current form
	$$('input#splash-usersplashstate').addEvent('click', function(e){
		var stateField = $('usersplashstate');
		stateField.value = this.checked ? this.value : "0" ;
	});
});
