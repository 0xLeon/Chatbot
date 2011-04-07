<?php

class ModulePoll extends Module {
	protected $yes = 0;
	protected $no = 0;
	protected $voteActive = false;
	protected $voted = array();
	protected $voteStart = 0;
	
	public function destruct() {

	}	
	
	protected function reset() {
		$this->yes = $this->no = $this->voteStart = 0;
		$this->voteActive = false;
		$this->voted = array();
	}
	public function handle(Bot $bot) {
		if ($this->voteActive && ($this->voteStart + 30) < time()) {
			$this->end($bot);
		}
		if (substr($bot->message['text'], 0, 6) == '!vote ' && !$this->voteActive) {
			$bot->queue('Umfrage: '.substr($bot->message['text'], 6));
			$bot->queue('!yes für ja, !no für nein');
			$bot->queue('Ihr habt 30 Sekunden Zeit um abzustimmen');
			$this->voteActive = true;
			$this->voteStart = time();
		}
		else if (substr($bot->message['text'], 0, 6) == '!vote ') {
			Bot::queue('/whisper "'.$bot->message['usernameraw'].'" Es läuft gerade eine Abstimmung');
		}
		else if ($this->voteActive && $bot->message['text'] == '!yes' && !in_array($bot->message['usernameraw'], $this->voted)) {
			$this->yes++;
			$this->voted[] = $bot->message['usernameraw'];
			$bot->success();
		}
		else if ($this->voteActive && $bot->message['text'] == '!no' && !in_array($bot->message['usernameraw'], $this->voted)) {
			$this->no++;
			$this->voted[] = $bot->message['usernameraw'];
			$bot->success();
		}
	}
	
	protected function end(Bot $bot) {
		$bot->queue('Die Abstimmung ist beendet');
		$bot->queue('Es haben '.$this->yes.' Leute für Ja und '.$this->no.' für Nein gestimmt');
		$this->reset();
	}
}
?>
