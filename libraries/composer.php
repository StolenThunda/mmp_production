<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
use EllisLab\ExpressionEngine\Controller\Utilities;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Model\Email\EmailCache;
use EllisLab\ExpressionEngine\View;

/**
 * Copy of Communicate Controller
 */
class Composer {

	private $attachments = array();
	private $csv_lookup = array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		$CI = ee();

		if ( ! ee()->cp->allowed_group('can_access_comm'))
		{
			show_error(lang('unauthorized_access'), 403);
		} 
		ee()->config->load('compose_js');

		$internal_js = ee()->config->item('internal_js');
		foreach ($internal_js as $js){
			ee()->cp->load_package_js($js);
		}
		$external_js = ee()->config->item('external_js');
		foreach($external_js as $script){
			ee()->cp->add_to_foot($script);
		}

	}

	/**
	 * Index
	 *
	 * @param	obj	$email	An EmailCache object for use in re-populating the form (see: resend())
	 */
	public static function compose(EmailCache $email = NULL)
	{
		$default = array(
			'from'		 	=> ee()->session->userdata('email'),
			'recipient'  	=> '',
			'cc'			=> '',
			'bcc'			=> '',
			'subject' 		=> '',
			'message'		=> '',
			'plaintext_alt'	=> '',
			'mailtype'		=> ee()->config->item('mail_format'),
			'wordwrap'		=> ee()->config->item('word_wrap'),
			'recipient_type'=> lang('compose_csv_recipient_type')
		);

		$vars['mailtype_options'] = array(
			'text'		=> lang('plain_text'),
			'markdown'	=> lang('markdown'),
			'html'		=> lang('html')
		);

		$vars['recipient_types'] = array(
			'recipient' => lang('compose_default_recipient_type'),
			'csv_recipient' => lang('compose_csv_recipient_type'),
			'file_recipient' => lang('compose_file_recipient_type')
		);

		$member_groups = array();

		if ( ! is_null($email))
		{
			$default['from'] = $email->from_email;
			$default['recipient'] = $email->recipient;
			$default['cc'] = $email->cc;
			$default['bcc'] = $email->bcc;
			$default['subject'] = str_replace('', '(TEMPLATE) ', $email->subject);
			$default['message'] = $email->message;
			$default['plaintext_alt'] = $email->plaintext_alt;
			$default['mailtype'] = $email->mailtype;
			$default['wordwrap'] = $email->wordwrap;
			// if ( ! isset($this->member))
			// {
			// 	$member_groups = $email->getMemberGroups()->pluck('group_id');
			// }
		}
		console_message($default, __METHOD__);
		// Set up member group emailing options
		if (ee()->cp->allowed_group('can_email_member_groups'))
		{
			$groups = ee('Model')->get('MemberGroup')
				->filter('site_id', ee()->config->item('site_id'))
				->all();

			$member_groups = [];
			$disabled_groups = [];
			foreach ($groups as $group)
			{
				$member_groups[$group->group_id] = $group->group_title;

				if (ee('Model')->get('Member')
					->filter('group_id', $group->group_id)
					->count() == 0)
				{
					$disabled_groups[] = $group->group_id;
				}
			}
		}

		$csvHTML = array(
			form_textarea(
				array(
					'name' => 'csv_recipient',
					'id' => 'csv_recipient',
					'rows' => '10',
					'class' => 'required',
				)
			),
			form_button('show_csv','Parse Emails','class="btn" onClick="getEmails()"')
		);

		if ($default['mailtype'] != 'html')
		{
			ee()->javascript->output('$("textarea[name=\'plaintext_alt\']").parents("fieldset").eq(0).hide();');
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'your_email',
					'fields' => array(
						'from' => array(
							'type' => 'text',
							'value' => $default['from'],
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'email_subject',
					'fields' => array(
						'subject' => array(
							'type' => 'text',
							'required' => TRUE,
							'value' => $default['subject']
						)
					)
				),
				array(
					'title' => 'email_body',
					'fields' => array(
						'message' => array(
							'type' => 'html',
							'content' => ee('View')->make(EXT_SHORT_NAME.':email/body-field')
								->render($default + $vars),
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'plaintext_body',
					'desc' => 'plaintext_alt',
					'fields' => array(
						'plaintext_alt' => array(
							'type' => 'textarea',
							'value' => $default['plaintext_alt'],
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'attachment',
					'desc' => 'attachment_desc',
					'fields' => array(
						'attachment' => array(
							'type' => 'file'
						)
					)
				)
			),
			'recipient_options' => array(
				array(
					'title' => 'recipient_type',
					'desc' => 'recipient_type_desc',
					'fields' => array(
						'dump_vars' => array(
							'type' => 'html',
							'content' => form_button('btnDump','Dump Hidden Values', 'class="btn" onClick="dumpHiddenVals()"')
						)
						,'recipient_type' => array(
							'type' => 'html',
							'content' => form_dropdown('recipient_type', $vars['recipient_types'], $default['recipient_type'] , 'id="recipient_type"')
						),
						'csv_object' => array(
							'type' => 'hidden',
							'value' => ''
						),
						'mailKey' => array(
							'type' => 'hidden',
							'value' => ''
                        ),
                        'formatted_emails' => array(
							'type' => 'hidden',
							'value' => ''
                        )
					)
				),
				array(
					'title' => 'primary_recipients',
					'desc' => 'primary_recipients_desc',
					'fields' => array(
						'recipient' => array(
							'type' => 'text',
							'value' => $default['recipient']
						)
					)
				),
				array(
					'title' => 'csv_recipient',
					'desc' => 'csv_recipient_desc',
					'fields' => array(
						'csv_recipient' => array(
							'type' => 'html',
							'content' => implode('<br />', $csvHTML)
							)
					)
				),
				array(
					'title' => 'file_recipient',
					'desc' => 'file_recipient_desc',
					'fields' => array(
						'files' => array(
							'type' => 'html',
							'content' => form_input(
								array(
									'id' => 'files',
									'name' => 'files[]',
									'type' => 'hidden',
									'value' => '0'
								)
							)
						),
						'csv_content' => array(
							'type' => 'html',
							'content' => '<output id=\'list\'></output><br><table id=\'csv_content\'></table>'
						),
						'file_recipient' => array(
							'type' => 'file',
							'content' => ee('CP/FilePicker')
								->make()
								->getLink('Choose File')
								->withValueTarget('files')
								->render()
							)
					)
				),
				array(
					'title' => 'cc_recipients',
					'desc' => 'cc_recipients_desc',
					'fields' => array(
						'cc' => array(
							'type' => 'text',
							'value' => $default['cc']
						)
					)
				),
				array(
					'title' => 'bcc_recipients',
					'desc' => 'bcc_recipients_desc',
					'fields' => array(
						'bcc' => array(
							'type' => 'text',
							'value' => $default['bcc']
						)
					)
				)
			)
		);

		if (ee()->cp->allowed_group('can_email_member_groups'))
		{
			$vars['sections']['recipient_options'][] = array(
				'title' => 'add_member_groups',
				'desc' => 'add_member_groups_desc',
				'fields' => array(
					'member_groups' => array(
						'type' => 'checkbox',
						'choices' => $member_groups,
						'disabled_choices' => $disabled_groups,
					)
				)
			);
		}
   		$vars['cp_page_title'] = lang('compose_heading');
		$vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/email:send');
		$vars['save_btn_text'] = lang('compose_send_email');
		$vars['save_btn_text_working'] = lang('compose_sending_email');
		$vars['cp_breadcrumbs'] = array(ee('CP/URL', EXT_SETTINGS_PATH.'/email:send')->compile(), $vars['cp_page_title']);
		console_message($vars, __METHOD__);
		return $vars;
	}

	/**
	 * Prepopulate form to send to specific member
	 *
	 * @param int $id
	 * @access public
	 * @return void
	 */
	public function member($id)
	{
		$member = ee('Model')->get('Member', $id)->first();
		$this->member = $member;

		if (empty($member))
		{
			show_404();
		}

		$cache_data = array(
			'recipient'	=> $member->email,
			'from_email' => ee()->session->userdata('email')
		);

		$email = ee('Model')->make('EmailCache', $cache_data);
		$email->removeMemberGroups();
		$this->compose($email);
	}

	/**
	 * Send Email
	 */
	public function send()
	{
		ee()->load->library('email');

		// Fetch $_POST data
		// We'll turn the $_POST data into variables for simplicity

		$groups = array();

		$form_fields = array(
			'subject',
			'message',
			'plaintext_alt',
			'mailtype',
			'wordwrap',
			'from',
			'attachment',
			'recipient',
			'cc',
			'bcc',
			'csv_object',
			'formatted_emails',
			'mailKey'
		);

		$wordwrap = 'n';

		foreach ($_POST as $key => $val)
		{
			if ($key == 'member_groups')
			{
				// filter empty inputs, like a hidden no-value input from React
				$groups = array_filter(ee()->input->post($key));
			}
			elseif (in_array($key, $form_fields))
			{
				$$key = ee()->input->post($key);
			}
		}
		
		// create lookup array for easy email lookup
		if (isset($csv_object) AND $csv_object !== ""){
			$rows =  json_decode($csv_object, TRUE);
			foreach ($rows as $row){
				$this->csv_lookup[$row[$mailKey]] = $row;
			}
		}

		//  Verify privileges
		if (count($groups) > 0 && ! ee()->cp->allowed_group('can_email_member_groups'))
		{
			show_error(lang('not_allowed_to_email_member_groups'));
		}

		// Set to allow a check for at least one recipient
		$_POST['total_gl_recipients'] = count($groups);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules('subject', 'lang:subject', 'required|valid_xss_check');
		ee()->form_validation->set_rules('message', 'lang:message', 'required');
		ee()->form_validation->set_rules('from', 'lang:from', 'required|valid_email');
		ee()->form_validation->set_rules('cc', 'lang:cc', 'valid_emails');
		ee()->form_validation->set_rules('bcc', 'lang:bcc', 'valid_emails');
		ee()->form_validation->set_rules('recipient', 'lang:recipient', 'valid_emails|callback__check_for_recipients');
		ee()->form_validation->set_rules('attachment', 'lang:attachment', 'callback__attachment_handler');

		if (ee()->form_validation->run() === FALSE)
		{
			ee()->view->set_message('issue', lang('compose_error'), lang('compose_error_desc'));

			return $this->compose();
		}

		$name = ee()->session->userdata('screen_name');

		// ee()->view->cp_page_title = lang('email_success');
		$debug_msg = '';

		switch ($mailtype)
		{
			case 'text':
				$text_fmt = 'none';
				$plaintext_alt = '';
				break;

			case 'markdown':
				$text_fmt = 'markdown';
				$mailtype = 'html';
				$plaintext_alt = $message;
				break;

			case 'html':
				// If we strip tags and it matches the message, then there was
				// not any HTML in it and we'll format for them.
				if ($message == strip_tags($message))
				{
					$text_fmt = 'xhtml';
				}
				else
				{
					$text_fmt = 'none';
				}
				break;
		}

		$subject = "${subject} (TEMPLATE) ";

		// Assign data for caching
		$cache_data = array(
			'cache_date'		=> ee()->localize->now,
			'total_sent'		=> 0,
			'from_name'	 		=> $name,
			'from_email'		=> $from,
			'recipient'			=> $recipient,
			'cc'				=> $cc,
			'bcc'				=> $bcc,
			'recipient_array'	=> array(),
			'subject'			=> $subject,
			'message'			=> $message,
			'mailtype'			=> $mailtype,
			'wordwrap'	  		=> $wordwrap,
			'text_fmt'			=> $text_fmt,
			'total_sent'		=> 0,
			'plaintext_alt'		=> $plaintext_alt,
			'attachments'		=> $this->attachments,
		);

		$email = ee('Model')->make('EmailCache', $cache_data);
		$email->save();

		// //  Send a single email
		// if (count($groups) == 0)
		// {
		// 	console_message("Sending one", __METHOD__);
		// 	$debug_msg = $this->deliverOneEmail($email, $recipient);
		// 	console_message($debug_msg, __METHOD__);
		// 	ee()->view->set_message('success', lang('email_sent_message'), $debug_msg, TRUE);
		// 	ee()->functions->redirect(
		// 		ee('CP/URL',EXT_SETTINGS_PATH.'/email:compose')
		// 	);
		// }

		// Get member group emails
		$member_groups = ee('Model')->get('MemberGroup', $groups)
			->with('Members')
			->all();

		$email_addresses = array();
		foreach ($member_groups as $group)
		{
			foreach ($group->getMembers() as $member)
			{
				$email_addresses[] = $member->email;
			}
		}

		if (empty($email_addresses) AND $recipient == '')
		{
			show_error(lang('no_email_matching_criteria'));
		}

		/** ----------------------------------------
		/**  Do we have any CCs or BCCs?
		/** ----------------------------------------*/

		//  If so, we'll send those separately first

		$total_sent = 0;

		if ($cc != '' OR $bcc != '')
		{
			$to = ($recipient == '') ? ee()->session->userdata['email'] : $recipient;
			$debug_msg = $this->deliverOneEmail($email, $to, empty($email_addresses));

			$total_sent = $email->total_sent;
		}
		else
		{
			// No CC/BCCs? Convert recipients to an array so we can include them in the email sending cycle

			if ($recipient != '')
			{
				foreach (explode(',', $recipient) as $address)
				{
					$address = trim($address);

					if ( ! empty($address))
					{
						$email_addresses[] = $address;
					}
				}
			}
		}

		//  Store email cache
		$email->recipient_array = $email_addresses;
		$email->setMemberGroups(ee('Model')->get('MemberGroup', $groups)->all());
		$email->save();
		$id = $email->cache_id;

		// insert data into Mm_cache
		// set cache_id 
		// set json content 


		// Is Batch Mode set?

		$batch_mode = bool_config_item('email_batchmode');
		$batch_size = (int) ee()->config->item('email_batch_size');

		if (count($email_addresses) <= $batch_size)
		{
			$batch_mode = FALSE;
		}

		//** ----------------------------------------
		//  If batch-mode is not set, send emails
		// ----------------------------------------*/

		if ($batch_mode == FALSE)
		{
			$total_sent = $this->deliverManyEmails($email);

			$debug_msg = ee()->email->print_debugger(array());

			$this->deleteAttachments($email); // Remove attachments now

			ee()->view->set_message('success', lang('total_emails_sent') . ' ' . $total_sent, $debug_msg, TRUE);
			ee()->functions->redirect(ee('CP/URL',EXT_SETTINGS_PATH.'/email:compose'));
		}

		if ($batch_size === 0)
		{
			show_error(lang('batch_size_is_zero'));
		}

		/** ----------------------------------------
		/**  Start Batch-Mode
		/** ----------------------------------------*/

			// ee()->functions->redirect(ee('CP/URL',EXT_SETTINGS_PATH.'/'.EXT_SHORT_NAME.'/email:compose'));
		ee()->view->set_refresh(ee('CP/URL',EXT_SETTINGS_PATH.'/email:batch/' . $email->cache_id)->compile(), 6, TRUE);

		ee('CP/Alert')->makeStandard('batchmode')
			->asWarning()
			->withTitle(lang('batchmode_ready_to_begin'))
			->addToBody(lang('batchmode_warning'))
			->defer();

		ee()->functions->redirect(ee('CP/URL',EXT_SETTINGS_PATH.'/email:compose'));
	}

	/**
	 * Batch Email Send
	 *
	 * Sends email in batch mode
	 *
	 * @param int $id	The cache_id to send
	 */
	public function batch($id)
	{
		ee()->load->library('email');

		if (ee()->config->item('email_batchmode') != 'y')
		{
			show_error(lang('batchmode_disabled'));
		}

		if ( ! ctype_digit($id))
		{
			show_error(lang('problem_with_id'));
		}

		$email = ee('Model')->get('EmailCache', $id)->first();

		if (is_null($email))
		{
			show_error(lang('cache_data_missing'));
		}

		$start = $email->total_sent;

		$this->deliverManyEmails($email);

		if ($email->total_sent == count($email->recipient_array))
		{
			$debug_msg = ee()->email->print_debugger(array());

			$this->deleteAttachments($email); // Remove attachments now

			ee()->view->set_message('success', lang('total_emails_sent') . ' ' . $email->total_sent, $debug_msg, TRUE);
			ee()->functions->redirect(ee('CP/URL',EXT_SETTINGS_PATH.'/'.EXT_SHORT_NAME.'/email:compose'));
		}
		else
		{
			$stats = str_replace("%x", ($start + 1), lang('currently_sending_batch'));
			$stats = str_replace("%y", ($email->total_sent), $stats);

			$message = $stats.BR.BR.lang('emails_remaining').NBS.NBS.(count($email->recipient_array)-$email->total_sent);

			ee()->view->set_refresh(ee('CP/URL',EXT_SETTINGS_PATH.'/'.EXT_SHORT_NAME.'/email:batch/' . $email->cache_id)->compile(), 6, TRUE);

			ee('CP/Alert')->makeStandard('batchmode')
				->asWarning()
				->withTitle($message)
				->addToBody(lang('batchmode_warning'))
				->defer();

			ee()->functions->redirect(is_valid_uri);
		}
	}

	/**
	 * Fetches an email from the cache and presents it to the user for re-sending
	 *
	 * @param int $id	The cache_id to send
	 */
	public function resend($id)
	{
		if ( ! ctype_digit($id))
		{
			show_error(lang('problem_with_id'));
		}

		$caches = ee('Model')->get('EmailCache', $id)
			->with('MemberGroups')
			->all();

		$email = $caches[0];

		if (is_null($email))
		{
			show_error(lang('cache_data_missing'));
		}

		console_message($email->subject, __METHOD__);
		return $this->compose($email);
	}

	/**
	 * Sends a single email handling errors
	 *
	 * @param	obj		$email	An EmailCache object
	 * @param	str		$to		An email address to send to
	 * @param	bool	$delete	Delete email attachments after send?
	 * @return	str				A response messge as a result of sending the email
	 */
	private function deliverOneEmail(EmailCache $email, $to, $delete = TRUE)
	{
		$error = FALSE;

		if ( ! $this->deliverEmail($email, $to, $email->cc, $email->bcc))
		{
			$error = TRUE;
		}

		if ($delete)
		{
			$this->deleteAttachments($email); // Remove attachments now
		}

		$debug_msg = ee()->email->print_debugger(array());

		if ($error == TRUE)
		{
			$email->delete();
			show_error(lang('error_sending_email').BR.BR.$debug_msg);
		}

		$total_sent = 0;

		foreach (array($to, $email->cc, $email->bcc) as $string)
		{
			if ($string != '')
			{
				$total_sent += substr_count($string, ',') + 1;
			}
		}

		// Save cache data
		$email->total_sent = $total_sent;
		$email->save();

		return $debug_msg;
	}

	/**
	 * Sends multiple emails handling errors
	 *
	 * @param	obj	$email	An EmailCache object
	 * @return	int			The number of emails sent
	 */
	private function deliverManyEmails(EmailCache $email)
	{
		$recipient_array = array_slice($email->recipient_array, $email->total_sent);
		$number_to_send = count($recipient_array);

		if ($number_to_send < 1)
		{
			return 0;
		}

		if (ee()->config->item('email_batchmode') == 'y')
		{
			$batch_size = (int) ee()->config->item('email_batch_size');

			if ($number_to_send > $batch_size)
			{
				$number_to_send = $batch_size;
			}
		}

		for ($x = 0; $x < $number_to_send; $x++)
		{
			$email_address = array_shift($recipient_array);

			if (isset($this->csv_lookup) AND count($this->csv_lookup) > 0){
				$tmp_message = $this->formatMessage($email);
				$tmp_plaintext = $email->plaintext_alt; 
				$record = $this->csv_lookup[$email_address];
				$tmp_message = strtr($email->message, $record);
				if ($email->mailtype == 'markdown')  $tmp_plaintext = $tmp_message;

				// standard 'First Last <email address> format (update: rejected by Php's FILTER_VALIDATE_EMAIL)
				// $to = "{$found['{{first_name}}']} {$found['{{last_name}}']}  <{$found['{{email}}']}>"; 
				$to = $record['{{email}}']; 
				$cache_data = array(
					'cache_date'		=> ee()->localize->now,
					'total_sent'		=> 0,
					'from_name'	 		=> $email->from_name,
					'from_email'		=> $email->from_email,
					'recipient'			=> $to,
					'cc'				=> $email->cc,
					'bcc'				=> $email->bcc,
					'recipient_array'	=> array(),
					'subject'			=> str_replace('(TEMPLATE) ', '', $email->subject),
					'message'			=> $tmp_message,
					'mailtype'			=> $email->mailtype,
					'wordwrap'	  		=> $email->wordwrap,
					'text_fmt'			=> $email->text_fmt,
					'total_sent'		=> 0,
					'plaintext_alt'		=> $tmp_message,
					'attachments'		=> $this->attachments,
				);

				$singleEmail = ee('Model')->make('EmailCache', $cache_data);
				$singleEmail->save();
				if ( ! $this->deliverEmail($singleEmail, $to))
				{
					$singleEmail->delete();

					$debug_msg = ee()->email->print_debugger(array());

					show_error(lang('error_sending_email').BR.BR.$debug_msg);
				}
				$singleEmail->total_sent++;
				$singleEmail->save();
			}else{				// Assign data for caching
				if ( ! $this->deliverEmail($email, $email_address))
				{
					$email->delete();

					$debug_msg = ee()->email->print_debugger(array());

					show_error(lang('error_sending_email').BR.BR.$debug_msg);
				}
			}
			$email->total_sent++;
		}

		$email->save();
		return $email->total_sent;
	}


	/**
	 * Delivers an email
	 *
	 * @param	obj	$email	An EmailCache object
	 * @param	str	$to		An email address to send to
	 * @param	str	$cc		An email address to cc
	 * @param	str	$bcc	An email address to bcc
	 * @return	bool		True on success; False on failure
	 */
	private function deliverEmail(EmailCache $email, $to, $cc = NULL, $bcc = NULL)
	{
		ee()->email->clear(TRUE);
		ee()->email->wordwrap  = $email->wordwrap;
		ee()->email->mailtype  = $email->mailtype;
		ee()->email->from($email->from_email, $email->from_name);
		ee()->email->to($to);

		if ( ! is_null($cc))
		{
			ee()->email->cc($email->cc);
		}

		if ( ! is_null($bcc))
		{
			ee()->email->bcc($email->bcc);
		}

		ee()->email->subject($this->censorSubject($email));
		ee()->email->message($this->formatMessage($email), $email->plaintext_alt);

 		foreach ($email->attachments as $attachment)
		{
			ee()->email->attach($attachment);
		}
		console_message(ee()->email->print_debugger(), __METHOD__);

		return ee()->email->send(FALSE);
	}


	/**
	 * Formats the message of an email based on the text format type
	 *
	 * @param	obj	$email	An EmailCache object
	 * @return	string		The formatted message
	 */
	private function formatMessage(EmailCache $email)
	{
		$message = $email->message;
		if ($email->text_fmt != 'none' && $email->text_fmt != '')
		{
			ee()->load->library('typography');
			ee()->typography->initialize(array(
				'bbencode_links' => FALSE,
				'parse_images'	=> FALSE,
				'parse_smileys'	=> FALSE
			));

			$message = ee()->typography->parse_type($email->message, array(
				'text_format'    => $email->text_fmt,
				'html_format'    => 'all',
				'auto_links'	 => 'n',
				'allow_img_url'  => 'y'
			));
		}
		return $message;
	}

	/**
	 * Censors the subject of an email if necessary
	 *
	 * @param	obj	$email	An EmailCache object
	 * @return	string		The censored subject
	 */
	private function censorSubject(EmailCache $email)
	{
		console_message($email, __METHOD__);
		$subject = $email->subject;

		if (bool_config_item('enable_censoring'))
    	{
			$subject = (string) ee('Format')->make('Text', $subject)->censor();
		}

		return $subject;
	}

	/**
	 * View sent emails
	 */
	public static function sent()
	{
		if ( ! ee()->cp->allowed_group('can_send_cached_email'))
		{
			show_error(lang('not_allowed_to_email_cache'));
		}

		if (ee()->input->post('bulk_action') == 'remove')
		{
			ee('Model')->get('EmailCache', ee()->input->get_post('selection'))->all()->delete();
			ee()->view->set_message('success', lang('emails_removed'), '');
		}

		$table = ee('CP/Table');
		$table->setColumns(
			array(
				'subject',
				'date',
				'total_sent',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$table->setNoResultsText('no_cached_emails', 'create_new_email', ee('CP/URL',EXT_SETTINGS_PATH.'/email:compose'));

		$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
		$page = ($page > 0) ? $page : 1;

		$offset = ($page - 1) * 50; // Offset is 0 indexed

		$count = 0;

		$emails = ee('Model')->get('EmailCache');

		$search = $table->search;
		if ( ! empty($search))
		{
			$emails = $emails->filterGroup()
				               ->filter('subject', 'LIKE', '%' . $table->search . '%')
				               ->orFilter('message', 'LIKE', '%' . $table->search . '%')
				               ->orFilter('from_name', 'LIKE', '%' . $table->search . '%')
				               ->orFilter('from_email', 'LIKE', '%' . $table->search . '%')
				               ->orFilter('recipient', 'LIKE', '%' . $table->search . '%')
				               ->orFilter('cc', 'LIKE', '%' . $table->search . '%')
				               ->orFilter('bcc', 'LIKE', '%' . $table->search . '%')
						     ->endFilterGroup();
		}

		$count = $emails->count();

		$sort_map = array(
			'date' => 'cache_date',
			'subject' => 'subject',
			'status' => 'status',
			'total_sent' => 'total_sent',
		);

		$emails = $emails->order($sort_map[$table->sort_col], $table->sort_dir)
			->limit(20)
			->offset($offset)
			->all();
		// $emails = $emails->all();
		
		$vars['emails'] = array();
		$data = array();
		foreach ($emails as $email)
		{
			// Prepare the $email object for use in the modal
			$email->text_fmt = ($email->text_fmt != 'none') ?: 'br'; // Some HTML formatting for plain text
			// $email->subject = htmlentities($this->censorSubject($email), ENT_QUOTES, 'UTF-8');


			$data[] = array(
				$email->subject,
				ee()->localize->human_time($email->cache_date->format('U')),
				$email->total_sent,
				array('toolbar_items' => array(
					'view' => array(
						'title' => lang('view_email'),
						'href' => '',
						'id' => $email->cache_id,
						'rel' => 'modal-email-' . $email->cache_id,
						'class' => 'm-link'
					),
					'sync' => array(
						'title' => lang('resend'),
						'href' => ee('CP/URL',EXT_SETTINGS_PATH.'/email:resend/'. $email->cache_id)
					))
				),
				array(
					'name'  => 'selection[]',
					'value' => $email->cache_id,
					'data'	=> array(
						'confirm' => lang('view_email_cache') . ': <b>' . $email->subject . '(x' . $email->total_sent . ')</b>'
					)
				)
			);

			ee()->load->library('typography');
			ee()->typography->initialize(array(
				'bbencode_links' => FALSE,
				'parse_images'	=> FALSE,
				'parse_smileys'	=> FALSE
			));

			$email->message = ee()->typography->parse_type($email->message, array(
				'text_format'    => ($email->text_fmt == 'markdown') ? 'markdown' : 'xhtml',
				'html_format'    => 'all',
				'auto_links'	 => 'n',
				'allow_img_url'  => 'y'
			));

			$vars['emails'][] = $email;
		}

		console_message($vars, __METHOD__);
		$table->setData($data);

		$base_url = ee('CP/URL',EXT_SETTINGS_PATH.'/email:sent');
		$vars['table'] = $table->viewData($base_url);

		$vars['pagination'] = ee('CP/Pagination', $count)
			->currentPage($page)
			->render($vars['table']['base_url']);

		// Set search results heading
		if ( ! empty($vars['table']['search']))
		{
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$vars['table']['total_rows'],
				htmlspecialchars($vars['table']['search'], ENT_QUOTES, 'UTF-8')
			);
		}

		$vars['cp_page_title'] = lang('view_email_cache');
		ee()->javascript->set_global('lang.remove_confirm', lang('view_email_cache') . ': <b>### ' . lang('emails') . '</b>');

		// ee()->cp->add_js_script(array( 'file' => array('cp/confirm_remove'),));
		$vars['base_url'] = $base_url;
		return $vars;
	}

	/**
	 * Check for recipients
	 *
	 * An internal validation function for callbacks
	 *
	 * @param	string
	 * @return	bool
	 */
	public function _check_for_recipients($str)
	{
		console_message($str, __METHOD__);
		if ( ! $str && ee()->input->post('total_gl_recipients') < 1)
		{
			ee()->form_validation->set_message('_check_for_recipients', lang('required'));
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Attachment Handler
	 *
	 * Used to manage and validate attachments. Must remain public,
	 * it's a form validation callback.
	 *
	 * @return	bool
	 */
	public function _attachment_handler()
	{
		// File Attachments?
		if ( ! isset($_FILES['attachment']['name']) OR empty($_FILES['attachment']['name']))
		{
			return TRUE;
		}

		ee()->load->library('upload');
		ee()->upload->initialize(array(
			'allowed_types'	=> '*',
			'use_temp_dir'	=> TRUE
		));

		if ( ! ee()->upload->do_upload('attachment'))
		{
			ee()->form_validation->set_message('_attachment_handler', lang('attachment_problem'));
			return FALSE;
		}

		$data = ee()->upload->data();

		$this->attachments[] = $data['full_path'];

		return TRUE;
	}

	/**
	 * Delete Attachments
	 */
	private function deleteAttachments($email)
	{
		console_message($email, __METHOD__);
		foreach ($email->attachments as $file)
		{
			if (file_exists($file))
			{
				unlink($file);
			}
		}

		$email->attachments = array();
		$email->save();
	}

}
// END CLASS
// EOF
