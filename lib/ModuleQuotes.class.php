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
		else if (substr($bot->message['text'], 0, 10) == '!setquote ') {
			$this->config->config[$bot->lookUpUserID()] = substr($bot->message['text'], 10);
			$bot->success();
		}
		else if ($bot->message['text'] == '!delquote') {
			unset($this->config->config[$bot->lookUpUserID()]);
			$bot->success();
		}
		else if (substr(Module::removeWhisper($bot->message['text']), 0, 11) == '!wipequote ') {
			if (Core::isOp($bot->lookUpUserID())) {
				$username = substr(Module::removeWhisper($bot->message['text']), 11);
				$userID = $bot->lookUpUserID($username);
				if ($userID > 0) {
					unset($this->config->config[$userID]);
					$bot->success();
				}
				else {
					$bot->queue('/whisper "'.$bot->message['usernameraw'].'" Konnte den Benutzer '.$username.' nicht finden');
				}
			}
			else {
				$bot->denied();
			}
		}
	}
}
