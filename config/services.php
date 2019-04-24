<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    return $config = array( 
        'services' => array(
			'mandrill' => array(
				'mandrill_api_key',
				// 'mandrill_options' => array(
				// 	'mandrill_use_test_key' =>  'checkbox'
				// ), 
				'mandrill_test_api_key',
				'mandrill_subaccount'
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
			)
		)
    );
?>