<?php

class ModuleUtil extends Module {
	public function destruct() {
	
	}
	
	public function handle(Bot $bot) {
		if (Module::removeWhisper($bot->message['text']) == '!mycolor') {
			preg_match_all('/color: #[0-9a-fA-F]{6}/', $bot->message['username'], $matches);
			$bot->queue('/whisper "'.$bot->message['usernameraw'].'" /color '.substr($matches[0][0], 7).' '.substr($matches[0][count($matches[0])-1], 7));
		}
	}
}