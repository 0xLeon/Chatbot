<?php
/**
 * Adds a kind of dictionary
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
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
			if (Core::compareLevel($bot->lookUpUserID(), 'dic.add')) {
				$data = explode(' ', substr(Module::removeWhisper($bot->message['text']), 5), 2);
				$this->config->config[$data[0]] = $data[1];
				Core::log()->info = $bot->message['usernameraw'].' added '.$data[0].' to dictionary';
				$bot->success();
			}
			else {
				$bot->denied();
			}
		}
		else if (substr(Module::removeWhisper($bot->message['text']), 0, 8) == '!deldic ') {
			if (Core::compareLevel($bot->lookUpUserID(), 'dic.add')) {
				$data = substr(Module::removeWhisper($bot->message['text']), 8);
				unset($this->config->config[$data]);
				$bot->success();
			}
			else {
				$bot->denied();
			}
		}
		else if (Module::removeWhisper($bot->message['text']) == '!listdic') {
			if (Core::compareLevel($bot->lookUpUserID(), 'dic.list')) {
				$entries = array_keys($this->config->config);
				sort($entries);
				$bot->queue('/whisper "'.$bot->message['usernameraw'].'" '.Core::language()->dic_listdic.': '.implode(', ', $entries));
			}
			else {
				$bot->denied();
			}
		}
	}
}
?>
