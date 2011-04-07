<?php

class ModuleUtil extends Module {
	public function destruct() {
	
	}
	
	public function handle(Bot $bot) {
		if (Module::removeWhisper($bot->message['text']) == '!mycolor') {
			preg_match_all('/color: #[0-9a-fA-F]{6}/', $bot->message['username'], $matches);
			$bot->queue('/whisper "'.$bot->message['usernameraw'].'" /color '.substr($matches[0][0], 7).' '.substr($matches[0][count($matches[0])-1], 7));
		}
		else if ($bot->message['text'] == '!info') {
			$bot->queue(Core::language()->util_information.':');
			$bot->queue(Core::language()->util_since.": ".date('d.m.Y H:i:s', TIME));
			$bot->queue(Core::language()->util_sent.": ".$bot->messageCount.' ('.round($bot->messageCount / (time() - TIME) * 60, 4).'/m)');
			$bot->queue(Core::language()->util_got.": ".$bot->sendCount.' ('.round($bot->sendCount / (time() - TIME) * 60, 4).'/m)');
		}
	}
}