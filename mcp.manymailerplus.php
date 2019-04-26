<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Manymailerplus_mcp 
{
	private $version = EXT_VERSION;
	private $attachments = array();
	private $csv_lookup = array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		$CI = ee();
		$this->debug = FALSE;
		ee()->extensions->end_script = TRUE;
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
		ee()->load->helper('debug');
		ee()->load->helper('html');
		ee()->load->library('services_module', null, 'mail_svc');
		ee()->load->library('composer', null, 'mail_funcs');
		$this->services = ee()->config->item('services', 'services'); 
		$this->sidebar_loaded = ee()->config->load('sidebar', TRUE, TRUE);
		$this->sidebar_options = ee()->config->item('options', 'sidebar');
		$this->_update_sidebar_options(array_keys($this->services));

		if (!$this->sidebar_loaded)
		{
			//render page to show errors
			$vars = array(
				'base_url' => ee('CP/URL',EXT_SETTINGS_PATH),
				'cp_page_title' => lang(EXT_SHORT_NAME),
				'save_btn_text' => 'btn_save_settings',
				'sections' 	=> array(),
				'save_btn_text_working' => 'btn_saving',
			);
			return ee('View')->make(EXT_SHORT_NAME.':compose_view')->render($vars);
		}else{
			$this->makeSidebar();
		}
		
	}

	public function makeSidebar(){
		$this->sidebar = ee('CP/Sidebar')->make();
		foreach(array_keys($this->sidebar_options) as $category){
			$left_nav = $this->sidebar->addHeader(lang("{$category}_title"), ee('CP/URL',EXT_SETTINGS_PATH.'/'.$category));
			if(isset($this->sidebar_options[$category]['links']) AND count($this->sidebar_options[$category]['links']) > 0){
				$list_items = $left_nav->addBasicList();	
				foreach ($this->sidebar_options[$category]['links'] as $link_text) {
					$list_items->addItem(lang(''.$link_text.'_name'), ee('CP/URL',EXT_SETTINGS_PATH.'/'.$category.'/'.$link_text));
				}
			}
		}
	}
	
	function _update_sidebar_options($additional_links = array())
	{
		if (!empty($additional_links)){
			if (array_key_exists('services', $this->sidebar_options)){
				$this->sidebar_options['services']['links'] = array_unique(array_merge($this->sidebar_options['services']['links'], $additional_links));
			}
		}
	}
	
	public function index(){
		$vars['base_url'] = ee('CP/URL',EXT_SETTINGS_PATH.'/'.__FUNCTION__);
		$vars['cp_page_title'] = lang(__FUNCTION__. '_title');
		$vars['save_btn_text'] = "";
		$vars['save_btn_text_working'] = "";
		$vars['current_action'] = __FUNCTION__;
		$vars['categories'] = array_keys($this->sidebar_options);
		$vars['breadcrumb'] = ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile();
		$vars['sections'] = array();
		console_message($vars, __METHOD__);
		return  array(
				'body' => ee('View')->make(EXT_SHORT_NAME.':compose_view')->render($vars),
				'breadcrumb' => array(
					$vars['breadcrumb']=> EXT_NAME
				),
				'heading' => $vars['cp_page_title']
			);
	}

	function email($func = ""){
		$breadcrumbs = array(
			ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile() => EXT_NAME,
			ee('CP/URL')->make(EXT_SETTINGS_PATH .'/email')->compile() => lang('email_title')
		);
		switch ($func) {
			case 'compose':
			case 'send':
			case 'sent':
				$vars = ee()->mail_funcs->{$func}();
				break;
			case 'resend':
			case 'batch':
				$id = ee()->uri->segment(7, 0);
				return ee()->mail_funcs->{$func}($id);
				break;
			default:
				array_pop($breadcrumbs);
				$vars['base_url'] = ee('CP/URL',EXT_SETTINGS_PATH.'/email');
				$vars['cp_page_title'] = lang('email_title');
				$vars['save_btn_text'] = "";
				$vars['save_btn_text_working'] = "";
				$vars['current_action'] = 'email';
				$vars['sections'] = array();
		}
		$vars['categories'] = array_keys($this->sidebar_options);
		console_message($vars, __METHOD__);
		return array(
			'body' => ee('View')->make(EXT_SHORT_NAME.':compose_view')->render($vars),
			'breadcrumb' => $breadcrumbs,
			'heading' => $vars['cp_page_title']
			);
	}

	function services($func = ""){
		switch ($func) {
			case 'list':
				return ee()->mail_svc->get_settings();
				break;
			case 'save':
				return ee()->mail_svc->save_settings();
				break;
			default:
				$vars =  ee()->mail_svc->settings_form(array());
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
		ee()->load->library('services_module', null, 'mail_svc');
		for ($x = 0; $x < $number_to_send; $x++)
		{
			$email_address = array_shift($recipient_array);

			if ($csv_lookup_loaded){
				$tmp_message = $this->formatMessage($email);
				$tmp_plaintext = $email->plaintext_alt; 
				$record = $this->csv_lookup[$email_address];
				$tmp_message = strtr($email->message, $record);
				if ($email->mailtype == 'markdown')  $tmp_plaintext = $tmp_message;
				console_message($record, __METHOD__);
				// standard 'First Last <email address> format (update: rejected by Php's FILTER_VALIDATE_EMAIL)
				//$to = "{$record['{{first_name}}']} {$record['{{last_name}}']}  <{$record['{{email}}']}>"; 
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
				ee()->typography->initialize(array(
					'bbencode_links' => FALSE,
					'parse_images'	=> FALSE,
					'parse_smileys'	=> FALSE
				));
	
				$cache_data['html'] = ee()->typography->parse_type($cache_data['message'], array(
					'text_format'    => ($email->text_fmt == 'markdown') ? 'markdown' : 'xhtml',
					'html_format'    => 'all',
					'auto_links'	 => 'n',
					'allow_img_url'  => 'y'
				));
				if (ee()->mail_svc->email_send($cache_data)){
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
		$vars['base_url'] = $base_url;
		$vars['save_btn_text'] = "";
		$vars['save_btn_text_working'] = "";
		$vars['sections'] = array();
		$vars['breadcrumb'] = ee('CP/URL')->make(EXT_SETTINGS_PATH.'/email/sent')->compile();
		$vars['active_service_names'] = ee()->mail_svc->getActiveServiceNames();
		$vars['sidebar'] = $this->sidebar_options;		
		// $this->_update_sidebar_options(array_keys($vars['services']) );

		console_message($vars, __METHOD__);

		return array(
			'body' => ee('View')->make(EXT_SHORT_NAME.':compose_view')->render($vars),
			'breadcrumb' => $service_vars['bc'],
			'heading' => $vars['cp_page_title']
		);
	}
}
// END CLASS