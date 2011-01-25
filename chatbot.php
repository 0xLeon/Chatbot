<?php
declare(ticks = 1);
define('DIR', dirname(__FILE__).'/');
require_once(DIR.'config/userdata.php');
define('TIME', time());
file_put_contents(DIR.'config/bot.pid', getmypid());
function __autoload($class) {
	require_once(DIR.'lib/'.$class.'.class.php');
}

set_error_handler(array('Core', 'handleError'));
#set_exception_handler(array('Core', 'handleException'));
Core::get();