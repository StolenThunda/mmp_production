<?php
	ee()->cp->load_package_css('settings');
	ee()->cp->load_package_js('settings');
?>
<div class="col-group ">
	
	<div>
	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
		
		<?php if((array_search(end($active), array_keys($sidebar_options))) !== FALSE) : ?>
				<h1><?= lang(EXT_SHORT_NAME.'_'. end($active) .'_heading'); ?></h1>
				<div class="txt-wrap">
					<?=lang(EXT_SHORT_NAME.'_'. end($active) .'_text')?>
				</div>
		<?php else : ?>
			<?php $this->embed('ee:_shared/form', $vars)?>
		<?php endif; ?>
	</div>
</div>

