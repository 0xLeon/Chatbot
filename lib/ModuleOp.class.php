<?php
class ModuleOp extends Module {
	public function destruct() {

	}
	public function handle(Bot $bot) {
		if (!Core::isOp($bot->lookUpUserID())) return;
		
		if ($bot->message['text'] == '!shutdown') exit;
	}
}
