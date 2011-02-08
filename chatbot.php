<?php
/**
 * Initializes the bot
 *
 * @author		Tim D�sterhus
 * @copyright	2010 Tim D�sterhus
 */
 
date_default_timezone_set('Europe/Berlin');

// get the signal handler working
declare(ticks = 1);
define('DIR', dirname(__FILE__).'/');

// load userconfig
require_once(DIR.'config/userdata.php');
define('TIME', time());

// write pidfile
file_put_contents(DIR.'config/bot.pid', getmypid());

function __autoload($class) {
	require_once(DIR.'lib/'.$class.'.class.php');
}

set_error_handler(array('Core', 'handleError'));

// start up 
Core::get();