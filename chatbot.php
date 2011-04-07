<?php
/**
 * Initializes the bot
 *
 * @author		Tim Düsterhus
 * @copyright	2010 Tim Düsterhus
 */
 
date_default_timezone_set('Europe/Berlin');

// get the signal handler working
declare(ticks = 1);
define('DIR', dirname(__FILE__).'/');

// load userconfig
require_once(DIR.'config/userdata.php');
define('TIME', time());

// write pidfile
file_put_contents(DIR.'bot.pid', getmypid());

function __autoload($class) {
	require_once(DIR.'lib/'.$class.'.class.php');
}

set_error_handler(array('Core', 'handleError'), E_ALL|E_STRICT|E_WARNING|E_NOTICE);

// start up 
Core::get();
