<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(PATH_THIRD.EXT_SHORT_NAME.'/config.php');

use ExpressionEngine\Service\Addon\Installer;

class MMP_Production_upd extends Installer
{
	public $version = EXT_VERSION;

	function ee_version()
	{
		return substr(APP_VER, 0, 1);
	}
	
	public function __construct()
	{
		return parent::__construct();
	}

	function install()
	{
		$this->settings = array();
		
		// ADD EXTENSION FOR SERVICES INTEGRATION
		$ext_data = array(
			'class'		=> ucfirst(EXT_SHORT_NAME).'_ext',
			'method'	=> 'email_send',
			'hook'		=> 'email_send',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'priority'  => 1,
			'enabled'	=> 'y'
		);
        ee()->db->insert('extensions', $ext_data);			

        $mod_data = array(
            'module_name' => EXT_NAME,
            'module_version' => $this->version,
            'has_cp_backend' => 'y',
            'has_publish_fields' => 'n'
        );

		$previousInstall = ee()->db->get_where('modules', $mod_data);
		if ($previousInstall->num_rows() == 0) ee()->db->insert('modules', $mod_data);			
		return $this->addCsvColumn();
	}	
	

	function uninstall()
	{
		// ADD EXTENSION FOR SERVICES INTEGRATION
		ee()->db->where('class',ucfirst(EXT_SHORT_NAME).'_ext');
		ee()->db->delete('extensions');

		ee()->db->where('module_name', EXT_NAME);
		ee()->db->delete('modules');

		// ee()->db->delete('modules', array( 'module_name' => EXT_NAME));
		// $result = ee()->db->simple_query('delete from exp_modules where module_name like "Manymailerplus%');
		//console_message(ee()->db->last_query(), ee()->db->affected_rows()." Rows Deleted", TRUE);

        ee()->load->dbforge();
		foreach (array('csv_object', 'mailKey') as $column){
			console_message("$column TEST", __METHOD__);
			if( ee()->db->field_exists($column, 'exp_email_cache')){
				$result = ee()->dbforge->drop_column('exp_email_cache', $column);
				console_message($result, __METHOD__);
			}else{

				console_message("$column does not exist", __METHOD__);
			}
        }
		return TRUE;
	}


	function update($version = '') 
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
		$hasColumns = FALSE;
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
		foreach (array_keys($fields) as $column){
			$hasColumns = ee()->db->field_exists($column, 'exp_email_cache');
			// console_message(ee()->db->last_query(), ee()->db->affected_rows()." Rows Affected");
			// console_message($column,"Has Column : ".(($hasColumns) ? 'TRUE': 'FALSE'));
			if ($hasColumns) break;
		} 
		
		if (!$hasColumns) {
			$result = ee()->dbforge->add_column('email_cache', $fields);
			// console_message($result->num_rows(), (string) $hasColumns);
		}
		return ee()->db->field_exists('csv_object', 'exp_email_cache');
	}
}

