<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo T('Discussions that are inside enabled Categories will be show in the default "All Discussions" view.'); ?>
</div>
<div class="FilterMenu">
	<?php echo Anchor('Enable All Categories', 'plugin/discussionscategoryfilter/enableall', 'SmallButton'); ?>
</div>

<?php echo $this->Form->Open(); ?>

<table class="FormTable AltColumns<?php echo $CssClass;?>" id="DiscussionsCategoryFilterTable">
	<thead>
		<tr id="0">
			<th><?php echo T('Category'); ?></th>
			<th class="Alt"><?php echo T('Status'); ?></th>
			<th><?php echo T('Options'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	$Alt = FALSE;
	foreach ($this->CategoryData->Result() as $Category):
		$Alt = $Alt ? FALSE : TRUE;
		$CssClass = $Alt ? 'Alt' : '';
		$CssClass .= $Category->AllowDiscussions == '1' ? ' Child' : ' Parent';

		$CssClass = trim($CssClass);
	?>
		<tr id="<?php echo $Category->CategoryID; ?>"<?php echo $CssClass != '' ? ' class="'.$CssClass.'"' : ''; ?>>
			<td class="First"><strong><?php echo $Category->Name; ?></strong></td>
			<td class="Alt"><?php echo $Category->ShowInAllDiscussions == 1 ? T('Enabled') : T('Disabled'); ?></td>
			<td class="Last">
				<?php
					if ($Category->ShowInAllDiscussions == '1') {
						echo Anchor('Disable', 'plugin/discussionscategoryfilter/disable/'.$Category->CategoryID, 'SmallButton');
					} else {
						echo Anchor('Enable', 'plugin/discussionscategoryfilter/enable/'.$Category->CategoryID, 'SmallButton');
					}
				?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<?php echo $this->Form->Close(); ?>