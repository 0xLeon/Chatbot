<?php
class Core {
	
	private static $instance = null;
	private static $config = null;
	private static $log = null;
	private static $bot = null;
	private static $modules = array();
	private function __construct() {
		self::init();
		self::$log = new Log();
		self::log()->info = 'Starting, PID is '.getmypid();
		self::$config = new Config();
		self::log()->info = 'Loaded Config';
		self::$bot = new Bot();
		
		$modules = self::config()->config['modules'];
		self::log()->info = 'Loading Modules';
		foreach ($modules as $module) {
			self::loadModule($module);
		}
		self::bot()->work();
	}
	
	protected static function init() {
		if (!file_exists(DIR.'log/')) mkdir(DIR.'log/', 0777);
		if (!file_exists(DIR.'cache/')) mkdir(DIR.'cache/', 0777);
		if (!file_exists(DIR.'config/')) mkdir(DIR.'config/', 0777);
	}
	
	public static function destruct() {
		if (self::$bot !== null) {
			if (!self::$bot->isParent()) return;
		}
		self::$bot->getConnection()->leave();
		self::$log->info = 'Shutting down, clearing cache';
		$files = glob(DIR.'cache/*.class.php');
		foreach ($files as $file) {
			unlink($file);
		}
		foreach (self::$modules as $module) {
			$module->destruct();
		}
		unlink(DIR.'config/bot.pid');
	}
	
	public static function isOp($userID) {
		return in_array($userID, self::config()->config['op']);
	}
	
	public static function loadModule($module) {
		if (isset(self::$modules[$module])) return self::log()->error = 'Tried to load module '.$module.' that is already loaded';
		if (!file_exists(DIR.'lib/Module'.ucfirst($module).'.class.php')) return self::log()->error = 'Tried to load Module '.$module.' but there is no matching classfile';
		$address = 'Module'.substr(StringUtil::getRandomID(), 0, 8);
		$data = str_replace('class Module'.$module.' {', 'class '.$address." {\n// Module is: ".$module, file_get_contents(DIR.'lib/Module'.ucfirst($module).'.class.php'));
		file_put_contents(DIR.'cache/'.$address.'.class.php', $data);
		
		require_once(DIR.'cache/'.$address.'.class.php');
		self::$modules[$module] = new $address();
		if (!self::$modules[$module] instanceof Module) {
			self::log()->error = 'Tried to load Module '.$module.' but it is no module, unloading';
			return self::unloadModule($module);
		}
		
		self::config()->config['modules'][$module] = $module;
		self::config()->write();
		
		self::log()->info = 'Loaded module '.$module.' @ '.$address;
		return $address;
	}
	
	public static function unloadModule($module) {
		if (!isset(self::$modules[$module])) return self::log()->error = 'Tried to unload module '.$module.' that is not loaded';
		$address = get_class(self::$modules[$module]);
		unlink(DIR.'cache/'.$address.'.class.php');
		
		self::$modules[$module]->destruct();
		
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
	
	public static function moduleLoaded($module) {
		return isset(self::$modules[$module]);
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

			self::$log->error = 'PHP '.$type.' in file '.$filename.' ('.$lineNo.'): '.$message;
		}
	}
}
