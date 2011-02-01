<?php

abstract class Module {
	abstract public function destruct();
	abstract public function handle(Bot $bot);
	public static function removeWhisper($message) {
	
		return str_replace('flüstert an '.NAME.': ', '', $message);
	}
	public function __toString() {
		return get_called_class();
	}
}
