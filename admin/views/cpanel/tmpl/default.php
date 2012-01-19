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
 * $Id: default.php 1597 2012-01-20 10:03:16Z akede $
 * @package joomfish
 * @subpackage Views
 *
*/
defined('_JEXEC') or die('Restricted access'); ?>

<form action="index.php" method="post">
<table class="adminform">
	<tr>
		<td width="55%" valign="top">
			<div id="cpanel">
			<?php
			$link = 'index2.php?option=com_joomfish&amp;task=translate.overview';
			$this->_quickiconButton( $link, 'icon-48-translation.png', JText::_('Translation') );

			$link = 'index2.php?option=com_joomfish&amp;task=translate.orphans';
			$this->_quickiconButton( $link, 'icon-48-orphan.png', JText::_('Orphans') );

			$link = 'index2.php?option=com_joomfish&amp;task=manage.overview';
			$this->_quickiconButton( $link, 'icon-48-manage.png', JText::_('Manage Translations') );

			$link = 'index2.php?option=com_joomfish&amp;task=statistics.overview';
			$this->_quickiconButton( $link, 'icon-48-statistics.png', JText::_('Statistics') );

			echo '<div style="clear: both;" />';

			$link = 'index2.php?option=com_joomfish&amp;task=languages.show';
			$this->_quickiconButton( $link, 'icon-48-language.png', JText::_('Language Configuration') );

			$link = 'index2.php?option=com_joomfish&amp;task=elements.show';
			$this->_quickiconButton( $link, 'icon-48-extension.png', JText::_('Content elements') );

			$link = 'index2.php?option=com_joomfish&amp;task=plugin.show';
			$this->_quickiconButton( $link, 'icon-48-plugin.png', JText::_('Manage Plugins') );

			$link = 'index2.php?option=com_joomfish&amp;task=help.show';
			$this->_quickiconButton( $link, 'icon-48-help.png', JText::_('HELP AND HOWTO') );
			?>
		</div>
		</td>
		<td width="45%" valign="top">
		<div style="width: 100%">
		<?php
			$tabs	= $this->get('publishedTabs');
			$pane		= JPane::getInstance('Tabs');
			echo $pane->startPane("content-pane");
	
			foreach ($tabs as $tab) {
				$title = JText::_($tab->title);
				$renderer = 'render' .$tab->name;
				$output = $this->$renderer();
				if($output != '')  {
					echo $pane->startPanel( $title, 'jfcpanel-panel-'.$tab->name );
					echo $output;
					echo $pane->endPanel();
				}
			}
	
			echo $pane->endPane();

		 ?>
		</div>
		</td>
	</tr>
</table>
<input type="hidden" name="option" value="com_joomfish" />
<input type="hidden" name="task" value="cpanel.show" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="<?php echo JUtility::getToken(); ?>" value="1" />
</form>

<?php if($this->usersplash == 1) :?>
<script type="text/javascript">
//<![CDATA[
function showUserSplash() {
	var sBox = SqueezeBox.initialize();

	sBox.fromElement(document, {
		handler: 'iframe',
		url: '<?php echo JURI::base()?>index.php?option=com_joomfish&task=cpanel.usersplash&layout=usersplash&tmpl=component',
		size: {x: 800, y: 600}
	});

	return sBox;
}

// The configuration shall be saved anytime the splash screen is closed.
// This is to ensure that a simple change in the splash screen can provide good user experience
sBox = showUserSplash();
sBox.addEvent('onClose', function(e){
	var form = $E('iframe').contentDocument.getElementById('jfusersplashform');
	form.submit();
});

//]]>
</script>
<?php endif;?>