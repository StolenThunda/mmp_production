<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(PATH_THIRD . '/manymailerplus/helpers/debug_helper.php');
$extensionName = "MMP_Production";
	if( ! defined('EXT_VERSION') )
	{
		define('EXT_VERSION', '1.2.0');
		define('EXT_NAME', $extensionName);
		define('EXT_SHORT_NAME', strtolower($extensionName));
		define('EXT_SETTINGS_PATH', 'addons/settings/'.strtolower($extensionName));
	define('EXT_MANUAL_PATH', 'addons/manual/' . strtolower($extensionName));
	}
