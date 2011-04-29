<?php
/**
 * Logs all messages
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
class ModuleLog extends Module {
	public function destruct() {

	}
	public function handle(Bot $bot) {
		Core::log()->message = $bot->message['usernameraw'].': '.$bot->message['text'];
	}
}
?>