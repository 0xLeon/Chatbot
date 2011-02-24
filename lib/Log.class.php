<?php
/**
 * Logger
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
class Log {
	
	/**
	 * Logs an action
	 *
	 * @param	string	$log		log-name
	 * @param	string	$what	message
	 * @return	void
	 */
	public function log($log, $what) {
		echo	"[".date('d.m.Y H:i:s')."] ".$log.": ".$what."\n";
		$data = '';
		if (file_exists(DIR.'log/'.$log)) {
			$data = file_get_contents(DIR.'log/'.$log);
		}
		file_put_contents(DIR.'log/'.$log, $data."\n[".date('d.m.Y H:i:s')."] ".$what);
	}
	
	/**
	 * @see	Log::log()
	 */
	public function __set($log, $what) {
		$this->log($log, $what);
	}
	
	/**
	 * @see	Log::clear()
	 */
	public function __unset($log) {
		$this->clear($log);
	}
	
	/**
	 * Clears the log
	 *
	 * @param	string	$log		log-name
	 * @return	void
	 */
	public function clear($log) {
		unlink(DIR.'log/'.$log);
	}
}