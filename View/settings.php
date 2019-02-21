
<?php
	ee()->cp->add_js_script(
		array(
			'ui'      => array('widget', 'dropdown', 'autocomplete'),
			'plugins'  => array('ee_notice', 'ee_table')
		)
	);
	
	ee()->cp->load_package_css('settings');
	ee()->cp->load_package_js('settings');
?>
<div class="col-group ">
	
	<div>
	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
		<?php if(isset($table)) : ?>
			<?php $this->embed(EXT_SHORT_NAME.":email/sent", $table); ?>
		<?php else : ?>
			<?php if((array_search(end($active), $current_settings['service_order'])) !== FALSE) : ?>
					<h1><?= lang(EXT_SHORT_NAME.'_'. end($active) .'_heading'); ?></h1>
					<div class="txt-wrap">
						<?=lang(EXT_SHORT_NAME.'_'. end($active) .'_text')?>
					</div>
			<?php else : ?>
				<?php $this->embed('ee:_shared/form', $vars)?>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

