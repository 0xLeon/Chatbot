<?php
/**
 * Parses language-files
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 * @licence	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class Language {
	protected $items = array();
	
	public function __construct($languageCode) {
		if (!$this->load($languageCode)) {
			Core::log()->error = 'Could not find language '.$languageCode;
			exit;
		}
	}
	
	/**
	 * Loads the given language
	 *
	 * @param	string		$languageCode	language to load
	 * @return	boolean				success
	 */
	public function load($languageCode) {
		if (!file_exists(DIR.'language/'.$languageCode.'.lng')) {
			return false;
		}
		$this->items = parse_ini_file(DIR.'language/'.$languageCode.'.lng');
		return true;
	}
	
	/**
	 * @see Language::get();
	 */
	public function __get($name) {
		return $this->get($name);
	}
	
	/**
	 * Returns the languageitem
	 *
	 * @param	string		$name	item-name
	 * @param	array<mixed>	$vars	variables to replace
	 * @return	string			item
	 */
	public function get($name, array $vars = array()) {
		if (isset($this->items[$name])) {
			return str_replace(array_keys($vars), array_values($vars), $this->items[$name]);
		}
		return '';
	}
}
?>