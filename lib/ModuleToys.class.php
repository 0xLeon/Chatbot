<?php
class ModuleToys extends Module {
	public static $eight = array('Wenn Gott es will', 'Nein!', 'Ja!', 'Lass mich schlafen', 'Auf jeden Fall',
		'Ich muss darüber nachdenken', 'Natürlich nicht', 'Frag morgen nochmal', 'Meine Glaskugel ist derzeit in Reparatur',
		'Vielleicht', 'Hör auf! Hör auf! Hör auf!', 'Was war nochmal die Frage?');
	public static $KimKola = array('War das nicht irgendein berühmter Schauspieler?', 'Irgendwoher kenn ich diesen Namen...', 'Hat mal wer ne Cola für mich?', 'Das wird "Cola" geschrieben ihr dödel!');

	public function destruct() {

	}

	public function handle(Bot $bot) {
		if (preg_match('~^\.\.\.+$~', $bot->message['text'])) {
			$bot->queue('/me verwandelt sich in Pacman und isst alle Punkte auf');
		}
		if(strstr($bot->message['text'], 'TimWolla')){
			$bot->queue('KimKola? '.self::$KimKola(rand(0, (count(self::$KimKola)-1))));
		}
		if ($bot->message['text'] == '!info') {
			$bot->queue("Information:");
			$bot->queue("Online seit: ".date('d.m.Y H:i:s', TIME));
			$bot->queue("Gelesene Nachrichten: ".$bot->messageCount);
		}
		else if (Module::removeWhisper($bot->message['text']) == '!ping') {
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
			$send = self::$eight[$sum % count(self::$eight)];
			$bot->queue('['.$bot->message['usernameraw'].'] '.$send);
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
