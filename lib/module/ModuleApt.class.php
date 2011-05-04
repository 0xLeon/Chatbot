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
		$text = substr($bot->message['text'], 7);
	}
}
?>