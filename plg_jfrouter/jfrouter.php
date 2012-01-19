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
 * $Id: jfrouter.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage jfrouter
 * @version 2.0
 *
*/

/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Restricted access' );

// In PHP5 this should be a instance_of check
// Currently Joom!Fish does not need to be active in Administrator
// This might be an extended version
if($mainframe->isAdmin()) {
	return;
}
jimport('joomla.filesystem.file');
// Joom!Fish router only gets activated if essential files are missing
//if ( !file_exists( JPATH_PLUGINS .DS. 'system' .DS. 'jfdatabase' .DS. 'jfdatabase.class.php' )) {
if ( !JFile::exists( JPATH_PLUGINS .DS. 'system' .DS. 'jfrouter' .DS. 'contact.php' )) {
	JError::raiseNotice('no_jf_plugin', JText::_('Joom!Fish router plugin not installed correctly. Plugin not executed') .' (jfr-contact)');
	return;
}
if(JFile::exists(JPATH_SITE .DS. 'components' .DS. 'com_joomfish' .DS. 'helpers' .DS. 'defines.php')) {
	require_once( JPATH_SITE .DS. 'components' .DS. 'com_joomfish' .DS. 'helpers' .DS. 'defines.php' );
	JLoader::register('JoomfishManager', JOOMFISH_ADMINPATH .DS. 'classes' .DS. 'JoomfishManager.class.php' );
	JLoader::register('JoomFishVersion', JOOMFISH_ADMINPATH .DS. 'version.php' );
	JLoader::register('JoomFish', JOOMFISH_PATH .DS. 'helpers' .DS. 'joomfish.class.php' );	
} else {
	JError::raiseNotice('no_jf_extension', JText::_('Joom!Fish extension not installed correctly. Plugin not executed') .' (jfr-defines)');
	return;
}
/**
* Language Determination and basic routing for Joomfish
*/
class plgSystemJFRouter extends JPlugin{

	/**
	 * stored configuration from plugin
	 *
	 * @var object configuration information
	 */
	var $_config = null;

	function plgSystemJFRouter(& $subject, $config)
	{
		global $mainframe;
		if ($mainframe->isAdmin()) {
			// This plugin is only relevant for use within the frontend!
			return;
		}
		parent::__construct($subject, $config);

		// put params in registry so I have easy access to them later
		$conf = JFactory::getConfig();
		$conf->setValue("jfrouter.params",$this->params);

		// Must do this here in case other plugins instantiate language!
		// Get the router
		$app	= JFactory::getApplication();
		$router = $app->getRouter();

		// atttach build rules for language
		$router->attachBuildRule("routeJFRule");

		// This gets the language from the router before any other part of Joomla can load the language !!
		$uri = JURI::getInstance();
		$this->parseJFRule($router, $uri);
	}

	function _discoverJFLanguage ( ) {

		static $discovered;
		if (isset($discovered) && $discovered){
			return;
		}
		$discovered=true;

		$registry = JFactory::getConfig();

		// Find language without loading strings		
		$locale	= $registry->getValue('config.language');
		
		// Attention - we need to access the site default values
		// #12943 explains that a user might overwrite the orignial settings based on his own profile
		$langparams = JComponentHelper::getParams('com_languages');
		$defLanguage = $langparams->get("site");
		$registry->setValue("config.defaultlang", (isset($defLanguage) && $defLanguage!='') ? $defLanguage : $locale);

		// get params from registry in case function called statically
		$params = $registry->getValue("jfrouter.params");

		$determitLanguage 		= $params->get( 'determitLanguage', 1 );
		$newVisitorAction		= $params->get( 'newVisitorAction', 'browser' );
		$use302redirect			= $params->get( 'use302redirect', 0 );
		$enableCookie			= $params->get( 'enableCookie', 1 );

		// get instance of JoomFishManager to obtain active language list and config values
		$jfm =  JoomFishManager::getInstance();
	
		$client_lang = '';
		$lang_known = false;
		$jfcookie = JRequest::getVar('jfcookie', null ,"COOKIE");
		if (isset($jfcookie["lang"]) && $jfcookie["lang"] != "") {
			$client_lang = $jfcookie["lang"];
			$lang_known = true;
		}

		$uri = JURI::getInstance();
		if ($requestlang = JRequest::getVar('lang', null ,"REQUEST")){
			if( $requestlang != '' ) {
				$client_lang = $requestlang;
				$lang_known = true;
			}
		}

		// no language choosen - Test plugin e.g. IP lookup tool
		if ( !$lang_known)	{
			// setup Joomfish pluginds
			$dispatcher	   = JDispatcher::getInstance();
			$iplang="";
			JPluginHelper::importPlugin('joomfish');
			$dispatcher->trigger('onDiscoverLanguage', array (& $iplang));
			if ($iplang!=""){
				$client_lang = $iplang;
				$lang_known = true;
			}
		}
		if ( !$lang_known && $determitLanguage &&
		key_exists( 'HTTP_ACCEPT_LANGUAGE', $_SERVER ) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {

			switch ($newVisitorAction) {
				// usesing the first defined Joom!Fish language
				case 'joomfish':
					$activeLanguages = $jfm->getActiveLanguages();
					reset($activeLanguages);
					$first = key($activeLanguages);
					$client_lang = $activeLanguages[$first]->getLanguageCode();
					break;

				case 'site':
					// We accept that this default locale might be overwritten by user settings!
					$jfLang = TableJFLanguage::createByJoomla( $locale );
					$client_lang = $jfLang->getLanguageCode();
					break;

					// no language chooses - assume from browser configuration
				case 'browser':
				default:
					// language negotiation by Kochin Chang, June 16, 2004
					// retrieve active languages from database
					$active_iso = array();
					$active_isocountry = array();
					$active_code = array();
					$activeLanguages = $jfm->getActiveLanguages();
					if( count( $activeLanguages ) == 0 ) {
						return;
					}

					foreach ($activeLanguages as $alang) {
						$active_iso[] = $alang->iso;
						if( preg_match('/[_-]/i', $alang->iso) ) {
							$isocountry = preg_split('[_-]',$alang->iso);
							$active_isocountry[] = $isocountry[0];
						}
						$active_code[] = $alang->shortcode;
					}

					// figure out which language to use - browser languages are based on ISO codes
					$browserLang = explode(',', $_SERVER["HTTP_ACCEPT_LANGUAGE"]);

					foreach( $browserLang as $blang ) {
						if( in_array($blang, $active_iso) ) {
							$client_lang = $blang;
							break;
						}
						$shortLang = substr( $blang, 0, 2 );
						if( in_array($shortLang, $active_isocountry) ) {
							$client_lang = $shortLang;
							break;
						}

						// compare with code
						if ( in_array($shortLang, $active_code) ) {
							$client_lang = $shortLang;
							break;
						}
					}
					break;
			}
		}

		// get the name of the language file for joomla
		$jfLang = TableJFLanguage::createByShortcode($client_lang, false);
		if( $jfLang === null && $client_lang!="") {
			$jfLang = TableJFLanguage::createByISO( $client_lang, false );
		}
		else if( $jfLang === null) {
			$jfLang = TableJFLanguage::createByJoomla( $locale );
		}

		if( !$lang_known && $use302redirect ) {
			// using a 302 redirect means that we do not change the language on the fly the first time, but with a clean reload of the page

			$href= "index.php";
			$hrefVars = '';
			$queryString = JRequest::getVar('QUERY_STRING', null ,"SERVER");
			if( !empty($queryString) ) {
				$vars = explode( "&", $queryString );
				if( count($vars) > 0 && $queryString) {
					foreach ($vars as $var) {
						if( preg('/=/i', $var ) ) {
							list($key, $value) = explode( "=", $var);
							if( $key != "lang" ) {
								if( $hrefVars != "" ) {
									$hrefVars .= "&amp;";
								}
								// ignore mosmsg to ensure it is visible in frontend
								if( $key != 'mosmsg' ) {
									$hrefVars .= "$key=$value";
								}
							}
						}
					}
				}
			}

			// Add the existing variables
			if( $hrefVars != "" ) {
				$href .= '?' .$hrefVars;
			}

			if( $jfLang->getLanguageCode() != null ) {
				$ulang = 'lang=' .$jfLang->getLanguageCode();
			} else {
				// it's important that we add at least the basic parameter - as of the sef is adding the actual otherwise!
				$ulang = 'lang=';
			}

			// if there are other vars we need to add a & otherwiese ?
			if( $hrefVars == '' ) {
				$href .= '?' . $ulang;
			} else {
				$href .= '&amp;' . $ulang;
			}

			$registry->setValue("config.multilingual_support", true);
			global $mainframe;

			$mainframe->setUserState('application.lang',$jfLang->code);
			$registry->setValue("config.jflang", $jfLang->code);
			$registry->setValue("config.lang_site",$jfLang->code);
			$registry->setValue("config.language",$jfLang->code);
			$registry->setValue("joomfish.language",$jfLang);

			$href = JRoute::_($href,false);
			
			header( 'HTTP/1.1 303 See Other' );
			header( "Location: ". $href );
			exit();
		}

		if( isset($jfLang) && $jfLang->code != "" && ($jfLang->active  || $jfm->getCfg("frontEndPreview") )) {
			$locale = $jfLang->code;
		} else {
			$jfLang = TableJFLanguage::createByJoomla( $locale );
			
			if( !$jfLang->active ) {
			?>
			<div style="background-color: #c00; color: #fff">
				<p style="font-size: 1.5em; font-weight: bold; padding: 10px 0px 10px 0px; text-align: center; font-family: Arial, Helvetica, sans-serif;">
				Joom!Fish config error: Default language is inactive!<br />&nbsp;<br />
				Please check configuration, try to use first active language</p>
			</div>
			<?php
				$activeLanguages = $jfm->getActiveLanguages();
				if( count($activeLanguages) > 0 ) {
					$jfLang = $activeLanguages[0];
					$locale = $jfLang->code;
				}
				else {
					// No active language defined - using system default is only alternative!
				}
			}
			$client_lang = ($jfLang->shortcode!='') ? $jfLang->shortcode : $jfLang->iso;
		}

		// TODO set the cookie domain so that it works for all subdomains
		if ($enableCookie){
			setcookie( "lang", "", time() - 1800, "/" );
			setcookie( "jfcookie", "", time() - 1800, "/" );
			setcookie( "jfcookie[lang]", $client_lang, time()+24*3600, '/' );
		}
		
		if( defined("_JLEGACY") ) {
			$GLOBALS['iso_client_lang'] = $client_lang;
			$GLOBALS['mosConfig_lang'] = $jfLang->code;
		}

		$registry->setValue("config.multilingual_support", true);

		global $mainframe;

		$mainframe->setUserState('application.lang',$jfLang->code);
		$registry->setValue("config.jflang", $jfLang->code);
		$registry->setValue("config.lang_site",$jfLang->code);
		$registry->setValue("config.language",$jfLang->code);
		$registry->setValue("joomfish.language",$jfLang);

		// Force factory static instance to be updated if necessary
		$lang = & JFactory::getLanguage();
		if ($jfLang->code != $lang->getTag()){
			// Must not assign by reference in order to overwrite the existing reference to the static instance of the language
			$lang = JFactory::_createLanguage();
			$lang->setDefault($jfLang->code);
			$lang->_metadata = array();
			$lang->_metadata['tag'] = $jfLang->code;
			$lang->_metadata['rtl'] = false;
		}
		// no need to set locale for this ISO code its done by JLanguage
		
		// overwrite with the valued from $jfLang
		$jfparams = JComponentHelper::getParams("com_joomfish");
		$overwriteGlobalConfig =  $jfparams->get( 'overwriteGlobalConfig', 0 );
		if($overwriteGlobalConfig ) {
			// We should overwrite additional global variables based on the language parameter configuration
			$params = new JParameter($jfLang->params);
			$paramarray = $params->toArray();
			foreach ($paramarray as $key=>$val) {
				$registry->setValue("config.".$key,$val);
	
				if (defined("_JLEGACY")){
					$name = 'mosConfig_'.$key;
					$GLOBALS[$name] = $val;
				}
			}
		}
	}

	/**
	 * Custom handlers to deal with bad component routers e.g. for contact
	 */ 
	function procesCustomBuildRule($router, &$uri){
		$option = $uri->getVar("option","");
		if (strpos($option,"com_")!==0) return;
		$option = substr($option,4);
		$customFile = dirname(__FILE__).DS."jfrouter".DS.$option.".php";
		if (file_exists($customFile)){
			include_once($customFile);
			if (function_exists("JFRouterHelper".ucfirst($option))){
				$function = "JFRouterHelper".ucfirst($option);
				$function ($router, $uri);
			}
		}
	}
	
	function parseJFRule($router, &$uri){
		//echo "got here too lang = ".$uri->getVar("lang","")."<br/>";
		$route = $uri->getPath();

		$conf = JFactory::getConfig();
		$params = $conf->getValue("jfrouter.params");

		$sefordomain = $params->get("sefordomain","sefprefix");

		if ($sefordomain == "domain"){
			$host = $uri->getHost();
			// TODO cache the indexed array
			$rawsubdomains = $params->getValue("sefsubdomain",array());
			$subdomains = array();
			$jfm =  JoomFishManager::getInstance();
			$langs = $jfm->getLanguagesIndexedById();
			foreach ($rawsubdomains as $domain) {
				list($langid,$domain) = explode("::",$domain,2);
				// if you have inactive languages and are not logged in then skip inactive language
				if (!array_key_exists($langid, $langs)) continue;
				$domain = strtolower(str_replace("http://","",$domain));
				$domain = str_replace("https://","",$domain);
				$domain = preg_replace("#/$#","",$domain);
				//$domain = str_replace("/","",$domain);
				$subdomains[$domain]=$langs[$langid]->shortcode;
			}
			if (array_key_exists($host, $subdomains)){
				$lang = $subdomains[$host];
				// This get over written later - really stupid !!!
				$uri->setVar("lang",$lang);
				JRequest::setVar('lang', $lang );
				// I need to discover language here since menu is loaded in router
				plgSystemJFRouter::_discoverJFLanguage();
				$config = JFactory::getConfig();
				// TODO fix this for HTTPS
				$config->setValue('config.live_site',"http://".$host);
				$config->setValue("joomfish.current_host",$host);
				return array("lang"=>$lang);
			}
		}

		else {
			// Consider stripping base path from URI
			/*
			$live_site = JURI::base();
			$livesite_uri = new JURI($live_site);
			$livesite_path = $livesite_uri->getPath();
			$route = str_replace($livesite_path,"",$route);
			*/

			$jfm =  JoomFishManager::getInstance();
			$langs = $jfm->getLanguagesIndexedById();

			$sefprefixes = $params->getValue("sefprefixes",array());

			// Workaround if some language prefixes are missing
			if (!is_array($sefprefixes)){
				$sefprefixes = array();
			}
			if (count($sefprefixes)<count($langs)){
				foreach ($sefprefixes as $prefix) {
					list($langid,$prefix) = explode("::",$prefix,2);
					if (array_key_exists($langid,$langs)){
						$langs[$langid]->hasprefix = true;
					}
				}
				foreach ($langs as $lang) {
					if (!isset($lang->hasprefix)){
						$sefprefixes[] = $lang->lang_id."::".$lang->sef;
					}
				}
			}

			$segments = explode('/', $route);
			$seg=0;
			while ($seg<count($segments)){
				if (strlen($segments[$seg])==0) {
					$seg++;
					continue;
				}
				foreach ($sefprefixes as $prefix) {
					list($langid,$prefix) = explode("::",$prefix,2);
					// explode off any suffix
					if (strpos($segments[$seg],".")>0){
						$segcompare = substr($segments[$seg],0, strpos($segments[$seg],"."));
						// Trap for pdf, feed of html info in the extension
						if (strpos($segments[$seg],$prefix.".")===0){
							$format = str_replace($prefix.".","",$segments[$seg]);
							//$uri->setVar("format",$format);
							//JRequest::setVar('format', $format);
						}
					}
					else {
						$segcompare = $segments[$seg];
					}
					// including fix for suffix based url's and feeds
					if ($conf->getValue('sef_suffix')==1 && $conf->getValue('sef_rewrite')==1 && $conf->getValue('sef') ) {
						for ($l=0; $l<count($segments);$l++) {
							if (!empty ($segments[$l]) ) {
								$format = explode (".",$segments[$l]);
								if (!empty($format[1]) && trim($format[1])!=="" && trim($format[1])!=="php") {
									$uri->setVar("format",$format[1]);
									JRequest::setVar('format', $format[1]);
									break;
								}
							}
						}
					}
					// does the segment match the prefix
					if ($segcompare==$prefix){
																				
						// This section forces the current url static to include the language string which means the base tag is correct - but ONLY on the home page
						// restricting this to the homepage means no risk for image paths etc.
						$homepage = true;
						for ($seg2=$seg+1;$seg2<count($segments);$seg2++) {
							$segment = $segments[$seg2];
							if (strlen($segment)>0) $homepage = false;
						}
						if ($homepage){
							$current = JURI::current();
							$uri	 =  JURI::getInstance();
							$current = $uri->toString( array('scheme', 'host', 'port', 'path'));
						}

						unset($segments[$seg]);//array_shift($segments);
						$uri->setPath(implode("/",$segments));

						$lang = $langs[$langid]->shortcode;
						// This get over written later - really stupid !!!
						$uri->setVar("lang",$lang);

						JRequest::setVar('lang', $lang);
						// I need to discover language here since menu is loaded in router
						plgSystemJFRouter::_discoverJFLanguage();
						return array("lang"=>$lang);
					}
				}

				$seg++;
			}
		}
		plgSystemJFRouter::_discoverJFLanguage();
		return array();
	}

}

function routeJFRule($router, &$uri){
	$registry = JFactory::getConfig();
	$multilingual_support= $registry->getValue("config.multilingual_support",false);
	$jfLang = $registry->getValue("joomfish.language", false);
	if ($multilingual_support && $jfLang){
		if ($uri->getVar("lang","")==""){
			$uri->setVar("lang",($jfLang->shortcode!='') ? $jfLang->shortcode : $jfLang->iso);
		}
		// this is dependent on Joomfish router being first!!
		$lang=$uri->getVar("lang","");

		$conf = JFactory::getConfig();

		// This may not ready at this stage
		$params = $conf->getValue("jfrouter.params");

		// so load plugin parameters directly
		if (is_null($params)){
			$params = JPluginHelper::getPlugin("system", "jfrouter");
			$params = new JParameter($params->params);
		}
		
		$sefordomain = $params->get("sefordomain","sefprefix");

		if ($sefordomain == "domain"){
			// If I set config_live_site I actually don't need this function at all let alone this logic ?  Apart from language switcher.
			// TODO cache the indexed array
			$rawsubdomains = $params->getValue("sefsubdomain",array());
			$subdomains = array();
			$jfm =  JoomFishManager::getInstance();
			$langs = $jfm->getLanguagesIndexedById();
			foreach ($rawsubdomains as $domain) {
				list($langid,$domain) = explode("::",$domain,2);
				$domain = strtolower(str_replace("http://","",$domain));
				$domain = str_replace("https://","",$domain);
				$domain = preg_replace("#/$#","",$domain);
				//$domain = str_replace("/","",$domain);
				$subdomains[$langs[$langid]->shortcode]=$domain;
			}

			if (array_key_exists($lang,$subdomains)) {
				$uri->setHost($subdomains[$lang]);
				$uri->delVar("lang");
				$registry->setValue("joomfish.sef_host",$subdomains[$lang]);
				
				plgSystemJFRouter::procesCustomBuildRule($router, $uri);
				return;
			}
		}
		else {
			// Get the path data
			$route = $uri->getPath();

			//Add the suffix to the uri
			if($router->_mode == JROUTER_MODE_SEF && $route && !$lang!==""){

				$jfm =  JoomFishManager::getInstance();
				$jfLang = $jfm->getLanguageByShortcode($lang);
				if (!$jfLang) return;

				$sefprefixes = $params->getValue("sefprefixes",array());

				// Workaround if some language prefixes are missing
				$langs = $jfm->getLanguagesIndexedById();
				if (!is_array($sefprefixes)){
					$sefprefixes = array();
				}
				if (count($sefprefixes)<count($langs)){
					foreach ($sefprefixes as $prefix) {
						list($langid,$prefix) = explode("::",$prefix,2);
						if (array_key_exists($langid,$langs)){
							$langs[$langid]->hasprefix = true;
						}
					}
					foreach ($langs as $lang) {
						if (!isset($lang->hasprefix)){
							$sefprefixes[] = $lang->lang_id."::".$lang->sef;
						}
					}
				}

				foreach ($sefprefixes as $prefix) {
					list($langid,$prefix) = explode("::",$prefix,2);
					if ($jfLang->lang_id == $langid){
						$uri->setPath($uri->getPath()."/".$prefix);
						$uri->delVar("lang");
						plgSystemJFRouter::procesCustomBuildRule($router, $uri);
						return;
					}
				}
			}
		}
		
	}
	return;
}
