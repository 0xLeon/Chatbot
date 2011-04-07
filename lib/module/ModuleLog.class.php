<?php

class ModuleLog extends Module {
	public function destruct() {

	}
	public function handle(Bot $bot) {
		Core::log()->message = $bot->message['usernameraw'].': '.$bot->message['text'];
	}
}
?>
