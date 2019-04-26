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
		$vars['active_service_names'] = ee()->mail_svc->getActiveServiceNames();
		$vars['sidebar'] = $this->sidebar_options;		
		console_message($vars, __METHOD__);
		return array(
			'body' => ee('View')->make(EXT_SHORT_NAME.':compose_view')->render($vars),
			'breadcrumb' => $service_vars['bc'],
			'heading' => $vars['cp_page_title']
		);
	}
}
// END CLASS