<?php
/**
 * Module-related function
 *
 * @author	Tim D�sterhus
 * @copyright	2010 - 2011 Tim D�sterhus
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
	 * Removes the "fl�stert an XXX"
	 *
	 * @param	string	$message		message to clean
	 * @return	string				cleaned message
	 */
	public static function removeWhisper($message) {
		return str_replace('flüstert an '.NAME.': ', '', $message);
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
