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
		else if (substr(Module::removeWhisper($bot->message['text']), 0, 4) == '!say') {
			$bot->queue(substr(Module::removeWhisper($bot->message['text']), 5));
		}
		else if ($bot->message['text'] == '!loaded') {
			$bot->queue('/whisper "'.$bot->message['usernameraw'].'" Folgende Module sind geladen: '.implode(', ', array_keys(Core::getModules())));
		}
	}
}
