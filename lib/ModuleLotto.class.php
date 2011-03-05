<?php

class ModuleLotto extends Module {

	public static $numbers = array();
	public static $players = array();
	public static $game = false;

	public function destruct() {

	}

	public function handle(Bot $bot) {
		if ($bot->message['text'] == '!lotto') {
			$this->startLotto();
		}
		if(preg_match('~\!tipp [0-9]+', $bot->message['text']) && self::$game){
			$numbers = preg_replace('!tipp', '', $bot->message['text']);
			$numbers = explode(' ', $numbers);
			$this->regUser($bot->message['usernameraw'], $numbers);
		}
	}

	public function startLotto($max = 49) {
		self::$game = true;

		$bot->queue('Yay! Die Lottorunde beginnt. Wir spielen 6 (+2) aus 49!');
		$bot->queue('Postet einfach eure Tipps mit z.B. "!tipp 13 25 29 19 20 30 49 12". Die vorletzte Zahl ist die Zusatzzahl und die letzte die Superzahl');
		$this->randNumbers();
		$this->startRegTime();
	}

	public function randNumbers() {
		for ($i = 0; $i < 9; $i++) {
			self::$numbers[$i] = mt_rand(0, $max);
		}
	}

	public function shoutWinners() {
		self::$game = false;
		$bot->queue('Die Lottorunde ist vorbei! Folgende User haben getippt: ' . implode(', ', self::$players));
		$bot->queue('Die gezogenen Zahlen sind ' . implode(', ', self::$numbers));
		foreach(self::$numbers as $index => $value){
			foreach(self::$players as $player => $number){
				// ...
			}
		}
	}

	public function startRegTime() {
		$time = time();
		$time_after = time() + 60;
		while (1) {
			if (time() >= $time_after)
				continue;
		}
		$this->shoutWinners();
	}

	public function regUser($nickname, array $numbers){
		$bot->queue('/whisper "'.$nickname.'" Deine Zahlen wurden erfolgreich registriert.');
	}

}

?>