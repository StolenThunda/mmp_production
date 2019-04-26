<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\SimpleCommerce\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * 
 */
class EmailTemplateCache extends Model {

	protected static $_primary_key = 'email_id';
	protected static $_table_name = 'exp_manymailer_emails';

	protected static $_validation_rules = array(
		'email_cache_id'    => 'required',
		'email_body'    => 'required',
		'email_name'    => 'required',
	);

	protected $email_id;
	protected $email_cache_id;
	protected $email_name;
	protected $email_subject;
	protected $email_body;
	protected $date;
}

// EOF
