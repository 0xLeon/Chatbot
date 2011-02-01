<?php
class ModuleOp extends Module {
	public function destruct() {

	}
	public function handle(Bot $bot) {		
		if ($bot->message['text'] == '!shutdown') {
			if (!Core::isOp($bot->lookUpUserID())) return $bot->denied();
			exit;
		}
		else if (substr($bot->message['text'],0,7) == '!lookup') {
			if (!Core::isOp($bot->lookUpUserID())) return $bot->denied();
			echo $bot->getConnection()->lookUp(substr($bot->message['text'],8));
		}
		else if (substr(Module::removeWhisper($bot->message['text']), 0, 4) == '!say') {
			if (!Core::isOp($bot->lookUpUserID())) return $bot->denied();
			$bot->queue(substr(Module::removeWhisper($bot->message['text']), 5));
		}
		else if ($bot->message['text'] == '!loaded') {
			if (!Core::isOp($bot->lookUpUserID())) return $bot->denied();
			$bot->queue('/whisper "'.$bot->message['usernameraw'].'" Folgende Module sind geladen: '.implode(', ', array_keys(Core::getModules())));
		}
	}
}
