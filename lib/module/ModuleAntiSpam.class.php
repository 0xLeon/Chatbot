<?php
/**
 * Provides basic antispam functions
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
class ModuleAntiSpam extends Module implements AlwaysFire {
	protected $config = null;
	protected $caps = array();

	public function __construct() {
		$this->config = new Config('antispam', array('capspercent' => 80));
		$this->config->write();
	}

	public function destruct() {
		$this->config->write();
	}

	public function handle(Bot $bot) {
		if ($bot->message['id'] % 500 == 0) $this->config->write();
		if ($bot->message['usernameraw'] == NAME) return;
		// TODO: Command for config-change
		$this->caps($bot);
	}
	
	protected function caps(Bot $bot) {
		if (!isset($this->caps[$bot->message['usernameraw']])) {
			$this->caps[$bot->message['usernameraw']] = array('kills' => 0, 'counter' => 0, 'lastreset' => 0);
		}
		if ($this->caps[$bot->message['usernameraw']]['lastreset'] < (time() - 3600)) {
			if ($this->caps[$bot->message['usernameraw']]['kills'] > 0) $this->caps[$bot->message['usernameraw']]['kills']--;
		}
		if ($this->caps[$bot->message['usernameraw']]['lastreset'] < (time() - 120)) {
			if ($this->caps[$bot->message['usernameraw']]['counter'] > 0) $this->caps[$bot->message['usernameraw']]['counter']--;
			$this->caps[$bot->message['usernameraw']]['lastreset'] = time();
		}
		
		$capscount = 0;
		// don't count whitespace
		$message = preg_replace('~[\s!?\.]~', '', $bot->message['text']);
		$charcount = strlen($message);
		$chars = str_split($bot->message['text']);
		foreach ($chars as $char) {
			if (preg_match('~[A-Z]~', $char)) $capscount++;
		}

		if (strlen($message) < 6 || ($capscount / $charcount * 100) < $this->config->config['capspercent']) return;
		
		$this->caps[$bot->message['usernameraw']]['counter']++;
		$bot->queue('/whisper "'.$bot->message['usernameraw'].'" '.Core::language()->antispam_no_caps);
		if ($this->caps[$bot->message['usernameraw']]['counter'] > 2) {
			$this->caps[$bot->message['usernameraw']]['counter'] = 0;
			$bot->queue('/tmute '.$bot->message['usernameraw'].' '.pow(2, $this->caps[$bot->message['usernameraw']]['kills']++));
		}
	}
}
?>