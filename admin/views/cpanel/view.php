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
 * $Id: view.php 1597 2012-01-20 10:03:16Z akede $
 * @package joomfish
 * @subpackage view
 *
*/

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.html.pane');

JLoader::import( 'views.default.view',JOOMFISH_ADMINPATH);

/**
 * HTML View class for the WebLinks component
 *
 * @static
 * @package		Joom!Fish
 * @subpackage	Views
 * @since 1.0
 */
class CPanelViewCPanel extends JoomfishViewDefault
{
	/**
	 * Control Panel display function
	 *
	 * @param template $tpl
	 */
	public function display($tpl = null)
	{
		
		JHTML::stylesheet( 'joomfish.css', 'administrator/components/com_joomfish/assets/css/' );
		JHTML::stylesheet( 'jfhelp.css', 'administrator/components/com_joomfish/assets/css/' );
		
		$document = JFactory::getDocument();
		$document->setTitle(JText::_('JOOMFISH_TITLE') . ' :: ' .JText::_('CONTROL PANEL'));
		$document->addScript(JURI::base().'components/com_joomfish/assets/js/joomfish.mootools.js');
		
		// Set toolbar items for the page
		JToolBarHelper::title( JText::_('JOOMFISH_TITLE') .' :: '. JText::_( 'JOOMFISH_HEADER' ), 'fish' );
		JToolBarHelper::preferences('com_joomfish', '580', '750');
		JToolBarHelper::help( 'screen.cpanel', true);

		JSubMenuHelper::addEntry(JText::_('Control Panel'), 'index2.php?option=com_joomfish', true);
		JSubMenuHelper::addEntry(JText::_('Translation'), 'index2.php?option=com_joomfish&amp;task=translate.overview');
		JSubMenuHelper::addEntry(JText::_('Orphans'), 'index2.php?option=com_joomfish&amp;task=translate.orphans');
		JSubMenuHelper::addEntry(JText::_('Manage Translations'), 'index2.php?option=com_joomfish&amp;task=manage.overview', false);
		JSubMenuHelper::addEntry(JText::_('Statistics'), 'index2.php?option=com_joomfish&amp;task=statistics.overview', false);
		JSubMenuHelper::addEntry(JText::_('Language Configuration'), 'index2.php?option=com_joomfish&amp;task=languages.show', false);
		JSubMenuHelper::addEntry(JText::_('Content elements'), 'index2.php?option=com_joomfish&amp;task=elements.show', false);
		JSubMenuHelper::addEntry(JText::_('HELP AND HOWTO'), 'index2.php?option=com_joomfish&amp;task=help.show', false);
						
		$this->panelStates	= $this->get('PanelStates');
		$this->contentInfo	= $this->get('ContentInfo');
		$this->performanceInfo	= $this->get('PerformanceInfo');
		$this->publishedTabs	= $this->get('PublishedTabs');
		
		$this->assignRef('panelStates', $this->panelStates);
		$this->assignRef('contentInfo', $this->contentInfo);
		$this->assignRef('performanceInfo', $this->performanceInfo);
		$this->assignRef('publishedTabs', $this->publishedTabs);
		$this->assignRef('usersplash', $this->get('usersplash'));
		
		JHTML::_('behavior.tooltip');
		parent::display($tpl);
	}
	

	 /**
	  * render Information module
	  */
	 protected function renderInformation () {
	 	$output = '';
	 	//$panelStates = $this->get('panelStates');
		$this->assignRef('sysInfo', $this->panelStates['system']);
		return $this->loadTemplate('information');
	 }
	 
	 /**
	  * render News feed from Joom!Fish portal
	  */
	 protected function renderJFNews() {
	 	
	 	$output = '';

		//  get RSS parsed object
		$options = array();
		$options['rssUrl']		= 'http://www.joomfish.net/news?format=feed&type=rss&utm_source=jf-core&utm_medium=newsfeed&utm_campaign=jf21';
		$options['cache_time']	= 86400;

		$rssDoc = JFactory::getXMLparser('RSS', $options);

		if ( $rssDoc == false ) {
			$output = '';
		} else {	
			// channel header and link
			$title 	= $rssDoc->get_title();
			$link	= $rssDoc->get_link();
			
			$output = '<table class="adminlist">';
			$output .= '<tr><th colspan="3"><a href="'.$link.'" target="_blank">'.JText::_($title) .'</th></tr>';
			$output .= '<tr><td colspan="3">'.JText::_('NEWS_INTRODUCTION').'</td></tr>';
			
			$items = array_slice($rssDoc->get_items(), 0, 3);
			$numItems = count($items);
            if($numItems == 0) {
            	$output .= '<tr><th>' .JText::_('No news items found'). '</th></tr>';
            } else {
            	$k = 0;
                for( $j = 0; $j < $numItems; $j++ ) {
                    $item = $items[$j];
                	$output .= '<tr><td class="row' .$k. '">';
                	$output .= '<a href="' .$item->get_link(). '" target="_blank">' .$item->get_title(). '</a>';
					if($item->get_description()) {
	                	$description = $this->limitText($item->get_description(), 50);
						$output .= '<br />' .$description;
					}
                	$output .= '</td></tr>';
                }
            }
			$k = 1 - $k;
						
			$output .= '</table>';
		}	 	
	 	return $output;
	 }
	 
	 /**
	  * render content state information
	  */
	 protected function renderContentState() {
	 	$joomFishManager =  JoomFishManager::getInstance();
	 	$output = '';
		$alertContent = false;
		if( array_key_exists('unpublished', $this->contentInfo) && is_array($this->contentInfo['unpublished']) ) {
			$alertContent = true;
		}		
		ob_start();
		?>
		<table class="adminlist">
			<tr>
				<th><?php echo JText::_("UNPUBLISHED CONTENT ELEMENTS");?></th>
				<th style="text-align: center;"><?php echo JText::_("Language");?></th>
				<th style="text-align: center;"><?php echo JText::_("Publish");?></th>
			</tr>
			<?php
			$k=0;
			if( $alertContent ) {
				$curReftable = '';
				foreach ($this->contentInfo['unpublished'] as $ceInfo ) {
					$contentElement = $joomFishManager->getContentElement( $ceInfo['catid'] );

					// Trap for content elements that may have been removed
					if (is_null($contentElement)){
						$name = "<span style='font-style:italic'>".JText::sprintf("CONTENT_ELEMENT_MISSING",$ceInfo["reference_table"])."</span>";
					}
					else {
						$name = $contentElement->Name;
					}
					if ($ceInfo["reference_table"] != $curReftable){
						$curReftable = $ceInfo["reference_table"];
						$k=0;
						?>
			<tr><td colspan="3"><strong><?php echo $name;?></strong></td></tr>
						<?php
					}

					JLoader::import( 'models.ContentObject',JOOMFISH_ADMINPATH);
					$contentObject = new ContentObject( $ceInfo['language_id'], $contentElement );
					$contentObject->loadFromContentID($ceInfo['reference_id']);
					$link = 'index2.php?option=com_joomfish&amp;task=translate.edit&amp;&amp;catid=' .$ceInfo['catid']. '&cid[]=0|' .$ceInfo['reference_id'].'|'.$ceInfo['language_id'];
					$hrefEdit = "<a href='".$link."'>".$contentObject->title. "</a>";

					$link = 'index2.php?option=com_joomfish&amp;task=translate.publish&amp;catid=' .$ceInfo['catid']. '&cid[]=0|' .$ceInfo['reference_id'].'|'.$ceInfo['language_id'];
					$hrefPublish = '<a href="'.$link.'"><img src="images/publish_x.png" width="12" height="12" border="0" alt="" /></a>';
					?>
			<tr class="row<?php echo $k;?>">
				<td align="left"><?php echo $hrefEdit;?></td>
				<td style="text-align: center;"><?php echo $ceInfo['language'];?></td>
					<td style="text-align: center;"><?php echo $hrefPublish;?></td>
			</tr>
					<?php
					$k = 1 - $k;
				}
			} else {
					?>
			<tr class="row0">
				<td colspan="3"><?php echo JText::_("No unpublished translations found");?></td>
			</tr>
					<?php
			}
			?>
		</table>
		<?php
		$output .= ob_get_clean();
	 	return $output;
	 }
	 
	 /**
	  * render content state information
	  */
	 protected function renderPerformanceInfo() {
	 	$output = '';
		ob_start();
		?>
		<table class="adminlist">
			<tr>
				<th />
				<th ><?php echo JText::_("Current");?></th>
				<th ><?php echo JText::_("Best Available");?></th>
			</tr>
			<tr class="row0">
				<?php 
				if ($this->performanceInfo["driver"]["optimal"]){
					$color="green";
				}
				else {
					$color="red";					
				}
				echo "<td>".JText::_("mySQL Driver")."</td>\n";
				echo "<td>".$this->performanceInfo["driver"]["current"]."</td>\n";
				echo "<td style='color:$color;font-weight:bold'>".$this->performanceInfo["driver"]["best"]."</td>\n";
				?>
			</tr>
			<tr class="row1">
				<?php 
				if ($this->performanceInfo["cache"]["optimal"]){
					$color="green";
				}
				else {
					$color="red";					
				}
				echo "<td>".JText::_("Translation Caching")."</td>\n";
				echo "<td>".$this->performanceInfo["cache"]["current"]."</td>\n";
				echo "<td style='color:$color;font-weight:bold'>".$this->performanceInfo["cache"]["best"]."</td>\n";
				?>
			</tr>
			</table>
		<?php
		$output .= ob_get_clean();
	 	return $output;
	 }
	 
	 /**
	  * render system state information
	  */
	 protected function renderSystemState() {
	 	$output = '';
		$stateGroups =  $this->panelStates;
		ob_start();
		?>
		<table class="adminlist">
			<?php
			foreach ($stateGroups as $key=>$stateRow) {
				if (!is_array($stateRow) || count($stateRow)==0){
					continue;
				}
				?>
			<tr>
				<th colspan="3"><?php echo JText::_($key. ' state');?></th>
			</tr>
				<?php
				$k=0;
				foreach ($stateRow as $row) {
					if (!isset($row->link ) ) continue;
					?>
			<tr class="row<?php echo $k;?>">
				<td><?php
					if( $row->link != '' ) {
						$row->description = '<a href="' .$row->link. '">' .$row->description. '</a>';
					}
					echo $row->description;
				?></td>
				<td colspan="2"><?php echo $row->resultText;?></td>
			</tr>
					<?php
					$k = 1 - $k;
				}
			}
			?>
			</table>
		<?php
		$output .= ob_get_clean();
	 	return $output;
	 }

	protected function limitText($text, $wordcount)
	{
		if(!$wordcount) {
			return $text;
		}

		$texts = explode( ' ', $text );
		$count = count( $texts );

		if ( $count > $wordcount )
		{
			$text = '';
			for( $i=0; $i < $wordcount; $i++ ) {
				$text .= ' '. $texts[$i];
			}
			$text .= '...';
		}

		return $text;
	}
		 
}
