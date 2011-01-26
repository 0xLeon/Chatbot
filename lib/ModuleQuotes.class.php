<?php
class ModuleQuotes extends Module {
	protected $config = null;
	public function __construct() {
		$this->config = new Config('quotes', array());
		$this->config->write();
	}
	
	public function destruct() {
		$this->config->write();
	}
	
	public function handle(Bot $bot) {
		if ($bot->message['id'] % 500 == 0) $this->config->write();
		
		if ($bot->message['text'] == 'hat den Chat betreten' && $bot->message['type'] == 1) {
			if (isset($this->config->config[$bot->lookUpUserID()])) {
				$bot->queue('['.$bot->message['usernameraw'].'] '.$this->config->config[$bot->lookUpUserID()]);
			}
		}
		else if (substr($bot->message['text'], 0,9) == '!setquote') {
			$this->config->config[$bot->lookUpUserID()] = substr($bot->message['text'], 10);
			$bot->queue('['.$bot->message['usernameraw'].'] Deine Join-Nachricht wurde auf: "'.substr($bot->message['text'], 10).'" gesetzt');
		}
		else if ($bot->message['text'] == '!delquote') {
			unset($this->config->config[$bot->lookUpUserID()]);
			$bot->queue('['.$bot->message['usernameraw'].'] deine Join-Nachricht wurde gelöscht');
		}
	/*	else if (substr($bot->message['text'], 0, strlen('flüstert an '.$bot->$own.': !wipequote')) == 'flüstert an '.$bot->$own.': !wipequote' && in_array($bot->message['usernameraw'], $bot->$mod)) {
			$username = substr($bot->message['text'], strlen('flüstert an '.$bot->$own.': !wipequote')+1);
			unset($bot->$config['quotes'][$username]);
			$bot->queue('/f "'.$bot->message['usernameraw'].'" Die Join-Nachricht wurde gelöscht');
		}*/
	}
}