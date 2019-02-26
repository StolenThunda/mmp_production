<?php
class MessageArray extends stdClass{
		var $data = array();

		public function addMsg($str){
			$msgObj = new stdClass();
			$info = explode(':', $str);
			$msgObj->title = $info[0];
			$msgObj->msg = $info[1];
			$msgObj->misc = array_slice($info, 1);
			$this->data[] = $msgObj;
		}

		public function print(){
			return array(print_r($obj->data, True) . PHP_EOL, 'Current Debug Messages');
		}
	}