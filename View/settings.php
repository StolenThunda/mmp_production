<<<<<<< Updated upstream
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

		<?php //if($is_category) : ?>
		<?php if (isset($current_action)) : ?>
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
=======
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

		<?php //if($is_category) : ?>
		<?php if (isset($current_action)) : ?>
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
		<!-- <div class="col w-4">
		<div class="box sidebar">
			<h2<?php if(empty($current_service)) : ?> class="act"<?php endif; ?>><a href="<?=ee('CP/URL','addons/settings/escort');?>">Overview</a></h2>
			<h2><?= lang('escort_services'); ?></h2>
				<ul class="escort-service-list" data-action-url="<?=ee('CP/URL','addons/settings/escort');?>">
				<?php foreach($services as $service => $settings) : ?>
					<li data-escort-service="<?=$service;?>" class="<?=(!empty($current_settings[$service.'_active']) && $current_settings[$service.'_active'] == 'y') ? 'enabled-service' : 'disabled-service';?><?php if($current_service == $service) : ?> act<?php endif; ?>">
						<a href="<?=ee('CP/URL','addons/settings/escort/'.$service);?>"><?= lang('escort_'.$service.'_name'); ?></a>
					</li>
				<?php endforeach; ?>
				</ul>
		</div> -->
>>>>>>> Stashed changes
</div>