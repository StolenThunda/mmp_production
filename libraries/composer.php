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
/**
 * Copy of Communicate Controller
 */
class Composer {

	private $attachments = array();
	private $csv_lookup = array();
	private $csv_email_column = "{{email}}";

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
		$this->debug = FALSE;
	}

	/**
	 * compose
	 *
	 * @param	obj	$email	An EmailCache object for use in re-populating the form (see: resend())
	 */
	public function compose(EmailCache $email = NULL)
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
		);

		$vars['mailtype_options'] = array(
			'text'		=> lang('plain_text'),
			'markdown'	=> lang('markdown'),
			'html'		=> lang('html')
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
		}
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
			form_button('convert_csv','Convert CSV','class="btn"')
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
			),
				'recipient_options' => array(
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
							'file_recipient' => array(
								'type' => 'file',
								'content' => ee('CP/FilePicker')
									->make()
									->getLink('Choose File')
									->withValueTarget('files')
									->render()
							),
							'dump_vars' => array(
								'type' => 'hidden',
								'content' => form_button('btnDump','Dump Hidden Values', 'class="btn" onClick="dumpHiddenVals()"')
							),
							'csv_object' => array(
								'type' => 'hidden',
								'value' => ''
							),
							'mailKey' => array(
								'type' => 'hidden',
								'value' => ''
							),
						)
					),
					array(
						'title' => 'csv_recipient',
						'desc' => 'csv_recipient_desc',
						'fields' => array(
							'csv_errors' => array(
								'type' => 'html',
								'content' => '<span id="csv_errors"></span>'
							),
							'csv_recipient' => array(
								'type' => 'html',
								'content' => implode('<br />', $csvHTML)
							),
							
							'csv_reset' => array(
								'type' => 'html',
								'content' => form_button('btnReset','Reset CSV Data', 'class="btn"')
							),
						)
					),
					array(
						'title' => 'primary_recipients',
						'desc' => 'primary_recipients_desc',
						'fields' => array(
							'recipient' => array(
								'type' => 'text',
								'value' => $default['recipient']
							),'csv_content' => array(
								'type' => 'html',
								'content' => '<table class=\'fixed_header\' id=\'csv_content\'></table>'
							)
						)
					),
				),
			'compose_email_detail' =>array(
				
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
				
			'other_recipient_options' => array(	
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
			$vars['sections']['other_recipient_options'][] = array(
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
		// $vars['categories'] = array_keys($this->sidebar_options);
		$vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/email/send');
		$vars['save_btn_text'] = lang('compose_send_email');
		$vars['save_btn_text_working'] = lang('compose_sending_email');
		ee()->cp->add_to_foot(link_tag(array(
			'href' => 'http://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css',
			'rel' => 'stylesheet',
			'type' => 'text/css',
		)));
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

		// $email = ee('Model')->get(EXT_SHORT_NAME.':', $cache_data);
		$email = ee('Model')->get('EmailCache', $cache_data);
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
		
		if (isset($mailKey)) $this->csv_email_column = $mailKey;
		// create lookup array for easy email lookup
		if (isset($csv_object) AND $csv_object !== "" AND isset($mailKey)){
			$rows =  json_decode($csv_object, TRUE);
			foreach ($rows as $row){
				$this->csv_lookup[trim($row[$mailKey])] = $row;
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
		console_message($cache_data, __METHOD__);
		$email = ee('Model')->make('EmailCache', $cache_data);
		$email->save();

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
			ee()->functions->redirect(ee('CP/URL',EXT_SETTINGS_PATH.'/email/compose'));
		}

		if ($batch_size === 0)
		{
			show_error(lang('batch_size_is_zero'));
		}

		/** ----------------------------------------
		**  Start Batch-Mode
		** ----------------------------------------*/

			// ee()->functions->redirect(ee('CP/URL',EXT_SETTINGS_PATH.'/'.EXT_SHORT_NAME.'/email:compose'));
		ee()->view->set_refresh(ee('CP/URL',EXT_SETTINGS_PATH.'/email/batch/' . $email->cache_id)->compile(), 6, TRUE);

		ee('CP/Alert')->makeStandard('batchmode')
			->asWarning()
			->withTitle(lang('batchmode_ready_to_begin'))
			->addToBody(lang('batchmode_warning'))
			->defer();

		ee()->functions->redirect(ee('CP/URL',EXT_SETTINGS_PATH.'/email/compose'));
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

		$email =ee('Model')->get(EXT_SHORT_NAME.':', $id)->first();

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

		$caches = ee('Model')->get(EXT_SHORT_NAME.':EmailCachePlus', $id)
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
			$this->_removeMail($email);
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
	 * * @param	obj	$email	An EmailCache object
	 * @return	int			The number of emails sent
	 */
	private function deliverManyEmails(EmailCache $email)
	{
		$recipient_array = array_slice($email->recipient_array, $email->total_sent);
		$number_to_send = count($recipient_array);
		$csv_lookup_loaded = (count($this->csv_lookup) > 0);

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
		
		$formatted_message = $this->formatMessage($email);
		for ($x = 0; $x < $number_to_send; $x++)
		{
			$email_address = array_shift($recipient_array);

			if ($csv_lookup_loaded){
				$tmp_plaintext = $email->plaintext_alt; 
				$record = $this->csv_lookup[$email_address];
				// $formatted_message = strtr($email->message, $record);
				console_message($record, __METHOD__);

				// standard 'First Last <email address> format (update: rejected by Php's FILTER_VALIDATE_EMAIL)
				$to = "{$record['{{first_name}}']} {$record['{{last_name}}']}  <{$record['{{email}}']}>"; 

				// $to = $record[$this->csv_email_column]; 
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
					'message'			=> $formatted_message,
					'mailtype'			=> $email->mailtype,
					'wordwrap'	  		=> $email->wordwrap,
					'text_fmt'			=> $email->text_fmt,
					'total_sent'		=> 0,
					'plaintext_alt'		=> $email->message,
					'attachments'		=> $this->attachments,
				);

				$singleEmail = ee('Model')->make('EmailCache', $cache_data);
				$singleEmail->save();
				// ee()->typography->initialize(array(
				// 	'bbencode_links' => FALSE,
				// 	'parse_images'	=> FALSE,
				// 	'parse_smileys'	=> FALSE
				// ));
				$cache_data['text'] = $formatted_message;
				$cache_data['lookup'] = $record;
				$cache_data['html'] = ee()->typography->parse_type($email->message, array(
					'text_format'    => ($email->text_fmt == 'markdown') ? 'markdown' : 'xhtml',
					'html_format'    => 'all',
					'auto_links'	 => 'n',
					'allow_img_url'  => 'y'
				));
				console_message($cache_data, __METHOD__);
				if ($this->email_send($cache_data)){
					$singleEmail->total_sent++;
					$singleEmail->save();	
				}else{
					$this->_removeMail($email);
				}
			} else if( ! $this->deliverEmail($email, $email_address))
			{
				$this->_removeMail($email);
			}		
			$email->total_sent++;		
				
		}
		$email->save();
		return $email->total_sent;
	}
private function _removeMail(EmailCache $email){
		$email->delete();

		$debug_msg = ee()->email->print_debugger(array());
		console_message($debug_msg, __METHOD__);
		show_error(lang('error_sending_email').BR.BR.$debug_msg);
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
	 * @return	string		The  message
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

	function email_send($data)
	{	
		$settings = ee()->mail_svc->get_settings();
		$str_settings = json_encode(json_decode(json_encode($settings, JSON_PRETTY_PRINT)));
		if(empty($settings['service_order']))
		{
			return false;
		}
		
		ee()->lang->loadfile(EXT_SHORT_NAME);
		ee()->load->library('logger');
		
		$sent = false;
		$this->email_in = $data;
		unset($data);

		$this->email_out['lookup'] =  $this->email_in['lookup'];
		
		$this->email_in['finalbody'] = $this->email_in['message'];
		
		$this->email_out['text'] = $this->email_in['text'];

		
		if($this->debug == true)
		{
			console_message($this->email_in);
		}
		
		// Set X-Mailer
		$this->email_out['headers']['X-Mailer'] = APP_NAME .' (via '. EXT_NAME . ' ' . EXT_VERSION .')';

		// From (may include a name)
		$this->email_out['from'] = array(
			'name' 	=> $this->email_in['from_name']	,  
			'email' 	=> $this->email_in['from_email']	
		);  
		
		// Reply-To (may include a name)
		if(!empty($this->email_in['headers']['Reply-To']))
		{
			$this->email_out['reply-to'] = $this->_name_and_email($this->email_in['headers']['Reply-To']);
		}
		
		// To (email-only)
		$this->email_out['to'] = array($this->email_in['recipient']);
		
		// Cc (email-only)
		if(!empty($this->email_in['cc_array']))
		{
			$this->email_out['cc'] = array();
			foreach($this->email_in['cc_array'] as $cc_email)
			{
				if(!empty($cc_email))
				{
					$this->email_out['cc'][] = $cc_email;
				}
			}
		}
		elseif(!empty($this->email_in['cc']))
		{
			$this->email_out['cc'] = $this->email_in['cc'];
		}

		// Bcc (email-only)
		if(!empty($this->email_in['bcc_array']))
		{
			$this->email_out['bcc'] = array();
			foreach($this->email_in['bcc_array'] as $bcc_email)
			{
				if(!empty($bcc_email))
				{
					$this->email_out['bcc'][] = $bcc_email;
				}
			}
		}
		elseif(!empty($this->email_in['headers']['Bcc']))
		{
			$this->email_out['bcc'] = $this->_recipient_array($this->email_in['headers']['Bcc']);
		}
		
		// Subject	
		$subject = '';
		if(!empty($this->email_in['subject']))
		{
			$subject = $this->email_in['subject'];
		}
		elseif(!empty($this->email_in['headers']['Subject']))
		{
			$subject = $this->email_in['headers']['Subject'];
		}
		$this->email_out['subject'] = (strpos($subject, '?Q?') !== false) ? $this->_decode_q($subject) : $subject;
		
		
		// Set HTML/Text and attachments
		// $this->_body_and_attachments();
		$this->email_out['html'] = $this->email_in['html'];
		
		if($this->debug == true)
		{
			console_message($this->email_out);
		}		

		foreach($settings['service_order'] as $service)
		{
			// console_message($service, __METHOD__);
			if(!empty($settings[$service.'_active']) && $settings[$service.'_active'] == 'y')
			{
				$missing_credentials = true;
				console_message($service, __METHOD__);
				switch($service)
				{
					case 'mailgun':
						if(!empty($settings['mailgun_api_key']) && !empty($settings['mailgun_domain']))
						{
							$sent = $this->_send_mailgun($settings['mailgun_api_key'], $settings['mailgun_domain']);
							$missing_credentials = false;
						}
						break;				
					case 'mandrill':
						$key = (!empty($settings['mandrill_api_key'])) ? $settings['mandrill_api_key'] : "";
						if (!empty($settings['mandrill_test_api_key'])){ // && $key == ""){
							$key = $settings['mandrill_test_api_key'];
						}
						$log_message = sprintf(lang('using_alt_credentials'), $service, $key, $service, $str_settings);
						ee()->logger->developer($log_message);
						if($key !== ""){
							$subaccount = (!empty($settings['mandrill_subaccount']) ? $settings['mandrill_subaccount'] : '');
							$sent = $this->_send_mandrill($key, $subaccount);
							console_message($log_message, __METHOD__);
							// ee()->session->set_flashdata(array('message_error' => $log_message));
							$missing_credentials = false;
						}
						break;
					case 'postageapp':
						if(!empty($settings['postageapp_api_key']))
						{
							$sent = $this->_send_postageapp($settings['postageapp_api_key']);
							$missing_credentials = false;
						}						
						break;	
					case 'postmark':
						if(!empty($settings['postmark_api_key']))
						{
							$sent = $this->_send_postmark($settings['postmark_api_key']);
							$missing_credentials = false;
						}						
						break;				
					case 'sendgrid':
						if(!empty($settings['sendgrid_api_key']))
						{
							$sent = $this->_send_sendgrid($settings['sendgrid_api_key']);
							$missing_credentials = false;
						}
						break;
					case 'sparkpost':
						if(!empty($settings['sparkpost_api_key']))
						{
							$sent = $this->_send_sparkpost($settings['sparkpost_api_key']);
							$missing_credentials = false;
						}
						break;
				}
				
				if($missing_credentials == true)
				{
					ee()->logger->developer(sprintf(lang('missing_service_credentials'), $service));
				}
				elseif($sent == false)
				{
					ee()->logger->developer(sprintf(lang('could_not_deliver'), $service));
				}
			}
			console_message($sent, __METHOD__);
			if($sent == true)
			{
				ee()->extensions->end_script = true;
				return true;
			}		
		}
		
		return false;
				  
	}
	
	
	/**
		Sending methods for each of our services follow.
	**/

	function _send_mandrill($api_key, $subaccount)
	{
		$content = array(
			'key' => $api_key,
			'async' => TRUE,
			'message' => $this->email_out
		);
		console_message($content, __METHOD__);
		if(!empty($subaccount))
		{
			$content['message']['subaccount'] = $subaccount;
		}
		
		$content['message']['from_email'] = $content['message']['from']['email'];
		if(!empty($content['message']['from']['name']))
		{
			$content['message']['from_name'] = $content['message']['from']['name'];
		}
		unset($content['message']['from']);
		
		$mandrill_to = array('email' => $content['message']['to']);
		foreach($content['message']['to'] as $to)
		{
			$mandrill_to[] = array_merge($this->_name_and_email($to), array('type' => 'to'));
		}
		
		if(!empty($content['message']['cc']))
		{
			foreach($content['message']['cc'] as $to)
			{
				$mandrill_to[] = array_merge($this->_name_and_email($to), array('type' => 'cc'));
			}
			unset($content['message']['cc']);
		}
				
		if(!empty($content['message']['reply-to']))
		{
			$content['message']['headers']['Reply-To'] = $this->_recipient_str($content['message']['reply-to'], true);
		}
		unset($content['message']['reply-to']);

		
		if(!empty($content['message']['bcc']))
		{
			foreach($content['message']['bcc'] as $to)
			{
				$mandrill_to[] = array_merge($this->_name_and_email($to), array('type' => 'bcc'));
			}
		}
		unset($content['message']['bcc']);
		
		$content['message']['to'] = $mandrill_to;

		$content['message']['merge_language'] = 'handlebars';

		$content['message']['track_opens'] = TRUE;

		$content['message']['tags'] = array(EXT_NAME . " " . EXT_VERSION);

		$merge_vars = array(
			array(
				'rcpt' => $content['message']['to'][0]['email'],
				'vars' => $this->_mandrill_lookup_to_merge($content['message']['lookup'])
			)
		);
		unset($content['message']['lookup']);

		// $content['message']['auto_html'] = FALSE;
		// $content['message']['auto_text'] = TRUE;
		$content['message']['global_merge_vars'] = $merge_vars;
						
		$headers = array(
	    	'Accept: application/json',
			'Content-Type: application/json',
		);
		
		if(ee()->extensions->active_hook('pre_send'))
		{
			$content = ee()->extensions->call('pre_send', 'mandrill', $content);
		}
		
		// Did someone set a template? Then we need a different API method.
		$method = (!empty($content['template_name']) && !empty($content['template_content'])) ? 'send-template' : 'send';
		$content = json_encode($content);
				
		console_message($content,__METHOD__);	
		ee()->logger->developer($content);
		return $this->_curl_request('https://mandrillapp.com/api/1.0/messages/'.$method.'.json', $headers, $content);
	}
	
	function _mandrill_lookup_to_merge($lookup){
		$merge_vars = array();
		foreach(array_keys($lookup) as $key){
			$merge_vars[] = array(
				'name' => str_replace(array('{{','}}'), '', $key),
				'content' => $lookup[$key]
			);
		}
		return $merge_vars;
	}	
	
	/**
		Ultimately sends the email to each server.
	**/	
	function _curl_request($server, $headers = array(), $content, $htpw = null)
	{	
		$ch = curl_init($server);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, 1);
	    // Convert @ fields to CURLFile if available
	    if(is_array($content) && class_exists('CURLFile'))
	    {
		    foreach($content as $key => $value)
		    {
		        if(strpos($value, '@') === 0)
		        {
		            $filename = ltrim($value, '@');
		            $content[$key] = new CURLFile($filename);
		        }
		    }
		}
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		if(!empty($headers))
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		if(!empty($htpw))
		{
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $htpw);
		}
		
		$status = curl_exec($ch);
		console_message($status,__METHOD__);
		$curl_error = curl_error($ch);
		console_message($curl_error,__METHOD__);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		return ($http_code != 200) ? false : true;
	}	
	

	/**
		Remove the Q encoding from our subject line
	**/
	function _decode_q($subject)
	{
	    $r = '';
	    $lines = preg_split('/['.$this->email_crlf.']+/', $subject); // split multi-line subjects
		foreach($lines as $line)
	    { 
	        $str = '';
	        // $line = str_replace('=9', '', $line); // Replace encoded tabs which ratch the decoding
	        $parts = imap_mime_header_decode(trim($line)); // split and decode by charset
	        foreach($parts as $part)
	        {
	            $str .= $part->text; // append sub-parts of line together
	        }
	        $r .= $str; // append to whole subject
	    }
	    
	    return $r;
	    // return utf8_encode($r);
	}
	
	
	/**
		Breaks the PITA MIME message we receive into its constituent parts
	**/
	function _body_and_attachments()
	{
		console_message($this->protocol, __METHOD__);
		if($this->protocol == 'mail')
		{
			// The 'mail' protocol sets Content-Type in the headers
			if(strpos($this->email_in['header_str'], "Content-Type: text/plain") !== false)
			{	
				$this->email_out['text'] = $this->email_in['finalbody'];
			}
			elseif(strpos($this->email_in['header_str'], "Content-Type: text/html") !== false)
			{
				$this->email_out['html'] = $this->email_in['finalbody'];
			}
			else
			{
				preg_match('/Content-Type: multipart\/[^;]+;\s*boundary="([^"]+)"/i', $this->email_in['header_str'], $matches);
			}
		}	
		else
		{
			// SMTP and sendmail will set Content-Type in the body
			if(stripos($this->email_in['finalbody'], "Content-Type: text/plain") === 0)
			{	
				$this->email_out['text'] = $this->_clean_chunk($this->email_in['finalbody']);
			}
			elseif(stripos($this->email_in['finalbody'], "Content-Type: text/html") === 0)
			{
				$this->email_out['html'] = $this->_clean_chunk($this->email_in['finalbody']);
			}
			else
			{
				preg_match('/^Content-Type: multipart\/[^;]+;\s*boundary="([^"]+)"/i', $this->email_in['finalbody'], $matches);
			}
		}	
		
		// Extract content and attachments from multipart messages
		if(!empty($matches) && !empty($matches[1]))
		{
			$boundary = $matches[1];
			$chunks = explode('--' . $boundary, $this->email_in['finalbody']);
			foreach($chunks as $chunk)
			{
				if(stristr($chunk, "Content-Type: text/plain") !== false)
				{
					$this->email_out['text'] = $this->_clean_chunk($chunk);
				}
				
				if(stristr($chunk, "Content-Type: text/html") !== false)
				{
					$this->email_out['html'] = $this->_clean_chunk($chunk);
				}
				
				// Attachments
				if(stristr($chunk, "Content-Disposition: attachment") !== false)
				{
					preg_match('/Content-Type: (.*?); name=["|\'](.*?)["|\']/is', $chunk, $attachment_matches);
					if(!empty($attachment_matches))
					{
						if(!empty($attachment_matches[1]))
						{
							$type = $attachment_matches[1];
						}
						if(!empty($attachment_matches[2]))
						{
							$name = $attachment_matches[2];
						}
						$attachment = array(
							'type' => trim($type),
							'name' => trim($name),
							'content' => $this->_clean_chunk($chunk)
						);
						$this->email_out['attachments'][] = $attachment;
					}
				}
				
				if(stristr($chunk, "Content-Type: multipart") !== false)
				{
					// Another multipart chunk - contains the HTML and Text messages, here because we also have attachments
					preg_match('/Content-Type: multipart\/[^;]+;\s*boundary="([^"]+)"/i', $chunk, $inner_matches);
					if(!empty($inner_matches) && !empty($inner_matches[1]))
					{
						$inner_boundary = $inner_matches[1];
						$inner_chunks = explode('--' . $inner_boundary, $chunk);
						foreach($inner_chunks as $inner_chunk)
						{
							if(stristr($inner_chunk, "Content-Type: text/plain") !== false)
							{
								$this->email_out['text'] = $this->_clean_chunk($inner_chunk);
							}
							
							if(stristr($inner_chunk, "Content-Type: text/html") !== false)
							{
								$this->email_out['html'] = $this->_clean_chunk($inner_chunk);
							}
						}
					}
				}
			}
		}
		
		if(!empty($this->email_out['html']))
		{
			// HTML emails will have been run through quoted_printable_encode
			$this->email_out['html'] = quoted_printable_decode($this->email_out['html']);
		}
	}
	

	/**
		Explodes a string which contains either a name and email address or just an email address into an array
	**/
	function _name_and_email($str)
	{
		$r = array(
			'name' => '',
			'email' => ''
		);
		
		$str = str_replace('"', '', $str);
		if(preg_match('/<([^>]+)>/', $str, $email_matches))
		{
			$r['email'] = trim($email_matches[1]);
			$str = trim(preg_replace('/<([^>]+)>/', '', $str));
			if(!empty($str) && $str != $r['email'])
			{
				$r['name'] = utf8_encode($str);
			}
		}
		else
		{
			$r['email'] = trim($str);
		}
		return $r;
	}
	
	/**
		Explodes a comma-delimited string of email addresses into an array
	**/	
	function _recipient_array($recipient_str)
	{
		$recipients = explode(',', $recipient_str);
		$r = array();
		foreach($recipients as $recipient)
		{
			$r[] = trim($recipient);
		}
		return $r;
	}
	
	/**
		Implodes an array of email addresses and names into a comma-delimited string
	**/		
	function _recipient_str($recipient_array, $singular = false)
	{
		if($singular == true)
		{
			if(empty($recipient_array['name']))
			{
				return $recipient_array['email'];
			}
			else
			{
				return $recipient_array['name'].' <'.$recipient_array['email'].'>';
			}
		}
		$r = array();
		foreach($recipient_array as $k => $recipient)
		{
			if(!is_array($recipient))
			{
				$r[] = $recipient;
			}
			else
			{
				if(empty($recipient['name']))
				{
					$r[] = $recipient['email'];
				}
				else
				{
					$r[] = $recipient['name'].' <'.$recipient['email'].'>';
				}
			}
		}
		return implode(',', $r);
	}
	
	/**
		Removes cruft from a multipart message chunk
	**/		
	function _clean_chunk($chunk)
	{
		return trim(preg_replace("/Content-(Type|ID|Disposition|Transfer-Encoding):.*?".NL."/is", "", $chunk));
	}
	
	
	/**
		Writes our array of base64-encoded attachments into actual files in the tmp directory
	**/		
	function _write_attachments()
	{
		$r = array();
		ee()->load->helper('file');
    	foreach($this->email_out['attachments'] as $attachment)
    	{
    		if(write_file(realpath(sys_get_temp_dir()).'/'.$attachment['name'], base64_decode($attachment['content'])))
    		{
    			$r[$attachment['name']] = realpath(sys_get_temp_dir()).'/'.$attachment['name'];
    		}
    	}
    	return $r;
	}
	
	/**
		Translates a multi-dimensional array into the odd kind of array expected by cURL post
	**/		
	function _http_build_post($arrays, &$new = array(), $prefix = null)
	{	
	    foreach($arrays as $key => $value)
	    {
		    $k = isset( $prefix ) ? $prefix . '[' . $key . ']' : $key;
	        if(is_array($value))
	        {
	            $this->_http_build_post($value, $new, $k);
	        }
	        else
	        {
	            $new[$k] = $value;
	        }
	    }
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



		$table = ee('CP/Table', array('sort_col' => 'date', 'sort_dir' => 'desc'));
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

		$table->setNoResultsText('no_cached_emails', 'create_new_email', ee('CP/URL',EXT_SETTINGS_PATH.'/email/compose'));

		$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
		$page = ($page > 0) ? $page : 1;

		$offset = ($page - 1) * 50; // Offset is 0 indexed

		$count = 0;

		// $emails =ee('Model')->get(EXT_SHORT_NAME.':');
		$emails =ee('Model')->get('EmailCache');

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

		console_message($count, __METHOD__);
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
						'href' => ee('CP/URL',EXT_SETTINGS_PATH.'/email/resend/'. $email->cache_id)
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

		$base_url = ee('CP/URL',EXT_SETTINGS_PATH.'/email/sent');
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
		$vars['cp_page_title'] = lang('view_email_cache');
		ee()->javascript->set_global('lang.remove_confirm', lang('view_email_cache') . ': <b>### ' . lang('emails') . '</b>');
		$vars['current_service'] = __FUNCTION__;
		$vars['save_btn_text'] = "";
		$vars['save_btn_text_working'] = "";
		$vars['sections'] = array();

		console_message($vars, __METHOD__);
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