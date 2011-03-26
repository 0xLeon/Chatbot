<?php

class ModuleLotto extends Module {

	// todo: save all wins in database.
	protected $drawnNumbers = array();
	protected $players = array();
	protected $gameActive = false;
	protected $timeStart = 0;
	protected $numbers = 0, $max = 0;
	protected $bot;
	
	public function destruct() {

	}

	public function handle(Bot $bot) {
		$this->bot = $bot;
		if ($this->gameActive && ($this->timeStart + 60) < time()) {
			$this->shoutWinners();
		}

		if ($this->bot->message['text'] == '!lotto' && !$this->gameActive) {
			$this->startLotto();
		}
		else if ($this->bot->message['text'] == '!lotto') {
			$this->bot->queue('Es läuft bereits ein Lottospiel!');
			$this->bot->queue('Postet einfach eure Tipps mit z.B. "!tipp 13 25 29 19 20 30"');
		}

		if (substr($this->bot->message['text'], 0, 6) == '!tipp ' && $this->gameActive) {
			$numbers = str_replace('!tipp ', '', StringUtil::trim($this->bot->message['text']));
			$numbers = preg_replace('~\s\s+~', '', $numbers);
			$numbers = explode(' ', $numbers);
			$this->regUser($this->bot->message['usernameraw'], $numbers);
		}
		else if (substr($this->bot->message['text'], 0, 6) == '!tipp ') {
			$this->bot->queue('Es läuft im Moment kein Lottospiel. Benutze "!lotto" um dies zu ändern!');
		}
	}

	public function startLotto($numbers = 6, $max = 49) {
		$this->gameActive = true;
		$this->numbers = $numbers;
		$this->max = $max;

		$this->bot->queue('Yay! Die Lottorunde beginnt. Wir spielen ' . $this->numbers . ' aus ' . $this->max . '!');
		$this->bot->queue('Postet einfach eure Tipps mit z.B. "!tipp 13 25 29 19 20 30".');
		$this->randNumbers();
		$this->timeStart = time();
	}

	public function randNumbers() {
		do {
			$number = rand(1, $this->max);
			if (!in_array($number, $this->drawnNumbers)) {
				$this->drawnNumbers[] = $number;
			}
		}
		while (count($this->drawnNumbers) < $this->numbers);
	}

	public function shoutWinners() {
		asort($this->players);
		asort($this->drawnNumbers);
		
		$this->bot->queue('Die Lottorunde ist vorbei! Folgende User haben getippt: ' . implode(', ', $this->players));
		$this->bot->queue('Die gezogenen Zahlen sind ' . implode(', ', $this->drawnNumbers));
		foreach ($this->players as $player => $numbers) {
			$reward = 0;
			asort($numbers);
			foreach ($numbers as $id => $number) {
				$value = $this->numbers[$id];
				if ($value == $number)
					$reward++;
			}
			$this->bot->queue($player . ': ' . implode(', ', $numbers) . ' - ' . $reward . ' Punkte');
		}
		$this->reset();
	}

	public function reset() {
		$this->numbers = $this->max = 0;
		$this->drawnNumbers = array();
		$this->players = array();
		$this->gameActive = false;
		$this->timeStart = 0;
	}

	public function regUser($nickname, array $numbers) {
		$numbers = array_unique($numbers);
		foreach ($numbers as $key => $val) {
			if ($val > $this->max) {
				unset($numbers[$key]);
			} else {
				$this->players[$nickname][$key] = $val;
			}
		}
		if (count($numbers) != $this->numbers) {
			$this->bot->queue('/whisper "' . $nickname . '" Deine Zahlen sind ungültig.');
			unset($this->players[$nickname]);
		} else {
			$this->bot->success();
		}
	}
}
?>