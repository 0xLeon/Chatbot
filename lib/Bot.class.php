<?php

class Bot {
	protected $connection = null;
	protected $id = 0;
	protected $queue = array();
	protected $child = 0;
	protected $needRefork = false;
	public $data = array();
	public $message = array();
	public function __construct() {
		$this->connection = new Connection(SERVER, ID, null, null, HASH);
		$this->connection->getSecurityToken();
		preg_match("/new Chat\(([0-9]+)/", $this->connection->joinChat(), $matches);
		$this->id = $matches[1];
		Core::log()->info = 'Successfully connected to server, reading messages from: '.$this->id;
	}
	
	public function signalHandler($signo) {
		switch ($signo) {
			case SIGTERM:
				// handle shutdown tasks
				if ($this->child !== 0) {
					Core::log()->error = 'Received SIGTERM';
					posix_kill($this->child, SIGTERM); 
				}
				exit;
			case SIGCHLD:
				pcntl_waitpid(-1, $status);
				Core::log()->error = 'Child died, reforking';
				$this->needRefork = true;
				return;
			default:
			     // handle all other signals
		}
	}
	
	public function isParent() {
		return ($this->child > 0);
	}
	
	public function fork() {
		$this->child = pcntl_fork();	
		if ($this->child === -1) {
			Core::log()->error = 'Fatal: Could not fork, exiting';
			exit(1);
		}
	}
	
	public function work() {
		Core::log()->info = 'Initializing finished, forking';
		pcntl_signal(SIGTERM, array($this, 'signalHandler'));
		pcntl_signal(SIGCHLD, array($this, 'signalHandler'));
		register_shutdown_function(array('Core', 'destruct'));
		
		$this->needRefork = true;
		// parent
		while (true) {
			if ($this->needRefork) {
				$this->fork();
				$this->needRefork = false;
				
				if ($this->child === 0) {
					// child process
					return self::child();
				}
				else {
					Core::log()->info = 'Child is: '.$this->child;
				}
			}
			// read messages
			$this->data = Bot::read();
			foreach($this->data['messages'] as $this->message) {
				if (substr(Module::removeWhisper($this->message['text']), 0, 5) == '!load') {
					if (Core::isOp($this->lookUpUserID())) {
						Core::log()->info = $this->message['usernameraw'].' loaded a module';
						Core::loadModule(StringUtil::trim(substr(Module::removeWhisper($this->message['text']), 5)));
						$this->success();
					}
					else {
						Core::log()->permission = $this->message['usernameraw'].' tried to load a module';
					}
				}
				else if (substr(Module::removeWhisper($this->message['text']), 0, 7) == '!unload') {
					if (Core::isOp($this->lookUpUserID())) {
						Core::log()->info = $this->message['usernameraw'].' unloaded a module';
						Core::unloadModule(StringUtil::trim(substr(Module::removeWhisper($this->message['text']), 7)));
						$this->success();
					}
					else {
						Core::log()->permission = $this->message['usernameraw'].' tried to unload a module';
					}
				}
				else if (substr(Module::removeWhisper($this->message['text']), 0, 7) == '!reload') {
					if (Core::isOp($this->lookUpUserID())) {
						Core::log()->info = $this->message['usernameraw'].' reloaded a module';
						Core::reloadModule(StringUtil::trim(substr(Module::removeWhisper($this->message['text']), 7)));
						$this->success();
					}
					else {
						Core::log()->permission = $this->message['usernameraw'].' tried to reload a module';
					}
				}
				else if (substr(Module::removeWhisper($this->message['text']), 0, 3) == '!op') {
					$user = trim(substr(Module::removeWhisper($this->message['text']), 4));
					if (Core::isOp($this->lookUpUserID())) {
						$userID = $this->lookUpUserID($user);
						if ($userID) {
							Core::log()->info = $this->message['usernameraw'].' opped '.$user;
							Core::config()->config['op'][$userID] = $userID;
							Core::config()->write();
							$this->success();
						}
						else {
							$this->queue('/whisper "'.$this->message['usernameraw'].'" Konnte den Benutzer '.$user.' nicht finden, ist er online?');
						}
					}
					else {
						Core::log()->permission = $this->message['usernameraw'].' tried to op '.$user;
					}
				}
				else if (substr(Module::removeWhisper($this->message['text']), 0, 5) == '!deop') {
					$user = trim(substr(Module::removeWhisper($this->message['text']), 6));
					if (Core::isOp($this->lookUpUserID())) {
						$userID = $this->lookUpUserID($user);
						if ($userID) {
							Core::log()->info = $this->message['usernameraw'].' deopped '.$user;
							unset(Core::config()->config['op'][$userID]);
							Core::config()->write();
							$this->success();
						}
						else {
							$this->queue('/whisper "'.$this->message['usernameraw'].'" Konnte den Benutzer '.$user.' nicht finden, ist er online?');
						}
					}
					else {
						Core::log()->permission = $this->message['usernameraw'].' tried to deop '.$user;
					}
				}
				else {
					$modules = Core::getModules();
					foreach ($modules as $module) {
						$module->handle($this);
					}
				}
			}
			usleep(250000);
		}
	}
	
	public function lookUpUserID($username = null) {
		//  First lookup online users (faster)
		if ($username === null) $username = $this->message['usernameraw'];
		foreach ($this->data['users'] as $user) {
			if ($user['usernameraw'] == $username) return $user['userID'];
		}
		return $this->getConnection()->lookUp($username);
	}
	
	public function child() {
		while (true) {
			self::loadQueue();
			if (count($this->queue)) {
				self::getConnection()->postMessage(array_shift($this->queue));
			}
			usleep(250000);
		}
	}
	
	public function getConnection() {
		return $this->connection;
	}
	
	public function read() {
		$data = $this->getConnection()->readMessages($this->id);
		
		if (count($data['messages'])) {
			$id = end($data['messages']);
			$this->id = $id['id'];
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
	
	public function success() {
		$this->queue('/whisper "'.$this->message['usernameraw'].'" Der Befehl wurde erfolgreich ausgefuehrt');
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