<textarea class="has-format-options required" name="message" cols="" rows="10"><?=set_value('message', $message)?></textarea>
<?=form_error('message')?>
<div class="format-options">
	<label><?=lang('send_as')?></label>
	<!-- change 'markdown' to $mailtype when ready to handle html/plain text -->
	<?=form_dropdown('mailtype', $mailtype_options,'markdown' , 'id="mailtype"')?>
	<label><?=lang('word_wrap')?></label>
	<input type="checkbox" name="wordwrap" value="y" <?=set_checkbox('wordwrap', 'y', TRUE)?>>
</div>
