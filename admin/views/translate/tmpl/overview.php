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
 * $Id: overview.php 1592 2012-01-20 12:51:08Z akede $
 * @package joomfish
 * @subpackage Views
 *
*/


// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die( 'Restricted access' );

$message = $this->get('message');
$state			= $this->get('state');
$message1		= $state->get('message') == null ? $message : $state->get('message');
$message2		= $state->get('extension.message');
?>
<table class="adminform">
	<tbody>
		<?php if($message1) : ?>
		<tr>
			<th><?php echo JText::_($message1) ?></th>
		</tr>
		<?php endif; ?>
		<?php if($message2) : ?>
		<tr>
			<td><?php echo $message2; ?></td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>

$user = JFactory::getUser();
$db = JFactory::getDBO();
$filterOptions = '<table align="right"><tr>';
$filterOptions .= '<td  nowrap="nowrap" align="center">' .JText::_('Languages'). ':<br/>' .$this->langlist. '</td>';
$filterOptions .= '<td  nowrap="nowrap" align="center">' .JText::_('Content elements'). ':<br/>' .$this->clist. '</td>';
$filterOptions .= '</tr></table>';

if (isset($this->filterlist) && count($this->filterlist)>0){
	$filterOptions .= '<table align="right" style="clear:both"><tr>';
	foreach ($this->filterlist as $fl){
		if (is_array($fl))		$filterOptions .= "<td nowrap='nowrap' align='center'>".$fl["title"].":<br/>".$fl["html"]."</td>";
	}
	$filterOptions .= '</tr></table>';
}
?>
<form action="index.php" method="post" name="adminForm">
<?php echo $filterOptions; ?>
  <table class="adminlist" cellspacing="1">
  <thead>
    <tr>
      <th width="20"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($this->rows); ?>);" /></th>
      <th class="title" width="20%" align="left"  nowrap="nowrap"><?php echo JText::_('TITLE');?></th>
      <th width="10%" align="left" nowrap="nowrap"><?php echo JText::_('Language');?></th>
      <th width="20%" align="left" nowrap="nowrap"><?php echo JText::_('TITLE_TRANSLATION');?></th>
      <th width="15%" align="left" nowrap="nowrap"><?php echo JText::_('TITLE_DATECHANGED');?></th>
      <th width="15%" nowrap="nowrap" align="center"><?php echo JText::_('TITLE_STATE');?></th>
      <th align="center" nowrap="nowrap"><?php echo JText::_('TITLE_PUBLISHED');?></th>
    </tr>
    </thead>
    <tfoot>
        <tr>
    	  <td align="center" colspan="7">
			<?php echo $this->pageNav->getListFooter(); ?>
		  </td>
		</tr>
    </tfoot>
    
    <tbody>
	<?php
	if( !isset($this->catid) || $this->catid == "" || $this->language_id==-1) {
		?>
	<tr><td colspan="8"><p><?php echo JText::_('NOELEMENT_SELECTED');?></p></td></tr>
		<?php
	}
	else {
		?>
    <?php
    $k=0;
    $i=0;
    foreach ($this->rows as $row ) {
				?>
    <tr class="<?php echo "row$k"; ?>">
      <td width="20">
        <?php		if ($row->checked_out && $row->checked_out != $user->id) { ?>
        &nbsp;
        <?php		} else { ?>
        <input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row->translation_id."|".$row->id."|".$row->language_id; ?>" onclick="isChecked(this.checked);" />
        <?php		} ?>
      </td>
      <td>
      	<a href="#edit" onclick="hideMainMenu(); return listItemTask('cb<?php echo $i;?>','translate.edit');"><?php echo $row->title; ?></a>
			</td>
      <td nowrap><?php echo $row->language ? $row->language : JText::_('NOTRANSLATIONYET') ; ?></td>
      <td><?php echo $row->titleTranslation ? $row->titleTranslation : '&nbsp;'; ?></td>
	  <td><?php echo $row->lastchanged ? JHTML::_('date', $row->lastchanged, JText::_('DATE_FORMAT_LC2')):"" ;?></td>
				<?php
				switch( $row->state ) {
					case 1:
						$img = 'status_g.png';
						break;
					case 0:
						$img = 'status_y.png';
						break;
					case -1:
					default:
						$img = 'status_r.png';
						break;
				}
				?>
      <td align="center"><img src="components/com_joomfish/assets/images/<?php echo $img;?>" width="12" height="12" border="0" alt="" /></td>
				<?php
				if (isset($row->published) && $row->published) {
					$img = 'publish_g.png';
				} else {
					$img = 'publish_x.png';
				}

				$href='';
				if( $row->state>=0 ) {
					$href = '<a href="javascript: void(0);" ';
					$href .= 'onclick="return listItemTask(\'cb' .$i. '\',\'' .($row->published ? 'translate.unpublish' : 'translate.publish'). '\')">';
					$href .= '<img src="images/' .$img. '" width="12" height="12" border="0" alt="" />';
					$href .= '</a>';
				}
				else {
					$href = '<img src="images/' .$img. '" width="12" height="12" border="0" alt="" />';
				}
				?>
      <td align="center"><?php echo $href;?></td>
				<?php
				$k = 1 - $k;
				$i++;
    }
		?>
	</tr>
	</tbody>
</table>
<br />
<table cellspacing="0" cellpadding="4" border="0" align="center">
  <tr align="center">
    <td> <img src="components/com_joomfish/assets/images/status_g.png" width="12" height="12" border=0 alt="<?php echo JText::_('STATE_OK');?>" />
    </td>
    <td> <?php echo JText::_('TRANSLATION_UPTODATE');?> |</td>
    <td> <img src="components/com_joomfish/assets/images/status_y.png" width="12" height="12" border=0 alt="<?php echo JText::_('STATE_CHANGED');?>" />
    </td>
    <td> <?php echo JText::_('TRANSLATION_INCOMPLETE');?> |</td>
    <td> <img src="components/com_joomfish/assets/images/status_r.png" width="12" height="12" border=0 alt="<?php echo JText::_('STATE_NOTEXISTING');?>" />
    </td>
    <td> <?php echo JText::_('TRANSLATION_NOT_EXISTING');?></td>
  </tr>
  <tr align="center">
    <td> <img src="images/publish_g.png" width="12" height="12" border=0 alt="<?php echo JText::_('Translation visible');?>" />
    </td>
    <td> <?php echo JText::_('TRANSLATION_PUBLISHED');?>  |</td>
    <td> <img src="images/publish_x.png" width="12" height="12" border=0 alt="<?php echo JText::_('Finished');?>" />
    </td>
    <td> <?php echo JText::_('TRANSLATION_NOT_PUBLISHED');?></td>
    <td> &nbsp;
    </td>
    <td> <?php echo JText::_('STATE_TOGGLE');?></td>
  </tr>
</table>
<?php } ?>
</form>
