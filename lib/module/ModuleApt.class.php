<?php
/**
 * Apt easteregg
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
class ModuleApt extends Module {
	public function destruct() {

	}

	public function handle(Bot $bot) {
		if (substr($bot->message['text'], 0, 7) != 'apt-get') return;
		$text = explode(' ', substr($bot->message['text'], 7), 2);
		
		switch ($text[0]) {
			case 'moo':
				$bot->queue('...."Have you mooed today?"...');
			break;
			case 'install':
				if (!Core::compareLevel($bot->lookUpUserID(), 'op.load')) return $bot->denied();
				if (stripos($text[1], '--reinstall') !== -1) {
					$text[1] = str_replace('--reinstall', '', $text[1]);
					Core::log()->info = $this->message['usernameraw'].' reloaded a module';
					$result = Core::reloadModule(trim($text[1]));
				}
				else {
					Core::log()->info = $this->message['usernameraw'].' loaded a module';
					$result = Core::loadModule(trim($text[1]));
				}
				
				if (!is_int($result)) {
					$this->success();
				}
				else {
					$name = 'module_error_'.$result;
					$this->queue('/whisper "'.$this->message['usernameraw'].'" '.Core::language()->$name);
				}
			break;
			case 'remove':
			case 'purge':
				$text[1] = str_replace('--purge', '', $text[1]);
				if (!Core::compareLevel($bot->lookUpUserID(), 'op.load')) return $bot->denied();
				Core::log()->info = $this->message['usernameraw'].' unloaded a module';
				$result = Core::unloadModule(trim($text[1]));
				if (!is_int($result)) {
					$this->success();
				}
				else {
					$name = 'module_error_'.$result;
					$this->queue('/whisper "'.$this->message['usernameraw'].'" '.Core::language()->$name);
				}
			break;
		}
	}
}
?>