
<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
    This file is part of ManyMailer add-on for ExpressionEngine.

    ManyMailer is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    ManyMailer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    Read the terms of the GNU General Public License
    at <http://www.gnu.org/licenses/>.
    
    Copyright 2019 Antonio Moses - http://texasbluesalley.com
*/

require_once(PATH_THIRD.EXT_SHORT_NAME.'/config.php');

class Services_module {
	var $config;
	var $debug = FALSE;
	var $email_crlf = '\n';
	var $email_in = array();
	var $email_out = array();
	var $model;
	var $protocol;
	var $settings = array();
	var $site_id;
	var $services = array();
	var $version = EXT_VERSION;
	var $is_func = null;	

	function __construct($settings = '')
	{
		
		ee()->load->helper('debug');
		ee()->load->helper('MessageArray');
		ee()->load->helper('html');
		ee()->load->library('composer');
		$this->debug = TRUE;
        $this->config = ee()->config->item(EXT_SHORT_NAME.'_settings');
		if(ee()->config->item('email_crlf') != false)
		{
			$this->email_crlf = ee()->config->item('email_crlf');
		}
		$this->model = ee('Model')->get('Extension')
			->filter('class', ucfirst(EXT_SHORT_NAME).'_ext')
			->first();
		$this->protocol = ee()->config->item('mail_protocol');
		$this->site_id = ee()->config->item('site_id');
		$this->sidebar_loaded = ee()->config->load('sidebar', TRUE, TRUE);
		$this->services_loaded = ee()->config->load('services', TRUE, TRUE);
		$this->sidebar_options = ee()->config->item('options', 'sidebar');
		$this->services = ee()->config->item('services', 'services'); 
		$this->settings = $settings;
		$this->dbg_msgs = new MessageArray();
	}

	function settings_form($all_settings)
	{	    				
		// $settings_info = $this->getSettingInfo($all_settings);
		$settings = $this->get_settings();
		$services_sorted = array();
		
		if(ee('Request')->isAjax() && $services = ee('Request')->post('service_order'))
		{
			$all_settings[$this->site_id]['service_order'] = explode(',', $services);
			$this->model->settings = $all_settings;
			$this->model->save();
			exit();
		}	
		// Look at custom service order
		foreach($settings['service_order'] as $service)
		{
			$services_sorted[$service] = $this->services[$service];
		}

		// Add any services were not included in the custom order
		foreach($this->services as $service => $service_settings)
		{
			if(empty($services_sorted[$service]))
			{
				$services_sorted[$service] = $service_settings;
			}
		}
		$vars = array(
			'debug' => $this->debug,
			'current_service' => false,
			'current_settings' => $settings,
			'services' => $services_sorted,
			'ee_version' => $this->ee_version(),
			'categories' => array_keys($this->sidebar_options),
		);
		if (ee()->uri->segment(6) !== ''){
			$vars['current_service'] = $this->current_service =  ee()->uri->segment(6);
		}
		
		if(!empty($this->config))
		{
			$vars['form_vars']['extra_alerts'] = array('config_warning');
			ee('CP/Alert')->makeInline('config_warning')
				->asWarning()
				->withTitle(lang('config_warning_heading'))
				->addToBody(lang('config_warning_text'))
				->cannotClose()
				->now();
		}

		$breadcrumbs = array(
			ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile() => EXT_NAME,
			ee('CP/URL')->make(EXT_SETTINGS_PATH ."/services")->compile() => lang('services'),
		);
		// if the current = the service detail page
		if (!$this->current_service) array_pop($breadcrumbs);

		$vars['base_url'] = ee('CP/URL',EXT_SETTINGS_PATH.'/services/save');
		
		$vars['save_btn_text'] = 'btn_save_settings';
		$vars['save_btn_text_working'] = 'btn_saving';
		$vars['sections'] = array();
		if ($this->current_service) {
			$vars = $this->_service_settings($vars, $settings);
			$vars['cp_page_title'] = lang(''.$this->current_service.'_name');
		}else{
			$vars['cp_page_title'] = lang('services');
			$vars['current_action'] = 'services';
			unset($vars['current_service']);
		}	   
		console_message($vars, __METHOD__);
		return array(
			'vars' => $vars,			
			'bc' => $breadcrumbs,
			);
	}

	function save_settings()
	{
		$settings = $this->get_settings(true);
		$current_service = '';

		foreach($this->services as $service => $service_settings)
		{
			if($v = ee('Request')->post($service.'_active'))
			{
				$current_service = $service;
				$settings[$this->site_id][$service.'_active'] = $v;
			
				foreach($service_settings as $setting)
				{
					$settings[$this->site_id][$setting] = ee('Request')->post($setting);
				}
			}
		}

		$this->model->settings = $settings;
		$this->model->save();
		console_message($current_service, __METHOD__);
		ee('CP/Alert')->makeInline('shared-form')
	      ->asSuccess()
		  ->withTitle(lang('settings_saved'))
		  ->addToBody(sprintf(lang('settings_saved_desc'), EXT_NAME))
	      ->defer();
	      
	    ee()->functions->redirect(
	    	ee('CP/URL')->make('addons/settings/'.EXT_SHORT_NAME.'/services/'.$current_service)
	    );
	}

	function viewDbg(&$vars){
		if ($this->debug){
			// add any accumalated debug messages
			$content = $this->dbg_msgs->data;
		
			// add messages to page
			ee()->load->helper('html');
			foreach($this->dbg_msgs as $msg){
				$vars['form_vars']['extra_alerts'][] = array('config_vars');
				ee('CP/Alert')->makeInline($msg->title)
					->asAttention()
					->withTitle($msg->title)
					->addToBody($msg->msg)
					->canClose()
					->now();
			}
		}
	}


	private function _service_settings(&$vars, $settings){
			
		$sections = array(
			array(
				'title' => lang('description'),
				'fields' => array(
					'description' => array(
						'type' => 'html',
						'content' => "<div class='".EXT_SHORT_NAME."-service-description'>".lang(''.$this->current_service.'_description').'</div>'),
						$this->current_service.'_active' => array(
						'type' => 'inline_radio',
						'choices' => array(
							'y' => lang('enabled'),
							'n' => lang('disabled')
						),
						'value' => (!empty($settings[$this->current_service.'_active']) && $settings[$this->current_service.'_active'] == 'y') ? 'y' : 'n'
					)
				)
			)
		);
			
		if (array_key_exists($this->current_service, $vars['services'])){
			foreach($vars['services'][$this->current_service] as $field_name)
			{
				// console_message($field_name, __METHOD__);
				// if (is_array($field_name)){
				// 	foreach ($field_name as $key => $value) {
				// 		# code...
				// 		switch ($value) {
				// 			case 'checkbox':
				// 			case 'inline_radio':
				// 				$field = array(
				// 					$key => array(
				// 						'type' => 'inline_radio',
				// 						'choices' => array(
				// 							'y' => lang('enabled'),
				// 							'n' => lang('disabled')
				// 						),
				// 						'value' => (!empty($field_name['value']) && $field_name['value'] == 'y') ? 'y' : 'n'
				// 					)
				// 				);
				// 			default:
				// 				# code...
				// 				$field = array(
				// 					$field_name = array(
				// 						'type' => $value,
				// 						'value' => (!empty($field_name['value'])) ? $field_name['value'] : '',
				// 					)
				// 				);
				// 				break;
				// 		}

				// 		$sections[] = array(
				// 			'title' => lang(''.$key),
				// 			'desc' =>  '',
				// 			'fields' => array(
				// 				$key => $field
				// 			)
				// 		);
						
				// 	}
				// }else{
					$sections[] = array(
						'title' => lang(''.$field_name),
						'desc' => ($field_name == 'mandrill_test_api_key' || $field_name == 'mandrill_subaccount') ? lang('optional') : '',
						'fields' => array(
							$field_name => array(
								'type' => 'text',
								'value' => (!empty($settings[$field_name])) ? $settings[$field_name] : '',
							)
						)
					);
				// }
			}
		}
		$vars['sections'] = array($sections);
		return $vars;
	}
	
	public function get_settings($all_sites = false)
	{
		$all_settings = $this->model->settings;
		console_message($this->site_id, __METHOD__);
		$settings = ($all_sites == true || empty($all_settings)) ? $all_settings : $all_settings[$this->site_id];
                
        // Check for config settings - they will override database settings
		if($all_sites == false)
	    {
	        // Set a service order if none is set
	        if(empty($settings['service_order']) && empty($this->config[$this->site_id]['service_order']))
	        {
		        $settings['service_order'] = array();
		        foreach($this->services as $service => $service_settings)
		        {
			        $settings['service_order'][] = $service;
		        }
			}
	        
	        // Override each setting from config
	        if(!empty($this->config[$this->site_id]))
	        {
		        foreach($this->config[$this->site_id] as $k => $v)
		        {
			        $settings[$k] = $v;
		        }
			}
		}
        return $settings;
    
	}

	function email_send($data)
	{	
		$settings = $this->get_settings();
		console_message($settings, __METHOD__);
		if(empty($settings['service_order']))
		{
			return false;
		}
		
		ee()->lang->loadfile(EXT_SHORT_NAME);
		ee()->load->library('logger');
		
		$sent = false;
		$this->email_in = $data;
		unset($data);
		
		$this->email_in['finalbody'] = $this->email_in['message'];
		
		if($this->debug == true)
		{
			console_message($this->email_in);
		}
		
		// Set X-Mailer
		$this->email_out['headers']['X-Mailer'] = APP_NAME .' (via '. EXT_NAME . ' ' .$this->version.')';

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
			console_message($service, __METHOD__);
			console_message($settings, __METHOD__);
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
						if (!empty($settings['mandrill_test_api_key']) && $key == ""){
							$key = $settings['mandrill_test_api_key'];
							ee()->logger->developer(sprintf(lang('using_alt_credentials'), $service, $key));
							console_message('Using Mandrill Test API Key', __METHOD__);
						}
						if($key == ""){
							$subaccount = (!empty($settings['mandrill_subaccount']) ? $settings['mandrill_subaccount'] : '');
							$sent = $this->_send_mandrill($key, $subaccount);
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
		return $this->_curl_request('https://mandrillapp.com/api/1.0/messages/'.$method.'.json', $headers, $content);
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


	function ee_version()
	{
		return substr(APP_VER, 0, 1);
	}
	

	function activate_extension()
	{
		$this->settings = array();
		
		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'email_send',
			'hook'		=> 'email_send',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);

		ee()->db->insert('extensions', $data);			
			return $this->addCsvColumn();
		
	}	
	

	function disable_extension()
	{
		ee()->db->where('class', __CLASS__);
		ee()->db->delete('extensions');
		ee()->load->dbforge();
		$added_cols = array(
			'csv_object',
			'mailKey'
		);
		foreach ($added_cols as $col){
			ee()->dbforge->drop_column('email_cache', $col);
		}
	}


	function update_extension($version = '')
	{
		if(version_compare($version, '0.1.4', '<='))
		{
			return $this->addCsvColumn();
		}
		if(version_compare($version, $this->version) === 0)
		{
			return FALSE;
		}
		return TRUE;		
	}	

	function addCsvColumn(){
		ee()->load->dbforge();
		$fields = array(
			'csv_object' => array(
				'type' => 'JSON'
			),
			'mailKey' => array(
				'type' => 'VARCHAR',
				'constraint' => 100
			)
			);

		ee()->dbforge->add_column('email_cache', $fields);
	}}