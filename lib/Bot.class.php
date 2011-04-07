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
	
	/**
	 * The number of messages the bot sent
	 *
	 * @var integer
	 */
	public $sendCount = 0;
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
			case SIGUSR1:
				// handle shutdown tasks
				if ($this->child !== 0) {
					Core::log()->error = 'Received SIGTERM / SIGUSR1';
					posix_kill($this->child, SIGTERM); 
				}
				if ($signo === SIGTERM) exit;
				else exit(2);
			case SIGCHLD:
				pcntl_waitpid(-1, $status);
				Core::log()->error = 'Child died, reforking';
				$this->needRefork = true;
				return;
			default:
			     // handle all other signals
		}
	}
	
	public function shutdown($signal = SIGTERM) {
		posix_kill(getmypid(), $signal);
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
		if (VERBOSE > 0) Core::log()->info = 'Initializing finished, forking';
		// register some functions
		pcntl_signal(SIGTERM, array($this, 'signalHandler'));
		pcntl_signal(SIGCHLD, array($this, 'signalHandler'));
		pcntl_signal(SIGUSR1, array($this, 'signalHandler'));
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
					if (Core::compareLevel($this->lookUpUserID(), 'op.load')) {
						Core::log()->info = $this->message['usernameraw'].' loaded a module';
						$result = Core::loadModule(StringUtil::trim(substr(Module::removeWhisper($this->message['text']), 5)));
						if (!is_int($result)) {
							$this->success();
						}
						else {
							$name = 'module_error_'.$result;
							$bot->queue('/whisper "'.$this->message['usernameraw'].'" '.Core::language()->$name);
						}
					}
					else {
						$this->denied();
					}
				}
				else if (substr(Module::removeWhisper($this->message['text']), 0, 8) == '!unload ') {
					if (Core::compareLevel($this->lookUpUserID(), 'op.load')) {
						Core::log()->info = $this->message['usernameraw'].' unloaded a module';
						Core::unloadModule(StringUtil::trim(substr(Module::removeWhisper($this->message['text']), 7)));
						if (!is_int($result)) {
							$this->success();
						}
						else {
							$name = 'module_error_'.$result;
							$bot->queue('/whisper "'.$this->message['usernameraw'].'" '.Core::language()->$name);
						}
					}
					else {
						$this->denied();
					}
				}
				else if (substr(Module::removeWhisper($this->message['text']), 0, 8) == '!reload ') {
					if (Core::compareLevel($this->lookUpUserID(), 'op.load')) {
						Core::log()->info = $this->message['usernameraw'].' reloaded a module';
						$result = Core::reloadModule(StringUtil::trim(substr(Module::removeWhisper($this->message['text']), 7)));
						if (!is_int($result)) {
							$this->success();
						}
						else {
							$name = 'module_error_'.$result;
							$bot->queue('/whisper "'.$this->message['usernameraw'].'" '.Core::language()->$name);
						}
					}
					else {
						$this->denied();
					}
				}
				// fallback
				else if (substr(Module::removeWhisper($this->message['text']), 0, 4) == '!op ') {
					$user = trim(substr(Module::removeWhisper($this->message['text']), 4));
					if (Core::compareLevel($this->lookUpUserID(), 500)) {
						$userID = $this->lookUpUserID($user);
						if ($userID) {
							Core::log()->info = $this->message['usernameraw'].' opped '.$user;
							Core::config()->config['levels'][$userID] = 1;
							Core::config()->write();
							$this->success();
						}
						else {
							$this->queue('/whisper "'.$this->message['usernameraw'].'" '.Core::language()->get('user_not_found', array('{user}' => $user)));
						}
					}
					else {
						$this->denied();
					}
				}
				else if (substr(Module::removeWhisper($this->message['text']), 0, 6) == '!deop ') {
					$user = trim(substr(Module::removeWhisper($this->message['text']), 6));
					if (Core::compareLevel($this->lookUpUserID(), 500)) {
						$userID = $this->lookUpUserID($user);
						if ($userID) {
							Core::log()->info = $this->message['usernameraw'].' deopped '.$user;
							unset(Core::config()->config['levels'][$userID]);
							Core::config()->write();
							$this->success();
						}
						else {
							$this->queue('/whisper "'.$this->message['usernameraw'].'" '.Core::language()->get('user_not_found', array('{user}' => $user)));
						}
					}
					else {
						$this->denied();
					}
				}
				else if (substr(Module::removeWhisper($this->message['text']), 0, 7) == '!level ') {
					$data = trim(substr(Module::removeWhisper($this->message['text']), 7));
					$split = strrpos($data, ' ');
					$user = substr($data, 0, $split);
					$level = substr($data, $split+1);
					if (Core::compareLevel($this->lookUpUserID(), 500)) {
						$userID = $this->lookUpUserID($user);
						if ($userID) {
							Core::log()->info = $this->message['usernameraw'].' opped '.$user;
							Core::config()->config['levels'][$userID] = $level;
							Core::config()->write();
							$this->success();
						}
						else {
							$this->queue('/whisper "'.$this->message['usernameraw'].'" '.Core::language()->get('user_not_found', array('{user}' => $user)));
						}
					}
					else {
						$this->denied();
					}
				}
				else if (substr(Module::removeWhisper($this->message['text']), 0, 6) == '!perm ') {
					$data = trim(substr(Module::removeWhisper($this->message['text']), 6));
					$split = strrpos($data, ' ');
					$node = substr($data, 0, $split);
					$level = substr($data, $split+1);
					if (Core::compareLevel($this->lookUpUserID(), 500)) {
						Core::permission()->$node = $level;
						$this->success();
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
		$this->queue('/whisper "'.$this->message['usernameraw'].'" '.Core::language->success);
	}
	
	/**
	 * Prints out a permissionDenied message and logs the command
	 *
	 * @return void
	 */
	public function denied() {
		$this->queue('/whisper "'.$this->message['usernameraw'].'" '.Core::language->access_denied);
		Core::log()->permission = $this->message['usernameraw'].' tried to use '.$this->message['text'];
	}
	
	/**
	 * Adds a message to the queue
	 *
	 * @param	string	$message	message to add
	 * @return	void
	 */
	public function queue($message, $roomID = null) {
		if (Core::config()->config['stfu']) return;
		$this->sendCount++;
		if ($roomID === null) {
			$roomID = $this->message['roomID'];
		}
		
		$data = '';
		if (file_exists(DIR.'say')) {
			$data = file_get_contents(DIR.'say')."\n";
		}
		file_put_contents(DIR.'say', $data.$roomID.' '.$message);
	}
}
