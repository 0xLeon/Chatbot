<?php
/**
 * Module-related function
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
abstract class Module {
	/**
	 * Called on shutdown
	 *
	 * @return	void
	 */
	abstract public function destruct();
	
	/**
	 * Handles a message
	 *
	 * @param	Bot	$bot		Bot-instance
	 * @return	void
	 */
	abstract public function handle(Bot $bot);
	
	/**
	 * Removes the "flüstert an XXX"
	 *
	 * @param	string	$message		message to clean
	 * @return	string				cleaned message
	 */
	public static function removeWhisper($message) {
		return str_replace('flÃ¼stert an '.NAME.': ', '', $message);
	}
	
	/**
	 * Returns the module-name
	 *
	 * @return	string	Module-name
	 */
	public function __toString() {
		return get_called_class();
	}
}
