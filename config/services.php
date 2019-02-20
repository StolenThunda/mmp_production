<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    return $config = array( 
        'services' => array(
			'mandrill' => array(
				'mandrill_api_key',
				'mandrill_subaccount'
			),
			/* 'mailgun' => array(
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
			), */
			'fakeservice' => array(
				'fakeservice_api_key',
				'fakeservice_stuff1',
				'fakeservice_stuff2',
				'fakeservice_stuff3',
				'fakeservice_stuff4',
			)
        )
    );
        
?>