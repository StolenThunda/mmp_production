<?php
	ee()->cp->load_package_css('settings');
?>
<div>
	<?php if (isset($active_service_names)) : ?>		
		<input id='active_services' type='hidden' value='<?php echo($active_service_names); ?>' />
	<?php endif; ?>
</div>
<div class="col-group ">
	<?php if (isset($current_action)) : ?>
		<h1><?= lang($current_action.'_heading'); ?></h1>
		<div class="txt-wrap">
			<?=lang($current_action .'_text')?>
		</div>
	<?php elseif (isset($table)) : ?>
		<?php $this->embed(EXT_SHORT_NAME.":email/sent", $table); ?>
	<?php else: ?>		
		<?php $this->embed('ee:_shared/form', $vars)?>
	<?php endif; ?>
</div>