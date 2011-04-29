<?php
/**
 * Provides permission handling
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
class Permission {
	/**
	 * The config
	 * 
	 * @var array<mixed>
	 */
	protected $config = array();
	
	public function __construct() {
		$this->config = new Config('permission', array());
		$this->config->write();
	}
	
	public function __get($name) {
		if (isset($this->config->config[$name])) return $this->config->config[$name];
		return 0;
	}
	public function __set($name, $value) {
		$this->config->config[$name] = intval($value);
		$this->config->write();
	}
	public function getNodes() {
		return $this->config->config;
	}
}
?>