<?php
/**
 * Posts a message when a user joins
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
class ModuleRose extends Module {
	public $config = null;

	public function __construct() {
		$this->config = new Config('rose', array());
		$this->config->write();
	}
	
	public function destruct() {
		$this->config->write();
	}
	
	public function handle(Bot $bot) {
		if ($bot->message['id'] % 500 == 0) $this->config->write();
		if ($bot->message['type'] == Bot::JOIN) {
			$userID = $bot->lookUpUserID();
			if (!isset($this->config->config[$userID])) {
				$this->config->config[$userID] = array('got' => 0, 'has' => 3, 'lastjoin' => 0, 'joins' => 0);
			}
			if ($this->config->config[$userID]['lastjoin'] + 86400 < time()) {
				$this->config->config[$userID]['lastjoin'] = time();
				$this->config->config[$userID]['joins']++;
				if ($this->config->config[$userID]['joins'] >= 3) {
					$this->config->config[$userID]['joins'] = 0;
					$this->config->config[$userID]['has']++;
				}
			}
		}
		if (substr($bot->message['text'], 0, 6) == '!roses') {
			if ($bot->message['text'] == '!roses') {
				$userID = $bot->lookUpUserID();
				$username = $bot->message['usernameraw'];
			}
			else {
				$username = substr($bot->message['text'], 7);
				$userID = $bot->lookUpUserID($username);
			}
			if (!isset($this->config->config[$userID])) {
				$this->config->config[$userID] = array('got' => 0, 'has' => 3, 'lastjoin' => 0, 'joins' => 0);
			}
			$bot->queue('['.$username.'] hat bisher '.$this->config->config[$userID]['got']. ' Rosen erhalten und kann noch '.$this->config->config[$userID]['has'].' Stück verteilen.');
		}
		else if (substr($bot->message['text'], 0, 6) == '!rose ') {
			$userID = $bot->lookUpUserID();
			if (!isset($this->config->config[$userID])) {
				$this->config->config[$userID] = array('got' => 0, 'has' => 3, 'lastjoin' => 0, 'joins' => 0);
			}
			if ($this->config->config[$userID]['has'] > 0) {
				$username = substr($bot->message['text'], 6);
				$to = $bot->lookUpUserID($username);
				
				if ($to) {
					if ($to != $userID) {
						if (!isset($this->config->config[$to])) {
							$this->config->config[$to] = array('got' => 0, 'has' => 3, 'lastjoin' => 0, 'joins' => 0);
						}
						$this->config->config[$to]['got']++;
						$bot->queue('['.$bot->message['usernameraw'].'] hat eine Rose an '.$username.' gegeben');
					}
					else {
						$bot->queue('/whisper "'.$bot->message['usernameraw'].'" Du kannst dir nicht selber eine Rose geben');
					}
				}
				else {
					$bot->queue('/whisper "'.$bot->message['usernameraw'].'" '.Core::language()->get('user_not_found', array('{user}' => $username)));
				}
			}
			else {
				$bot->queue('/whisper "'.$bot->message['usernameraw'].'" Du hast keine Rosen');
			}
		}
	}
}
?>