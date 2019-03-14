<?php
class MessageArray extends stdClass implements Iterator{
		var $data = array();

		public function addMsg($str){
			$msgObj = new stdClass();
			$info = explode(':', $str);
			if (count($info) > 1){
				$msgObj->title = $info[0];
				$msgObj->msg = $info[1];
				$msgObj->misc = array_slice($info, 1);
			}else{
				$msgObj->title = '';
				$msgObj->msg = $info[0];
				$msgObj->misc = null;
			}
			$this->data[] = $msgObj;
		}

	public function rewind()
    {
        reset($this->data);
    }
  
    public function current()
    {
        return current($this->data);
    }
  
    public function key() 
    {
        return key($this->data);
    }
  
    public function next() 
    {
        return next($this->data);
    }
  
    public function valid()
    {
        $key = key($this->data);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }

	public function emptyData(){
		$this->data = [];
	}	

	public function __toString(){
		return json_encode(array(print_r($this->data, True) . PHP_EOL, 'Current Debug Messages'));
	}
}