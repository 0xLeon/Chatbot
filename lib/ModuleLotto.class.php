<?php

class ModuleLotto extends Module {

	// todo: save all wins in database.
	private $numbers = array();
	private $players = array();
	public $gameActive = false;
	public $timeBefore = 0;

	public function destruct() {

	}

	public function handle(Bot $bot) {
		if ($this->gameActive && time() >= ($this->timeBefore + 60)) {
			$this->shoutWinners();
		}
		if ($bot->message['text'] == '!lotto' && !$this->game) {
			$this->startLotto();
		} else {
			$bot->queue('Es läuft bereits ein Lottospiel!');
			$bot->queue('Postet einfach eure Tipps mit z.B. "!tipp 13 25 29 19 20 30 49 12". Die vorletzte Zahl ist die Zusatzzahl und die letzte die Superzahl');
		}
		if (preg_match('~\!tipp [0-9]+', $bot->message['text']) && $this->gameActive) {
			$numbers = str_replace('!tipp', '', $bot->message['text']);
			$numbers = explode(' ', $numbers);
			$this->regUser($bot->message['usernameraw'], $numbers);
		} else {
			$bot->queue('Es läuft immoment kein Lottospiel. Benutze !lotto um dies zu ändern!');
		}
	}

	public function startLotto($max = 49) {
		$this->game = true;

		$bot->queue('Yay! Die Lottorunde beginnt. Wir spielen 6 (+2) aus 49!');
		$bot->queue('Postet einfach eure Tipps mit z.B. "!tipp 13 25 29 19 20 30 49 12". Die vorletzte Zahl ist die Zusatzzahl und die letzte die Superzahl');
		$this->randNumbers();
		$this->startRegTime();
	}

	public function randNumbers() {
		for ($i = 0; $i < 9; $i++) {
			$this->numbers[$i] = mt_rand(0, $max);
		}
	}

	public function shoutWinners() {
		$bot->queue('Die Lottorunde ist vorbei! Folgende User haben getippt: ' . implode(', ', $this->players));
		$bot->queue('Die gezogenen Zahlen sind ' . implode(', ', $this->numbers));
		foreach ($this->players as $player => $number) {
			$reward = 0;
			foreach ($this->numbers as $index => $value) {
				if ($value == $numbers)
					$reward++;
			}
			$bot->queue($player . ': ' . implode(', ', $number) . ' - ' . $reward . ' eDönerGutscheine'); // maybe randomize currency?
		}
		$bot->queue('Eventuell können die Gutscheine irgendwannmal eingelöst werden :)');
		$this->reset();
	}

	public function reset() {
		$this->numbers = array();
		$this->players = array();
		$this->gameActive = false;
		$this->timeBefore = 0;
	}

	public function startRegTime() {
		$this->timeBefore = time();
	}

	public function regUser($nickname, array $numbers) {
		foreach ($numbers as $index => $value) {
			$this->players[$nickname][$index] = $value;
		}
		$bot->queue('/whisper "' . $nickname . '" Deine Zahlen wurden erfolgreich registriert.');
	}

}

?>