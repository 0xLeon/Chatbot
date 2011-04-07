<?php
class ModuleToys extends Module {
	public static $eight = 11;

	public function destruct() {

	}

	public function handle(Bot $bot) {
		if (preg_match('~^\.\.\.+$~', $bot->message['text'])) {
			$bot->queue('/me '.Core::language()->toys_pacman);
		}

		if (Module::removeWhisper($bot->message['text']) == '!ping') {
			$bot->queue('/whisper "'.$bot->message['usernameraw'].'" !pong');
		}
		else if (substr($bot->message['text'], 0, 5) == '!dice') {
			$command = substr($bot->message['text'], 5);
			$command = explode('d', $command, 2);
			$command[0] = min(10, $command[0]);
			if (count($command) > 1) {
				while ($command[0]--) {
					$results[] = rand(1, $command[1]);
				}
			}
			else {
				 $results[] = rand(1,6);
			}
			sort($results);
			$bot->queue(implode(', ', $results).' wurde von '.$bot->message['usernameraw'].' gewuerfelt');
		}
		else if ($bot->message['text'] == '!kuschel') {
			$bot->queue('/me kuschelt sich an '.$bot->message['usernameraw']);
		}
		else if (substr($bot->message['text'], 0, 2) == '!8') {
			$data = str_split(substr(strtolower($bot->message['text']), 2));
			$sum = 0;
			foreach($data as $char) $sum += ord($char);
			$send = 'toys_eight_'.($sum % self::$eight);
			$bot->queue('['.$bot->message['usernameraw'].'] '.Core::language()->$send);
		}
		else if (substr($bot->message['text'], 0, 5) == '!user') {
			$text = str_split(substr(strtolower($bot->message['text']), 5));
			$sum = 0;
			foreach($text as $char) $sum += ord($char);
			$send = $bot->data['users'][$sum % count($bot->data['users'])]['usernameraw'];
			$bot->queue('['.$bot->message['usernameraw'].'] '.$send);
		}
	}
}
