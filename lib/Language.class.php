<?php
/**
 * Parses language-files
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
class Language {
	protected $items = array();
	
	public function __construct($languageCode) {
		if (!$this->load($languageCode)) {
			Core::log()->error = 'Could not find language '.$languageCode;
			exit;
		}
	}
	
	public function load($languageCode) {
		if (!file_exists(DIR.'language/'.$languageCode.'.lng')) {
			return false;
		}
		$this->items = parse_ini_file(DIR.'language/'.$languageCode.'.lng');
		return true;
	}
	
	public function __get($name) {
		return $this->get($name);
	}
	
	public function get($name, Array $vars = array()) {
		if (isset($this->items[$name])) {
			return str_replace(array_keys($vars), array_values($vars), $this->items[$name]);
		}
		return '';
	}
}
?>