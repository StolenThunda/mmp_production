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
		return $vars;
	}

	function save_settings(){
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
		console_message("$current_service : ". json_encode($settings), __METHOD__);
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
		// if ($this->debug){
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
		// }
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
			
		// $control_type = "text";
		// $choice_options = FALSE;
		// $enabled_disabled = array(
		// 	'y' => lang('enabled'),
		// 	'n' => lang('disabled')
		// );
		if (array_key_exists($this->current_service, $vars['services'])){
			foreach($vars['services'][$this->current_service] as $field_name)
			{
				
				$i = $this->getServiceFields($field_name);
				console_message($i);
				extract($i, EXTR_OVERWRITE);
			
				// $is_multi_choice = is_array($field_name);
				// if ($is_multi_choice) {
				// 	$choice_options = $field_name;					
				// 	$field_name = array_keys($field_name)[0];					
				// 	console_message($choice_options, __METHOD__);
				// }

				// $is_control = strpos($field_name, '__');
				
				// if ($is_control !== FALSE){
				// 	$control_type = substr($field_name, ($is_control + 2 ));
				// 	console_message("$field_name ( $is_control ) :  $control_type", __METHOD__);
				// } 
				
				$field = array('type' => $control_type);
				switch ($control_type) {
					case 'file':
					case 'image':
						$filepicker = ee('CP/FilePicker')->make();
						$link = $filepicker->getLink($field_name);
						$field = array_merge($field, array(							
							'value' => $link->render(),
						));
						break;
					case 'select':
					case 'dropdown':
					case 'radio':
					case 'yes_no':
					case 'checkbox':
					case 'inline_radio':
						
						$choices = (is_array($choice_options)) ? $choice_options[key($choice_options)] : $enabled_disabled;
						$field = array_merge($field, array(							
							'choices' => $choices,
							'value' => (!empty($settings[$field_name])) ? $settings[$field_name] : '',
						));						
						break;
					case 'textarea':
					case 'short-text':
						$field = array_merge($field, array('value' => (!empty($settings[$field_name])) ? $settings[$field_name] : ''));	
						break;
					default:
						$field = array(
							'type' => 'text',
							'value' => (!empty($settings[$field_name])) ? $settings[$field_name] : '',
						);
					}
						
					$sections[] = array(
						'title' => lang(''.$field_name),
						'desc' =>  (in_array($field_name, array('mandrill_test_api_key','mandrill_subaccount'))) ? lang('optional') : '',
						'fields' => array(
							$field_name => $field
						)
					);
			}
		}
		console_message($sections, __METHOD__);
		$vars['sections'] = array($sections);
		return $vars;
	}

	public function getServiceFields($field_name, $type = 'text'){
		$info = array();
		$is_multi_choice = is_array($field_name);
		$choice_options = FALSE;
		if ($is_multi_choice) {
			$choice_options = $field_name;					
			$field_name = array_keys($field_name)[0];					
			console_message($choice_options, __METHOD__);
		}

		$is_control = strpos($field_name, '__');
			
		if ($is_control !== FALSE){
			$type = substr($field_name, ($is_control + 2 ));
			console_message("$field_name ( $is_control ) :  $type", __METHOD__);
		} 

		return array(
			'field_name'  => $field_name,
			'choice_options' => $choice_options,
			'control_type' => $type,
			'enabled_disabled' => $enabled_disabled = array(
				'y' => lang('enabled'),
				'n' => lang('disabled')
			)
		);
	}
	
	public function get_settings($all_sites = false) {
		$all_settings = $this->model->settings;
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

	function getActiveServiceNames(){
		$settings = $this->get_settings();
		$active_services = array_filter($settings, function($v, $k){
			return $v == 'y';
		}, ARRAY_FILTER_USE_BOTH); 
		$acts = array_map(function($k){
			return explode('_', $k)[0];
		}, array_keys($active_services));
		return json_encode($acts);
	}

	function ee_version()
	{
		return substr(APP_VER, 0, 1);
	}

}
