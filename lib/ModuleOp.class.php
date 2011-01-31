<?php
class ModuleOp extends Module {
	public function destruct() {

	}
	public function handle(Bot $bot) {
		if (!Core::isOp($bot->lookUpUserID())) return;
		
		if ($bot->message['text'] == '!shutdown') exit;
		if (substr($bot->message['text'],0,7) == '!lookup') {
			echo $bot->getConnection()->lookUp(substr($bot->message['text'],8));
		}
	}
}
