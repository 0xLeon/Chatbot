<?php

class Bot {
	protected $connection = null;
	protected $id = 0;
	protected $queue = array();
	protected $child = 0;
	public function __construct() {
		$this->connection = new Connection(SERVER, ID, null, null, HASH);
		$this->connection->getSecurityToken();
		preg_match("/new Chat\(([0-9]+), ([0-9]), '(.*?)'\)/", $this->connection->joinChat(), $matches);
		$this->id = $matches[1];
		Core::log()->info = 'Successfully connected to server, reading messages from: '.$this->id;
	}
	
	public function signalHandler($signo) {
		switch ($signo) {
			case SIGTERM:
				// handle shutdown tasks
				if ($this->child !== 0) {
					posix_kill($this->child, SIGTERM); 
				}
				exit;
			case SIGCHLD:
				pcntl_waitpid(-1, $status);
				$this->child = pcntl_fork();	
				if ($this->child === -1) {
					Core::log()->error = 'Fatal: Could not fork, exiting';
					exit(1);
				}					
				else if ($this->child === 0) {
					return self::child();
				}
				Core::log()->error = 'Child died, reforking, child is: '.$this->child;
			default:
			     // handle all other signals
		}
	}
	
	public function work() {
		Core::log()->info = 'Initializing finished, forking';
		pcntl_signal(SIGTERM, array($this, 'signalHandler'));
		pcntl_signal(SIGCHLD, array($this, 'signalHandler'));
		
		$this->child = pcntl_fork();
		if ($this->child === -1) {
			Core::log()->error = 'Fatal: Could not fork, exiting';
			exit(1);
		}
		else if ($this->child === 0) {
			// child process
			return self::child();
		}
		else {
			register_shutdown_function(array('Core', 'destruct'));
			Core::log()->info = 'Child is: '.$this->child;
			// parent
			while (true) {
				sleep(1);
			}
		}
	}
	
	public function child() {
		while (true) {
			self::loadQueue();
			if (count($this->queue)) {
				self::getConnection()->postMessage(array_shift($this->queue));
			}
			usleep(500000);
		}
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
	
	public function loadQueue() {
		if (file_exists('say')) {
			$data = explode("\n", file_get_contents(DIR.'say'));
			unlink(DIR.'say');
			foreach ($data as $d) {
				if (!empty($d)) $this->queue[] = $d;
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