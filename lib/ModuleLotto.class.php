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

		if ($bot->message['text'] == '!lotto' && !$this->gameActive) {
			$this->startLotto();
		} else {
			$bot->queue('Es läuft bereits ein Lottospiel!');
			$bot->queue('Postet einfach eure Tipps mit z.B. "!tipp 13 25 29 19 20 30"');
		}

		if (preg_match('~!tipp [0-9]+', $bot->message['text']) && $this->gameActive) {
			$numbers = str_replace('!tipp', '', $bot->message['text']);
			$numbers = explode(' ', $numbers);
			$this->regUser($bot->message['usernameraw'], $numbers);
		} else {
			$bot->queue('Es läuft im Moment kein Lottospiel. Benutze "!lotto" um dies zu ändern!');
		}
	}

	public function startLotto($max = 49) {
		$this->gameActive = true;

		$bot->queue('Yay! Die Lottorunde beginnt. Wir spielen 6 aus 49!');
		$bot->queue('Postet einfach eure Tipps mit z.B. "!tipp 13 25 29 19 20 30".');
		$this->randNumbers();
		$this->startRegTime();
	}

	public function randNumbers($max = 49) {
		for ($i = 0; $i < 7; $i++) {
			$rand = mt_rand(0, $max);
			if (!array_search($rand, $this->numbers))
				$this->numbers[$i] = $rand;
			else
				$i--;
		}
	}

	public function shoutWinners() {
		$bot->queue('Die Lottorunde ist vorbei! Folgende User haben getippt: ' . implode(', ', $this->players));
		$bot->queue('Die gezogenen Zahlen sind ' . implode(', ', $this->numbers));
		asort($this->players);
		asort($this->numbers);
		foreach ($this->players as $player => $numbers) {
			$reward = 0;
			asort($numbers);
			foreach ($numbers as $id => $number) {
				$value = $this->numbers[$id];
				if ($value == $number)
					$reward++;
			}
			$bot->queue($player . ': ' . implode(', ', $numbers) . ' - ' . $reward . ' eDönerGutscheine'); // maybe randomize currency?
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
		for ($i = 0; $i < 7; $i++) { // to avoid > 6 numbers in array
			if (!array_search($value, $this->players[$nickname]))
				$this->players[$nickname][$i] = $numbers[$i];
		}
		$bot->queue('/whisper "' . $nickname . '" Deine Zahlen wurden erfolgreich registriert.');
	}

}

?>
