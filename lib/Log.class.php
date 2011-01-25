<?php
class Log {
	
	public function log($log, $what) {
		$data = '';
		if (file_exists(DIR.'log/'.$log)) {
			$data = file_get_contents(DIR.'log/'.$log);
		}
		file_put_contents(DIR.'log/'.$log, $data."\n[".date('d.m.Y H:i:s')."] ".$what);
	}
	
	public function __set($log, $what) {
		$this->log($log, $what);
	}
	
	public function __unset($log) {
		$this->clear($log);
	}
	
	public function clear($log) {
		unlink(DIR.'log/'.$log);
	}
}