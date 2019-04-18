<?php
	ee()->cp->load_package_css('settings');
?>
<div class="col-group ">
		<input id="current_service" type="hidden" value="<?php (isset($current_service) ? $current_service : ''); ?>">
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