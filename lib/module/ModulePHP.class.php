<?php
/**
 * Evals given PHP-Code
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
class ModulePHP extends Module {
	public function destruct() {

	}
	public function handle(Bot $bot) {		
		if (substr(Module::removeWhisper($bot->message['text']), 0, 5) == '!php ') {
			if (!Core::compareLevel($bot->lookUpUserID(), 'php.eval')) return $bot->denied();
			Core::log()->php = $bot->message['usernameraw'].' used '.Module::removeWhisper($bot->message['text']);
			eval(substr(Module::removeWhisper($bot->message['text']), 5));
		}
	}
}
?>