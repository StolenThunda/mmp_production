<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	/*default element in array is textbox. For other option
	 append with __ (2 underscores) + EE field name 

	 to add multiple options:
		 add array as element (key = el name, value = assoc array)
		 
		 For example:
		'services' => array(
			'mandrill' => array(
				'mandrill_api_key',
				'mandrill_subaccount',
				array(
					'mandrill_options__select' => array(
						'test1' => 'test1',
						'test2' => 'test2',
						'test3' => 'test3',
					)
				),
				'mandrill_testmode__yes_no', 
				'mandrill_test_api_key',				
			)
		)
	If element is a select, dropdown, radio, 
	Field NamesField name	Description
		text	Regular text input.
		short-text	Small text input, typically used when a fieldset needs multiple small, normally numeric, values set.
		textarea	Textarea input.
		select	Select dropdown input.
		dropdown	A rich select dropdown .
		checkbox	Checkboxes displayed in a vertical list.
		radio	Radio buttons displayed in a vertical list.
		yes_no	A Toggle control that returns either y or n respectively.
		file	File input. Requires filepicker configuration.
		image	Image input. Like file but shows an image thumbnail of the selected image as well as controls to edit or remove. Requires filepicker configuration.
		password	Password input.
		hidden	Hidden input.
		html	Freeform HTML can be passed in via the content key in the field definition to have a custom input field.
	*/
	return $config = array( 
        'services' => array(
			'mandrill' => array(
				'mandrill_api_key',
				'mandrill_subaccount',
				'mandrill_testmode__yes_no', 
				'mandrill_test_api_key',				
			),
			'mailgun' => array(
				'mailgun_api_key',
				'mailgun_domain'
			),
			'postageapp' => array(
				'postageapp_api_key'
			),			
			'postmark' => array(
				'postmark_api_key'
			),
			'sendgrid' => array(
				'sendgrid_api_key',
			),
			'sparkpost' => array(
				'sparkpost_api_key',
				array(
					'sparkpost_options__select' => array(
						'test1' => 'test1',
						'test2' => 'test2',
						'test3' => 'test3',
					)
				),
			)
		)
    );
?>