<?php
class ModuleDictionary extends Module {
	protected $config = null;
	public function __construct() {
		$this->config = new Config('dictionary', array());
		$this->config->write();
	}
	
	public function destruct() {
		$this->config->write();
	}
	
	public function handle(Bot $bot) {
		if ($bot->message['id'] % 500 == 0) $this->config->write();
		if (substr($bot->message['text'], 0, 1) == '-') {
			if (isset($this->config->config[substr($bot->message['text'], 1)])) {
				$bot->queue($this->config->config[substr($bot->message['text'], 1)]);
			}
		}
		else if (substr(Module::removeWhisper($bot->message['text']), 0, 5) == '!dic ') {
			if (Core::isOp($bot->lookUpUserID())) {
				$data = explode(' ', substr(Module::removeWhisper($bot->message['text']), 5));
				$this->config->config[$data[0]] = $data[1];
				$bot->success();
			}
			else {
				$bot->denied();
			}
		}
		else if (substr(Module::removeWhisper($bot->message['text']), 0, 8) == '!deldic ') {
			if (Core::isOp($bot->lookUpUserID())) {
				$data = substr(Module::removeWhisper($bot->message['text']), 8);
				unset($this->config->config[$data]);
				$bot->success();
			}
			else {
				$bot->denied();
			}
		}
	}
}
