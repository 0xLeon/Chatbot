<?php
/**
 * Core class of the Bot
 *
 * @author	Tim Düsterhus
 * @license 	CC by-nc-sa <http://creativecommons.org/licenses/by-nc-sa/3.0/de/>
 */
class Core {
	
	/**
	 * Holds the instance of the core class
	 * 
	 * @var	Core
	 */
	private static $instance = null;
	
	/**
	 * Holds the main config
	 *
	 * @var	Config
	 */
	private static $config = null;
	
	/**
	 * Holds the logging class
	 *
	 * @var	Log
	 */
	private static $log = null;
	
	/**
	 * Holds the instance of the real bot
	 *
	 * @var	Bot
	 */
	private static $bot = null;
	
	/**
	 * Holds the loaded modules
	 *
	 * @var	array<Object>
	 */
	private static $modules = array();
	private function __construct() {
		self::init();
		self::$log = new Log();
		self::$config = new Config();
		self::$bot = new Bot();
		
		$modules = self::config()->config['modules'];
		foreach ($modules as $module) {
			self::loadModule($module);
		}
		self::bot()->work();
	}
	
	/**
	 * Creates needed folders
	 *
	 * @return	void
	 */
	protected static function init() {
		if (!file_exists(DIR.'log/')) mkdir(DIR.'log/', 0777);
		if (!file_exists(DIR.'cache/')) mkdir(DIR.'cache/', 0777);
		if (!file_exists(DIR.'config/')) mkdir(DIR.'config/', 0777);
	}
	
	/**
	 * Cleans up on shutdown
	 *
	 * @return	void
	 */
	public static function destruct() {
		self::bot()->getConnection()->leave();
		self::log()->info = 'Shutting down, clearing cache';
		$files = glob(DIR.'cache/*.class.php');
		foreach ($files as $file) {
			unlink($file);
		}
		unlink(DIR.'config/bot.pid');
	}
	
	/**
	 * Checks whether the given userID is an op
	 *
	 * @param	integer	$userID	userID to check
	 * @return	boolean			isOp
	 */
	public static function isOp($userID) {
		return in_array($userID, self::config()->config['op']);
	}
	
	public static function loadModule($module) {
		if (isset(self::$modules[$module])) return self::log()->error = 'Tried to load module '.$module.' that is already loaded';
		
		$address = 'Module'.substr(StringUtil::getRandomID(), 0, 8);
		$data = str_replace('class Module'.$module.' {', 'class '.$address." {\n// Module is: ".$module, file_get_contents(DIR.'lib/Module'.ucfirst($module).'.class.php'));
		file_put_contents(DIR.'cache/'.$address.'.class.php', $data);
		
		require_once(DIR.'cache/'.$address.'.class.php');
		self::$modules[$module] = new $address();
		
		self::config()->config['modules'][$module] = $module;
		self::config()->write();
		
		self::log()->info = 'Loaded module '.$module.' @ '.$address;
		return $address;
	}
	
	public static function unloadModule($module) {
		if (!isset(self::$modules[$module])) return self::log()->error = 'Tried to unload module '.$module.' that is not loaded';
		$address = get_class(self::$modules[$module]);
		unlink(DIR.'cache/'.$address.'.class.php');
		
		unset(self::$modules[$module]);
		unset(self::config()->config['modules'][$module]);
		self::config()->write();
		
		self::log()->info = 'Unloaded module '.$module.' @ '.$address;
		return $address;
	}
	
	public static function reloadModule($module) {
		self::unloadModule($module);
		self::loadModule($module);
	}
	
	public final static function get() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	public static function __callStatic($name, $arguments) {
		if (isset(self::$modules[$name])) {
			return self::$modules[$name];
		}
		else if (isset(self::$$name)) {
			return self::$$name;
		}
		else {
			self::log()->error = 'Tried to access unknown member '.$name.' in Core';
		}
	}
	
	public static final function handleError($errorNo, $message, $filename, $lineNo) { 
		if (error_reporting() != 0) {
			$type = 'error';
			switch ($errorNo) {
				case 2: $type = 'warning';
					break;
				case 8: $type = 'notice';
					break;
			}

			self::log()->error = 'PHP '.$type.' in file '.$filename.' ('.$lineNo.'): '.$message;
		}
	}
}
