<?php
/**
 * Provides permission handling
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 * @licence	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
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
	
	/**
	 * Gets a permission
	 *
	 * @param	string		$name	permissionname
	 * @return	integer			value
	 */
	public function __get($name) {
		if (isset($this->config->config[$name])) return $this->config->config[$name];
		return 0;
	}
	
	/**
	 * Sets a permission
	 *
	 * @param	string		$name	permissionname
	 * @param	integer		$value	new value
	 * @return	void
	 */
	public function __set($name, $value) {
		$this->config->config[$name] = intval($value);
		$this->config->write();
	}
	
	/**
	 * Gets all permissions
	 *
	 * @return	array<integer>		permissions
	 */
	public function getNodes() {
		return $this->config->config;
	}
}
?>