<?php
/**
 * Quiz
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
class ModuleQuiz extends Module {
	public $results = array();
	const TIME_W_ANSWERS = 15;
	const TIME_WO_ANSWERS = 30;
	public static $letters = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm');
	public $right = '';
	public $questionNo = 0;
	public $questionEnd = 0;
	public $questionAnswers = array();
	
	public function destruct() {

	}
	
	public function handle(Bot $bot) {
		if (Module::removeWhisper($this->bot->message['text']) == '!quiz start') {
			if (Core::compareLevel($this->bot->lookUpUserID(), 'quiz.leader')) {
				$this->bot->queue($this->bot->message['usernameraw'].' hat ein Quiz gestartet');
				$this->bot->queue('Du hast für Fragen mit Antwortmöglichkeiten '.self::TIME_W_ANSWERS.' Sekunden');
				$this->bot->queue('Wenn keine Antworten zur Verfügung stehen sind es '.self::TIME_WO_ANSWERS.' Sekunden');
				$this->bot->queue('Die Antworten sind an mich zu flüstern');
				$this->bot->queue('Die Antwort wird nur gezählt, wenn auch Groß-/Kleinschreibung passt');
				$this->bot->queue('Viel Spaß');
				$this->start();
			}
			else {
				$this->bot->denied();
			}
		}
		else if (Module::removeWhisper($this->bot->message['text']) == '!quiz end') {
			if (Core::compareLevel($this->bot->lookUpUserID(), 'quiz.leader')) {
				$this->bot->queue($this->bot->message['usernameraw'].' hat das Quiz beendet. ');
				$this->end();
			}
			else {
				$this->bot->denied();
			}
		}
		else if (substr(Module::removeWhisper($this->bot->message['text']), 0, 10) == '!question ') {
			if (Core::compareLevel($this->bot->lookUpUserID(), 'quiz.leader')) {
				$this->question(substr(Module::removeWhisper($this->bot->message['text']), 10));
			}
			else {
				$this->bot->denied();
			}
		}
	}
	
	public function start() {
		Core::log()->quiz = 'Quiz was started ('.$this->bot->message['usernameraw'].')';
	}
	
	public function end() {
		Core::log()->quiz = 'Quiz was ended ('.$this->bot->message['usernameraw'].')';
		Core::log()->quiz = 'Results:';
		asort($this->results);
		$i = 0;
		foreach ($this->results as $username => $points) {
			echo '#'.++$i.' '.$username.' => '.$points.' Points';
		}
		
		$this->results = array();
		$this->questionNo = 0;
	}
	
	public function question($data) {
		$data = explode('|', trim($data));
		$question = $data[0];
		$this->right = strtolower($data[1]);
		unset($data[0], $data[1]);
		$answers = array_values($data);
		Core::log()->quiz = 'Question: '.$question;
		Core::log()->quiz = 'Right is: '.$this->right;
		$line = '';
		for ($i = 0, $max = count($answers); $i < $max; $i++) {
			$line .= self::$letters[$i+1].') '.$answers[$i];
		}
		Core::log()->quiz = 'Possible answers: '.$line;
		
		$this->bot->queue('Frage '.++$this->questionNo.': '.$question);
		if ($line != '') {
			$this->bot->queue($line);
			$this->questionEnd = time() + TIME_W_ANSWERS;
		}
		else {
			$this->bot->queue('Keine Antwortvorgaben');
			$this->questionEnd = time() + TIME_W_ANSWERS;
		}
	}
	
	public function questionEnd() {
		Core::log()->quiz = 'Times over';
		Core::log()->quiz = 'Answers by: '.implode(', ', $this->questionAnswers);
		$this->bot->queue('Die Zeit ist um');
		$this->bot->queue('Es gab Antworten von '.implode(', ', $this->questionAnswers));
		$this->questionAnswers = array();
	}
}
?>