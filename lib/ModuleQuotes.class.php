<?php
class ModuleQuotes extends Module {
	protected $config = null;
	protected $coolDown = array();

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
			
			$userID = $bot->lookUpUserID();
			if (isset($this->config->config[$userID]) && (!isset($this->coolDown[$userID]) || ($this->coolDown[$userID] + 5 * 60) < time())) {
				$bot->queue('['.$bot->message['usernameraw'].'] '.substr($this->config->config[$userID], 0, 250));
				$this->coolDown[$userID] = time();
			}
		}
		else if (substr(Module::removeWhisper($bot->message['text']), 0, 7) == '!quote ') {
			$username = substr(Module::removeWhisper($bot->message['text']), 7);
			$userID = $bot->lookUpUserID($username);
			if ($userID) {
				if (isset($this->config->config[$userID])) {
					$bot->queue('/whisper "'.$bot->message['usernameraw'].'" ['.$username.'] '.$this->config->config[$userID]);
				}
				else {
					$bot->queue('/whisper "'.$bot->message['usernameraw'].'" Der Benutzer hat kein Zitat');
				}
			}
			else {
				$bot->queue('/whisper "'.$bot->message['usernameraw'].'" Konnte den Benutzer '.$username.' nicht finden');
			}
		}
		else if (substr(Module::removeWhisper($bot->message['text']), 0, 10) == '!setquote ') {
			$this->config->config[$bot->lookUpUserID()] = substr(Module::removeWhisper($bot->message['text']), 10);
			$bot->success();
		}
		else if (Module::removeWhisper($bot->message['text']) == '!delquote') {
			unset($this->config->config[$bot->lookUpUserID()]);
			$bot->success();
		}
		else if (substr(Module::removeWhisper($bot->message['text']), 0, 11) == '!wipequote ') {
			if (Core::compareLevel($bot->lookUpUserID(), 'quote.wipe')) {
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
