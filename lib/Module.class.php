<?php

abstract class Module {
	abstract public function destruct();
	public static function removeWhisper($message) {
	
		return str_replace('flüstert an '.NAME.': ', '', $message);
	}
}
