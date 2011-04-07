<?php
/**
 * Core-class. Provides basic module handling
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
class Core {
	
	/**
	 * Singleton-Instance
	 *
	 * @var	Core
	 */
	private static $instance = null;
	
	/**
	 * Main-Config
	 *
	 * @var	Config
	 */
	private static $config = null;
	
	/**
	 * Permission
	 *
	 * @var	Permission
	 */
	private static $permission = null;
	
	/**
	 * Logger
	 *
	 * @var	Log
	 */
	private static $log = null;
	
	/**
	 * Bot-instance
	 *
	 * @var	Bot
	 */
	private static $bot = null;
	
	/**
	 * Holds the loaded modules
	 *
	 * @var	array<Module>
	 */
	private static $modules = array();
	
	const ALREADY_LOADED = 1;
	const NOT_LOADED = 2;
	const NOT_FOUND = 3;
	const NO_MODULE = 4;
	
	private function __construct() {
		self::init();
		self::$log = new Log();
		self::log()->info = 'Starting, PID is '.getmypid();
		if (VERBOSE > 5) {
			self::log()->info = '         (__) ';
			self::log()->info = '         (oo) ';
			self::log()->info = '   /------\/ ';
			self::log()->info = '  / |    ||   ';
			self::log()->info = ' *  /\---/\ ';
			self::log()->info = '    ~~   ~~   ';
			self::log()->info = '...."Have you mooed today?"...';
		}
		self::$config = new Config();
		self::$permission = new Permission();
		self::$language = new Language(LANGUAGE);
		if (VERBOSE > 0) self::log()->info = 'Loaded Config';
		self::$bot = new Bot();
		
		$modules = self::config()->config['modules'];
		// load default modules
		if (VERBOSE > 0) self::log()->info = 'Loading Modules';
		foreach ($modules as $module) {
			self::loadModule($module);
		}
		self::bot()->work();
	}
	
	/**
	 * Initializes folders
	 *
	 * @return	void
	 */
	protected static function init() {
		global $argv;
		if (!file_exists(DIR.'log/')) mkdir(DIR.'log/', 0777);
		if (!file_exists(DIR.'cache/')) mkdir(DIR.'cache/', 0777);
		if (!file_exists(DIR.'config/')) mkdir(DIR.'config/', 0777);
		
		$args = self::parseArgs($argv);
		define('VERBOSE', ((isset($args['flags']['v'])) ? $args['flags']['v'] : 0));
		define('LANGUAGE', ((isset($args['options']['language'])) ? $args['options']['language'] : 'de'));
	}
	
	protected static function parseArgs($args){
		$ret = array(
			'exec'      => '',
			'options'   => array(),
			'flags'     => array(),
			'arguments' => array(),
		);

		$ret['exec'] = array_shift($args);

		while (($arg = array_shift($args)) !== null) {
			// Is it a option? (prefixed with --)
			if (substr($arg, 0, 2) === '--') {
				$option = substr($arg, 2);

				// is it the syntax '--option=argument'?
				if (strpos($option, '=') !== false) {
					array_push($ret['options'], explode('=', $option, 2) );
				}
				else {
					array_push($ret['options'], $option);
				}
				
				continue;
			}

			// Is it a flag or a serial of flags? (prefixed with -)
			if (substr($arg, 0, 1) == '-') {
				for ($i = 1; isset($arg[$i]); $i++) {
					if (isset($ret['flags'][$arg[$i]])) $ret['flags'][$arg[$i]]++;
					else $ret['flags'][$arg[$i]] = 1;
				}
				continue;
			}

			// finally, it is not option, nor flag
			$ret['arguments'][] = $arg;
			continue;
		}
		return $ret;
	}
	
	/**
	 * Shuts the bot down
	 *
	 * @return	void
	 */
	public static function destruct() {
		// break in child
		if (self::$bot !== null) {
			if (!self::$bot->isParent()) return;
		}
		self::$log->info = 'Shutting down';
		
		// send leave message
		self::$bot->getConnection()->leave();
		if (VERBOSE > 0) self::$log->info = 'Left chat';
		// write the configs
		self::$config->write();
		if (VERBOSE > 0) self::$log->info = 'Written config';
		
		// call destructors of modules
		foreach (self::$modules as $module) {
			$module->destruct();
		}
		if (VERBOSE > 0) self::$log->info = 'Unloading modules';
		
		// clear class cache
		$files = glob(DIR.'cache/*');
		foreach ($files as $file) {
			unlink($file);
		}
		if (VERBOSE > 0) self::$log->info = 'Cleaned cache';
		unlink(DIR.'bot.pid');
	}
	
	public static function isOp($userID) {
		return self::compareLevel($userID, 1);
	}

	public static function compareLevel($userID, $level) {
		if (!is_int($level)) {
			$level = self::permission()->$level;
		}
		return ((!isset(self::config()->config['levels'][$userID]) && $level == 0) || (isset(self::config()->config['levels'][$userID]) && self::config()->config['levels'][$userID] >= $level));
	}

	/**
	 * Loads the given module
	 *
	 * @var		string	$module		module-name
	 * @return	string			module-address
	 */
	public static function loadModule($module) {
		$module = ucfirst($module);
		// handle loaded
		if (isset(self::$modules[$module])) {
			self::log()->error = 'Tried to load module '.$module.' that is already loaded';
			return self::ALREADY_LOADED;
		}
		
		// handle wrong name
		if (!file_exists(DIR.'lib/module/Module'.$module.'.class.php')) {
			self::log()->error = 'Tried to load module '.$module.' but there is no matching classfile';
			
			return self::NOT_FOUND;
		}
		
		// copy to prevent classname conflicts
		$address = 'Module'.substr(StringUtil::getRandomID(), 0, 8);
		$data = str_replace('class Module'.$module.' ',  "// Module is: ".$module."\nclass ".$address.' ', file_get_contents(DIR.'lib/Module'.$module.'.class.php'));
		file_put_contents(DIR.'cache/'.$address.'.class.php', $data);

		// now load
		require_once(DIR.'cache/'.$address.'.class.php');
		self::$modules[$module] = new $address();
		
		// check whether it is really a module
		if (!self::$modules[$module] instanceof Module) {
			self::log()->error = 'Tried to load Module '.$module.' but it is no module, unloading';
			self::unloadModule($module);
			return self::NO_MODULE;
		}
		
		self::config()->config['modules'][$module] = $module;
		self::config()->write();
		
		self::log()->info = 'Loaded module '.$module.' @ '.$address;
		return $address;
	}
	
	/**
	 * Unloads the given module
	 *
	 * @var		string	$module		module-name
	 * @return	void
	 */
	public static function unloadModule($module) {
		$module = ucfirst($module);
		if (!isset(self::$modules[$module])) {
			self::log()->error = 'Tried to unload module '.$module.' that is not loaded';

			return self::NOT_LOADED;
		}
		$address = get_class(self::$modules[$module]);
		
		self::$modules[$module]->destruct();
		
		unlink(DIR.'cache/'.$address.'.class.php');
		unset(self::$modules[$module]);
		unset(self::config()->config['modules'][$module]);
		self::config()->write();
		
		self::log()->info = 'Unloaded module '.$module.' @ '.$address;
		
		return $module;
	}
	
	/**
	 * Reloads the given module
	 *
	 * @var		string	$module		module-name
	 * @return	void
	 */
	public static function reloadModule($module) {
		self::unloadModule($module);
		return self::loadModule($module);
	}
	
	/**
	 * Checks whether the module is loaded
	 *
	 * @var		string	$module		module-name
	 * @return	boolean			module loaded
	 */
	public static function moduleLoaded($module) {
		return isset(self::$modules[$module]);
	}
	
	/**
	 * Return the loaded modules
	 * 
	 * @return	array<Module>
	 */
	public static function getModules() {
		return self::$modules;
	}
	
	/**
	 * Returns the Core-object
	 * 
	 * @return	Core		Singleton-Instance
	 */
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
	
	/**
	 * Logs PHP-Errors
	 *
	 * @var		int	$errorNo	error-number
	 * @var		string	$message	error-message
	 * @var		string	$filename	file with error
	 * @var		int	$lineNo		line in the file
	 * @return	void
	 */
	public static final function handleError($errorNo, $message, $filename, $lineNo) { 
		if (error_reporting() != 0) {
			$type = 'error';
			switch ($errorNo) {
				case 2: $type = 'warning';
					break;
				case 8: $type = 'notice';
					break;
			}

			self::$log->error = 'PHP '.$type.' in file '.$filename.' ('.$lineNo.'): '.$message;
		}
	}
}
