<?php
class ModulePHP extends Module {
	public function destruct() {

	}
	public function handle(Bot $bot) {		
		if (substr(Module::removeWhisper($bot->message['text']), 0, 5) == '!php ') {
			if (!Core::isOp($bot->lookUpUserID())) return $bot->denied();
			Core::log()->php = $bot->message['usernameraw'].' used '.Module::removeWhisper($bot->message['text']);
			eval(substr(Module::removeWhisper($bot->message['text']), 5));
		}
	}
}
