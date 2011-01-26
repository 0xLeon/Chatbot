<?php
class Config {
	public $config = array();
	protected $type = 'main';
	public function __construct($type = 'main', $standard = array(
		'modules' => array(
		
		),
		'op' => array(
			ID => ID
		),
		'stfu' => false
	)) {
		$this->type = 'main';
		$this->load($standard);
		$this->write();
	}
	
	public function load($standard) {
		if (!file_exists(DIR.'config/'.$this->type)) {
			$this->config = $standard;
			return;
		}
		$data = unserialize(file_get_contents(DIR.'config/'.$this->type));
		$this->config = self::array_extend($standard, $data);
	}
	
	public function write() {
		file_put_contents(DIR.'config/'.$this->type, serialize($this->config));
	}
	
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