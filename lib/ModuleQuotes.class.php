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
		else if (substr($bot->message['text'], 0, 9) == '!setquote') {
			$this->config->config[$bot->lookUpUserID()] = substr($bot->message['text'], 10);
	//		$bot->queue('['.$bot->message['usernameraw'].'] Deine Join-Nachricht wurde auf: "'.substr($bot->message['text'], 10).'" gesetzt');
			$bot->success();
		}
		else if ($bot->message['text'] == '!delquote') {
			unset($this->config->config[$bot->lookUpUserID()]);
	//		$bot->queue('['.$bot->message['usernameraw'].'] deine Join-Nachricht wurde gelöscht');
			$bot->success();
		}
		else if (substr(Module::removeWhisper($bot->message['text']), 0, 10) == '!wipequote' && Core::isOp($bot->lookUpUserID())) {
			$username = substr(Module::removeWhisper($bot->message['text']), 11);
			$userID = $bot->lookUpUserID($username);
			if ($userID) {
				unset($this->config->config[$userID]);
				$bot->success();
			}
			
		}
	}
}
