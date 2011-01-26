<?php

abstract class Module {
	abstract public function destruct();
	public function removeWhisper($message) {
	
		return str_replace('flüstert an '.NAME.': ', '', $message);
	}
}
