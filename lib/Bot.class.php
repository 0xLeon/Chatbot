<?php

class Bot {
	protected $connection = null;
	protected $id = 0;
	protected $queue = array();
	public function __construct() {
		$this->connection = new Connection(SERVER, ID, null, null, HASH);
		$this->connection->getSecurityToken();
		preg_match("/new Chat\(([0-9]+), ([0-9]), '(.*?)'\)/", $this->connection->joinChat(), $matches);
		$this->id = $matches[1];
		Core::log()->info = 'Successfully connected to server, reading messages from: '.$this->id;
	}
	
	public function getConnection() {
		return $this->connection;
	}
	
	public function read() {
		$data = self::$api->readMessages(self::$id);
		if (count($data['messages'])) {
			$id = end($data['messages']);
			$this->$id = $id['id'];
		}
		return $data;
	}
	
	public function send() {
		if (file_exists('say')) {
			$data = explode("\n", file_get_contents(DIR.'say'));
			unlink(DIR.'say');
			foreach ($data as $d) {
				if (!empty($d)) self::$queue[] = $d;
			}
		}
	}
	
	public function queue($message) {
		if (Core::config()->config['stfu']) return;
		$data = '';
		if (file_exists(DIR.'say')) {
			$data = file_get_contents(DIR.'say')."\n";
		}
		file_put_contents(DIR.'say', $data.$message);
	}
}