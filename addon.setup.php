<?php
      
include(PATH_THIRD.'/mmp_production/config.php');	
if (!array_search('camel2title', get_defined_functions())){
      function camel2title($str) {
            // inject space before the upper case letters
            $str = preg_replace_callback(
                  '/([A-Z])/',
                  function($match){
                        return implode(" " , array_unique($match));
                  },$str
            );
      }
}
define('EXT_DISPLAY_NAME', camel2title(EXT_NAME));
return array(
      'author' => 'Antonio Moses',
      'author_url' => 'http://texasbluesalley.com',
      'description' => 'MMP-PRODUCTION',
      'docs_url' => './README.md',
      'name' => EXT_NAME,
      'namespace' => EXT_NAME,
      'settings_exist' => TRUE,
      'version' => EXT_VERSION,

      'models' => array(
            'EmailCachePlus' => 'Model\EmailCachePlus'
      )
);
