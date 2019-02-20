<?php
	
include(PATH_THIRD.'/manymailerplus/config.php');	
if (!array_search('camel2title', get_defined_functions())){
      function camel2title($str) {
            // inject space before the upper case letters
            $str = preg_replace_callback(
                  '/([A-Z])/',
                        function($match){
                              return implode(" " , array_unique($match));
                        },$str
                  );
            //replace first char with upper case
            // return preg_replace_callback(
            // 	'/^./',
            // 	function($match){
            // 		return strtoupper(implode('',$match));
            // 	}, $str
            // );		
      }
}
define('EXT_DISPLAY_NAME', camel2title(EXT_NAME));
return array(
      'author' => 'Amphibian',
      'author_url' => 'http://amphibian.info',
      'description' => 'Let your transactional emails get chaperoned by the pros.',
      'docs_url' => 'http://amphibian.info/software/ee/escort',
      'name' => EXT_NAME,
      'namespace' => EXT_SHORT_NAME,
      'settings_exist' => true,
      'version' => EXT_VERSION
);
