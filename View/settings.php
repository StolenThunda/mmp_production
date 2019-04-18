<?php
	ee()->cp->load_package_css('settings');
	ee()->cp->load_package_js('settings');
	$current_action = $current_service;
	$is_category = (in_array($current_action, $categories));
?>
<div class="col-group">
	
	<div class="col w-12">
		<?php if($debug == TRUE) : ?>
			<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
		<?php endif; ?>

		<?php if($is_category) : ?>
				<h1><?= lang($current_action.'_heading'); ?></h1>
				<div class="txt-wrap">
					<?=lang($current_action .'_text')?>
				</div>
		<?php elseif($current_action === 'sent') : ?>
			<?php $this->embed(EXT_SHORT_NAME.":email/sent", $table); ?>
		<?php else : ?>
			<?php $this->embed('ee:_shared/form', $vars)?>
		<?php endif; ?>
	</div>
</div>