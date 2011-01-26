<?php
class ModuleToys extends Module {
	public function destruct() {
	
	}
	
	public function handle(Bot $bot) {
		if ($bot->message['text'] == '!info') {
			$bot->queue("Information:");
			$bot->queue("Online seit: ".date('d.m.Y H:i:s', TIME));
		}
		else if ($bot->message['text'] == '!ping') {
			$bot->queue('['.$bot->message['usernameraw'].'] !pong');
		}
	}
}