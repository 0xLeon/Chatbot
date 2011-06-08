<?php
/**
 * Several "Toys" like an Eightball or a dice
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
class ModuleToys extends Module {
	public static $eight = 12;
	public $rouletteBullet = 0;
	public $rouletteStatus = array();

	public function destruct() {

	}

	public function handle(Bot $bot) {
		if ($this->rouletteBullet == 0) $this->rouletteBullet = rand(1,6);
	/*	if (preg_match('~^\.\.\.+$~', $bot->message['text'])) {
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
		else if ($bot->message['type'] == Bot::MODERATE && preg_match('~bis [0-3][0-9].[01][0-9].([0-9]+)~', $bot->message['text'], $matches)) {
			if ($matches[1] > (date('Y') + 9000)) {
				$bot->queue('Its over ninethousand!');
			}
		}
		else */if ($bot->message['text'] == '!shot' || 
$bot->message['text'] == '!spin') {
			if ($bot->message['text'] == '!spin') {
				$this->rouletteBullet = rand(1,6);
				$cost = 4;
				$message = '['.$bot->message['usernameraw'].'] dreht den Zylinder und drückt ab…';
			}
			else {
				$cost = 1;
				$message = '['.$bot->message['usernameraw'].'] drückt ab…';
			}
			if (!isset($this->rouletteStatus[$bot->message['usernameraw']])) $this->rouletteStatus[$bot->message['usernameraw']] = 0;
			$this->rouletteBullet--;
			if ($this->rouletteBullet == 0) {
				
$this->rouletteStatus[$bot->message['usernameraw']] += $cost;
				$bot->queue($message.'Boooom');
				$bot->queue('/tmute '.$bot->message['usernameraw'].' '.($this->rouletteStatus[$bot->message['usernameraw']]));
				
				$this->rouletteBullet = rand(1,6);
			}
			else $bot->queue($message.'Klack, nichts passiert');
		}
	}
}
?>
