<?php
/**
 * Provides config handling
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 * @licence	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class Config {
	/**
	 * The config
	 * 
	 * @var	array<mixed>
	 */
	public $config = array();
	
	/**
	 * The config file
	 *
	 * @var string
	 */
	protected $type = 'main';
	public function __construct($type = 'main', $standard = array(
		'modules' => array(
		
		),
		'levels' => array(
			ID => PHP_INT_MAX
		),
		'ignore' => array(
		),
		'stfu' => false
	)) {
		$this->type = $type;
		$this->load($standard);
		$this->write();
	}
	
	/**
	 * Loads the config
	 * 
	 * @param	arrray<mixed>	$standard	standard config for this type
	 * @return	void
	 */
	public function load($standard) {
		if (!file_exists(DIR.'config/'.$this->type)) {
			$this->config = $standard;
			return;
		}
		$data = unserialize(file_get_contents(DIR.'config/'.$this->type));
		$this->config = self::array_extend($standard, $data);
	}
	
	/**
	 * Writes the config to disk
	 *
	 * @return	void
	 */
	public function write() {
		file_put_contents(DIR.'config/'.$this->type, serialize($this->config));
	}
	
	/**
	 * Merges two arrays recursively
	 *
	 * @param	array<mixed>	$a1	base array
	 * @param	array<mixed>	$a2	array to add
	 * @return	array<mixed>		merged array
	 */
	public static function array_extend($a1, $a2) {
		foreach ($a2 as $key => $val) {
			if (isset($a1[$key])) {
				if (is_array($a1[$key]) && is_array($val)) {
					$a1[$key] = self::array_extend($a1[$key], $val);
				}
				else if (is_array($a1[$key])) {
					$a1[$key][] = $val;
				}
				else {
					$a1[$key] = $val;
				}
			}
			else {
				$a1[$key] = $val;
			}
		}
		return $a1;
	}
}
?>