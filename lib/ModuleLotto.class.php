<?php

class ModuleLotto extends Module {

	// todo: save all wins in database.
	private $numbers = array();
	private $players = array();
	public $gameActive = false;
	public $timeBefore = 0;
	protected $numbers = 0, $max = 0;

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

		if (substr($bot->message['text'], 0, 6) == '!tipp ' && $this->gameActive) {
			$numbers = str_replace('!tipp', '', $bot->message['text']);
			$numbers = explode(' ', $numbers);
			$this->regUser($bot->message['usernameraw'], $numbers);
		} else {
			$bot->queue('Es läuft im Moment kein Lottospiel. Benutze "!lotto" um dies zu ändern!');
		}
	}

	public function startLotto($numbers = 6, $max = 49) {
		$this->gameActive = true;
		$this->numbers = $numbers;
		$this->max = $max;

		$bot->queue('Yay! Die Lottorunde beginnt. Wir spielen '.$this->numbers.' aus '.$this->max.'!');
		$bot->queue('Postet einfach eure Tipps mit z.B. "!tipp 13 25 29 19 20 30".');
		$this->randNumbers();
		$this->startRegTime();
	}

	public function randNumbers() {
		do {
			$number = rand(1, $this->max);
			if (!in_array($number, $this->drawnNumbers)) $this->drawnNumbers[] = $rand;
		}
		while (count($this->numbers) < $this->numbers);
	}

	public function shoutWinners() {
		$bot->queue('Die Lottorunde ist vorbei! Folgende User haben getippt: ' . implode(', ', $this->players));
		$bot->queue('Die gezogenen Zahlen sind ' . implode(', ', $this->drawnNumbers));
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
		$this->numbers = $this->max = 0;
		$this->drawnNumbers = array();
		$this->players = array();
		$this->gameActive = false;
		$this->timeBefore = 0;
	}

	public function startRegTime() {
		$this->timeBefore = time();
	}

	public function regUser($nickname, array $numbers) {
		$numbers = array_unique($numbers);
		foreach ($numbers as $key => $val) if ($val > $this->max) unset($numbers[$key]);
		if (count($numbers) != $this->numbers) {
			$bot->queue('/whisper "' . $nickname . '" Deine Zahlen sind ungültig.');
		}
		else {
			$bot->success();
		}
	}

}

?>