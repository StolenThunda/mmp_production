<?php
	ee()->cp->load_package_css('settings');
	ee()->cp->load_package_js('settings');
?>

<!--
	I need to close out the existing .col,
	because otherwise the below columns will not 
	meet up with the outer edges of the breacrumbs (ugly).
	Note that I now need to leave the closing </div> out of this view.
-->
</div>

<div class="col-group align-right">
	
	<div class="col w-12">
		<?php if(array_search(end($active), array_keys($sidebar_options))) : ?>
				<h1><?= lang(EXT_SHORT_NAME.'_'. end($active) .'_heading'); ?></h1>
				<div class="txt-wrap">
					<?=lang(EXT_SHORT_NAME.'_'. end($active) .'_text')?>
				</div>
		<?php else : ?>
			<?php $this->embed('ee:_shared/form', $vars)?>
		<?php endif; ?>
	</div>
	<div class="col w-4">
		<div class='box sidebar'>
		<?php if(isset($sidebar_options)) : ?>
			<?=$sidebar_content?>
		<?php else : ?>
			NO SIDEBAR
		<?php endif; ?>
		</div>
	</div>

	


<!-- Closing </div> intentionally absent! -->