<?php 
/************************************************************************
 * @project Gutuma Newsletter Managment
 * @author Rowan Seymour
 * @copyright This source is distributed under the GPL
 * @file included lists page
 * @modifications Cyril Maguire
 */
/* Gutama plugin package
 * @version 1.6
 * @date	01/10/2013
 * @author	Cyril MAGUIRE
*/

include_once '_menu.php';?>

<h2><?php echo t('Manage lists');?></h2>

<?php gu_theme_messages(); ?>

<p><?php echo t('These are the lists which have already been created.');?> </p>
<form method="post" name="lists_form" id="lists_form" action=""><input name="num_lists" type="hidden" id="num_lists" value="<?php echo count($lists); ?>" />
	<table border="0" cellspacing="0" cellpadding="0" class="results" id="liststable">
		<tr>
			<td><strong><?php echo t('Name');?></strong></td>
			<td><strong><?php echo t('Addresses');?></strong></td>
			<td><strong><?php echo t('In transit');?></strong></td>
			<td><strong><?php echo t('Private');?></strong></td>
			<td>&nbsp;</td>
		</tr>
<?php
if (count($lists) > 0) {
	foreach($lists as $list) {
		$list_is_private = $list->is_private();
?>
		<tr id="row_<?php echo $list->get_id(); ?>">
			<td class="name" title="<?php echo $list->get_name(); ?>"><?php echo $list->get_name(); ?></td>
			<td><?php echo $list->get_size(); ?></td>
			<td><i style="<?php echo ($list_is_private||!@$listsTmpSize[$list->get_id()])?'display:none;':''; ?>"><script type="text/javascript">document.write(gu_list_menu(<?php echo $list->get_id(); ?>, "tmp"))</script></i>&nbsp;<?php echo @$listsTmpSize[$list->get_id()] ?></td>
			<td><?php echo $list_is_private ? t('Yes') : t('No'); ?></td>
			<td style="text-align: center"><script type="text/javascript">document.write(gu_list_menu(<?php echo $list->get_id(); ?>))</script></td>
		</tr>
<?php
	}
}
?>
		<tr id="row_empty" style="display: <?php echo (count($lists) == 0) ? 'table-row' : 'none'; ?>"><td colspan="4" class="emptyresults"><?php echo t('No lists');?></td></tr>
	</table>
</form>
<h3><?php echo t('Create new list');?></h3>
<div class="formfieldcomment"><?php echo t('If the list is marked as private then people cannot subscribe to it, and it will not be listed on the default subscribe page.');?></div>
<form method="post" name="add_form" id="add_form" action="" onsubmit="gu_list_add(this.new_list_name.value, this.new_list_private.checked); return false;">
	<div class="menubar">
		<div style="float: left">
			<?php echo t('Name');?> <input name="new_list_name" type="text" class="textfield" id="new_list_name" /> <?php echo t('Private');?> <input type="checkbox" id="new_list_private" name="new_list_private" />	
		</div>
		<div style="float: right">
			<input name="add_list" type="submit" id="add_list" value="<?php echo t('Add');?>" />
		</div>	
	</div>
</form>
<h3><?php echo t('Import list');?></h3>
<!-- The data encoding type, enctype, MUST be specified as below -->
<form enctype="multipart/form-data" method="post" name="import_form" id="import_form" action="lists.php">
	<!-- MAX_FILE_SIZE must precede the file input field -->
	<p><?php echo t('A new list can be created from a CSV file of addresses. The format of this file should be email addresses in the first column - other columns will be ignored.');?> </p>
	<div class="menubar">
		<div style="float: left"><input name="import_file" type="file" id="import_file" /></div>
		<div style="clear: both;text-align: left;">
			<?php echo t('Separate by').'&nbsp;'; gu_theme_list_control('sep', array(array(';',t('Semicolon (;)')),array(',',t('Comma (,)'))),';') ?> &amp;
			<?php echo t('Ingnore first line');?>&nbsp;<input type="checkbox" id="first" name="first" checked="" />
			<div style="float: right">
				<input name="import_submit" type="submit" id="import_submit" value="<?php echo t('Import');?>" />
			</div>
		</div>
	</div>
</form>