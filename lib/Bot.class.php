<?php
/**
 * Main Bot class, handles messages
 *
 * @author	Tim Düsterhus
 * @copyright	2010 - 2011 Tim Düsterhus
 */
class Bot {
	/**
	 * Holds the Connection to chat
	 *
	 * @var Connection
	 */
	protected $connection = null;
	
	/**
	 * The ID to read next
	 *
	 * @var integer
	 */
	protected $id = 0;
	
	/**
	 * The message queue
	 *
	 * @var array<string>
	 */
	protected $queue = array();
	
	/**
	 * The PID of the child
	 *
	 * @var integer
	 */
	protected $child = 0;
	
	/**
	 * Died the child
	 *
	 * @var boolean
	 */
	protected $needRefork = false;
	
	/**
	 * The complete JSON-Data
	 *
	 * @var array<array>
	 */
	public $data = array();
	
	/**
	 * The message currently handled
	 *
	 * @var array<mixed>
	 */
	public $message = array();
	
	/**
	 * The number of messages the bot handled
	 *
	 * @var integer
	 */
	public $messageCount = 0;
	public function __construct() {
		$this->connection = new Connection(SERVER, ID, null, null, HASH);
		$this->connection->getSecurityToken();
		preg_match("/new Chat\(([0-9]+)/", $this->connection->joinChat(), $matches);
		$this->id = $matches[1];
		Core::log()->info = 'Successfully connected to server, reading messages from: '.$this->id;
	}
	
	/**
	 * Handles Signals
	 *
	 * @param	integer	$signo	the signal-numeric
	 * @return	void
	 */
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
	
	public function shutdown() {
		posix_kill(getmypid(), SIGTERM);
	}

	/**
	 * Checks whether we are in the parent process
	 *
	 * @return boolean
	 */
	public function isParent() {
		return ($this->child > 0);
	}
	
	/**
	 * Forks the bot
	 *
	 * @return void
	 */
	public function fork() {
		$this->child = pcntl_fork();
		if ($this->child === -1) {
			// yes this is an easteregg
			Core::log()->error = 'KERNEL PANIC: Could not fork, exiting, HERP-A-DERP';
			exit(1);
		}
	}
	
	/**
	 * Does all the work
	 * 
	 * @return void
	 */
	public function work() {
		Core::log()->info = 'Initializing finished, forking';
		// register some functions
		pcntl_signal(SIGTERM, array($this, 'signalHandler'));
		pcntl_signal(SIGCHLD, array($this, 'signalHandler'));
		register_shutdown_function(array('Core', 'destruct'));
		
		$this->needRefork = true;
		
		// main loop
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
			if (!is_array($this->data['messages'])) continue;
			$this->messageCount += count($this->data['messages']);
			foreach($this->data['messages'] as $this->message) {
				// remove crap
				$this->message['text'] = html_entity_decode(
					preg_replace('~<a href="(.*)">(.*)</a>~U', "\${1}", 
						preg_replace('~<img src="(.*)" alt="(.*)" />~U', "\${2}", 
							$this->message['text']
						)
					)
				);
				
				// core commands
				if (substr(Module::removeWhisper($this->message['text']), 0, 6) == '!load ') {
					if (Core::isOp($this->lookUpUserID())) {
						Core::log()->info = $this->message['usernameraw'].' loaded a module';
						Core::loadModule(StringUtil::trim(substr(Module::removeWhisper($this->message['text']), 5)));
						$this->success();
					}
					else {
						$this->denied();
					}
				}
				else if (substr(Module::removeWhisper($this->message['text']), 0, 8) == '!unload ') {
					if (Core::isOp($this->lookUpUserID())) {
						Core::log()->info = $this->message['usernameraw'].' unloaded a module';
						Core::unloadModule(StringUtil::trim(substr(Module::removeWhisper($this->message['text']), 7)));
						$this->success();
					}
					else {
						$this->denied();
					}
				}
				else if (substr(Module::removeWhisper($this->message['text']), 0, 8) == '!reload ') {
					if (Core::isOp($this->lookUpUserID())) {
						Core::log()->info = $this->message['usernameraw'].' reloaded a module';
						Core::reloadModule(StringUtil::trim(substr(Module::removeWhisper($this->message['text']), 7)));
						$this->success();
					}
					else {
						$this->denied();
					}
				}
				else if (substr(Module::removeWhisper($this->message['text']), 0, 4) == '!op ') {
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
							$this->queue('/whisper "'.$this->message['usernameraw'].'" Konnte den Benutzer '.$user.' nicht finden');
						}
					}
					else {
						$this->denied();
					}
				}
				else if (substr(Module::removeWhisper($this->message['text']), 0, 6) == '!deop ') {
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
							$this->queue('/whisper "'.$this->message['usernameraw'].'" Konnte den Benutzer '.$user.' nicht finden');
						}
					}
					else {
						$this->denied();
					}
				}
				else {
					// handle the modules
					$modules = Core::getModules();
					foreach ($modules as $module) {
						$userID = $this->lookUpUserID($this->message['usernameraw']);
						if (($module instanceof AlwaysFire) || !isset(Core::config()->config['ignore'][$userID])) {
							$module->handle($this);
						}
					}
				}
			}
			usleep(250000);
		}
	}
	
	/**
	 * Looks the userID of the specified user up
	 * 
	 * @param	string	$username	The username to check
	 * @return	integer				The matching userID
	 */
	public function lookUpUserID($username = null) {
		//  First lookup online users (faster)
		if ($username === null) $username = $this->message['usernameraw'];
		foreach ($this->data['users'] as $user) {
			if ($user['usernameraw'] == $username) return $user['userID'];
		}
		return $this->getConnection()->lookUp($username);
	}
	
	/**
	 * Does the work for the child
	 * 
	 * @return void
	 */
	public function child() {
		while (true) {
			self::loadQueue();
			if (count($this->queue)) {
				self::getConnection()->postMessage(array_shift($this->queue));
			}
			usleep(600000);
		}
	}
	
	
	/**
	 * @see Bot::$connection
	 */
	public function getConnection() {
		return $this->connection;
	}
	
	/**
	 * Reads the messages and parses the output
	 *
	 * @return array<array>	See Bot::$data
	 */
	public function read() {
		$data = $this->getConnection()->readMessages($this->id);
		
		if (count($data['messages'])) {
			$id = end($data['messages']);
			$this->id = $id['id'];
		}
		return $data;
	}
	
	/**
	 * Loads new messages into queue
	 *
	 * @return void
	 */
	public function loadQueue() {
		if (file_exists('say')) {
			$data = explode("\n", file_get_contents(DIR.'say'));
			unlink(DIR.'say');
			foreach ($data as $d) {
				if (!empty($d)) $this->queue[] = $d;
			}
		}
	}
	
	
	/**
	 * Prints out a success message
	 *
	 * @return void
	 */
	public function success() {
		$this->queue('/whisper "'.$this->message['usernameraw'].'" Der Befehl wurde erfolgreich ausgeführt');
	}
	
	/**
	 * Prints out a permissionDenied message and logs the command
	 *
	 * @return void
	 */
	public function denied() {
		$this->queue('/whisper "'.$this->message['usernameraw'].'" Zugriff verweigert');
		Core::log()->permission = $this->message['usernameraw'].' tried to use '.$this->message['text'];
	}
	
	/**
	 * Adds a message to the queue
	 *
	 * @param	string	$message	message to add
	 * @return	void
	 */
	public function queue($message) {
		if (Core::config()->config['stfu']) return;
		
		$data = '';
		if (file_exists(DIR.'say')) {
			$data = file_get_contents(DIR.'say')."\n";
		}
		file_put_contents(DIR.'say', $data.$message);
	}
}
