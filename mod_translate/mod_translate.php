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
 * $Id: mod_translate.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage mod_translate
 *
*/

// ensure this file is being included by a parent file
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

include_once( JPATH_SITE .DS. 'components' .DS. 'com_joomfish' .DS. 'helpers' .DS. 'defines.php' );
JLoader::register('JoomfishManager', JOOMFISH_ADMINPATH .DS. 'classes' .DS. 'JoomfishManager.class.php' );
JLoader::register('JoomfishExtensionHelper', JOOMFISH_ADMINPATH .DS. 'helpers' .DS. 'extensionHelper.php' );
JLoader::register('JoomFishVersion', JOOMFISH_ADMINPATH .DS. 'version.php' );
JLoader::import('helper', dirname( __FILE__ ), 'jftranslate');

JHTML::_('behavior.modal');

$linkType = $params->get("linktype","squeezebox");

$components = JFTranslateHTML::getComponentMapping($params->get("components",''));
$mapping=null;
foreach ($components as $component){
	$map = explode("#",$component);
	if (count($map)>=3 && trim($map[0])==$option){
		if (count($map)>3 && (count($map)-3)%2==0){
			$matched=true;
			for ($p=0;$p<(count($map)-3)/2;$p++){
				$testParam = JRequest::getVar( trim($map[3+$p*2]), '');
				if ((strpos(trim($map[4+$p*2]),"!")!==false && strpos(trim($map[4+$p*2]),"!")==0)){
					if ($testParam == substr(trim($map[4+$p*2]),1)){
						$matched=false;
						break;
					}
				}
				else {
					if ($testParam != trim($map[4+$p*2])){
						$matched=false;
						break;
					}
				}
			}
			if ($matched) {
				$mapping=$map;
				break;
			}
		}
		else {
			$mapping=$map;
			break;
		}
	}
}
// Add the standard style to the site
JHTML::stylesheet("mod_translate.css","administrator/modules/mod_translate/assets/");
$joomFishManager =  JoomFishManager::getInstance();

if ($mapping!=null){

	//Global definitions
	if( !defined('DS') ) {
		define( 'DS', DIRECTORY_SEPARATOR );
	}

	if( !defined('JOOMFISH_PATH') ) {
		define( 'JOOMFISH_PATH', JPATH_SITE .'components'.DS.'com_joomfish' );
		define( 'JOOMFISH_ADMINPATH', JPATH_ADMINISTRATOR .DS.'components'.DS.'com_joomfish' );
		define( 'JOOMFISH_LIBPATH', JOOMFISH_ADMINPATH .DS. 'libraries' );
		define( 'JOOMFISH_LANGPATH', JOOMFISH_PATH .DS. 'language' );
		define( 'JOOMFISH_URL', '/components/com_joomfish');
	}

	$lang = JFactory::getLanguage();
	$lang->load('com_joomfish');

	// load languages via translation model
	//JLoader::register('TranslateModelTranslate', JOOMFISH_ADMINPATH.DS.'models'.DS.'translate.php');
	$langActive = $joomFishManager->getLanguages(false);		// all languages even non active once
	$defaultLang = $joomFishManager->getDefaultLanguage();
	$params = JComponentHelper::getParams('com_joomfish');
	$showDefaultLanguageAdmin = $params->get("showDefaultLanguageAdmin", false);
	$langOptions[] = JHTML::_('select.option', -1, JText::_("SELECT LANGUAGE") );

	if ( count($langActive)>0 ) {
		foreach( $langActive as $language )
		{
			if($language->code != $defaultLang || $showDefaultLanguageAdmin) {
				$langOptions[] = JHTML::_('select.option',  $language->lang_id, $language->title );
			}
		}
	}
	$langlist = JHTML::_('select.genericlist', $langOptions, 'select_language_id', 'id="select_language_id" class="inputbox"  size="1" onChange="translateItem(\''.$linkType.'\');"', 'value', 'text', -1);//$langActive[0]->lang_id );
	// I also need to trap component specific actions e.g. pony gallery uses
?>
<span class='modtranslate'>
<script language="JavaScript" type="text/javascript">
function translateItem(linktype){
	var langCode=document.getElementById('select_language_id').value;
	var option="<?php echo trim($mapping[1]);?>";

	if( linktype == '' ) linktype = 'squeezebox';
	if( langCode == -1 ) return;

	if (document.adminForm.boxchecked.value==0) {
		alert("<?php echo JText::sprintf( 'Please make a selection from the list to', JText::_("translate") ); ?>")
		return
		<?php
		$setlang="&select_language_id=\"+langCode+\"";
		$targetURL = JURI::root()."administrator/index2.php?option=com_joomfish&task=translate.edit&catid=\"+option+\"".$setlang;
		?>

		openTranslationDialog(targetURL, linktype);
		return;// SqueezeBox.call(SqueezeBox, true);
	}
	if (document.adminForm.boxchecked.value!=1) {
		alert( "<?php echo JText::_("You must select exactly one item to translate");?>");
		return;
	}
	if (langCode==-1){
		alert( "<?php echo JText::_("You must select a language");?>");
		return;
	}
	// not all components use "cid" e.g. ponygallery uses act=pictures or act=showcatg
	var cid = "<?php echo trim($mapping[2]);?>[]";
	var checkboxes = document.getElementsByName(cid);
	for (var i=0;i<checkboxes.length;i++){
		if (checkboxes[i].checked){
			//alert("you want to edit item "+(i+1)+" content item id = "+checkboxes[i].value);
			// second part is language id 1=Cymraeg,5=German etc!
		<?php
		$targetURL = JURI::root()."administrator/index2.php?task=translate.edit&boxchecked=1&catid=\"+option+\"&cid[]=0|\"+checkboxes[i].value+\"|\"+langCode+\"&option=com_joomfish&select_language_id=\"+langCode+\"";
		?>
			targetURL = "<?php echo $targetURL;?>";
			openTranslationDialog(targetURL, linktype);
			return;// SqueezeBox.call(SqueezeBox, true);
		}
	}
	alert("There was a problem");
}
function openTranslationDialog(target, linktype) {
	switch (linktype) {
	case 'newwindow':
		target += '&direct=2';
		window.open(target,"translation","innerwidth=800,innerheight=500,menubar=yes,status=yes,location=yes,resizable=yes,scrollbars=yes");
		break;

	case 'samewindow':
		document.location.replace(target);
		break;

	case 'squeezebox':
	default:
		target += '&direct=1';
		SqueezeBox.initialize({});
		SqueezeBox.setOptions(SqueezeBox.presets,{'handler': 'iframe','size': {'x': 1000, 'y': 600},'closeWithOverlay': 0});
		SqueezeBox.url = target;

		SqueezeBox.setContent('iframe', SqueezeBox.url );
	}
	return;// SqueezeBox.call(SqueezeBox, true);
}
</script>
<a href="javascript:translateItem('<?php echo $linkType;?>')" title="<?php echo JText::_('translate this item'); ?>"><?php echo JText::_('translate to'); ?></a>:&nbsp;
<?php echo $langlist; ?>
</span>
<?php
}
else {
	$layout = JModuleHelper::getLayoutPath('mod_translate','default');
	require($layout);	
}
?>
