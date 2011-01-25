<?php
/**
 * WCFApi provides methods to externally access a WCF
 * 
 * @author	Tim Düsterhus
 * @copyright	2010 Tim Düsterhus
 * @licence	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class Connection {

	/**
	 * Useragent to send
	 *
	 * @var string
	 */
	const USERAGENT = 'PHP - Chatbot v2.0.0 - Jetzt noch besser :D';

	/**
	 * The URL of the WCF to access
	 *
	 * @var array<mixed>
	 */
	protected $url = array();

	/**
	 * Userdata to send for login
	 * $userID and either $password and $salt or $cookiePassword is needed
	 *
	 * @var mixed
	 */
	protected $userID = 0, $password = '', $salt = '', $cookiePassword = '';

	/**
	 * Save SessionID for Security Token
	 * 
	 * @var string
	 */
	protected $sessionID = '';

	/**
	 * WCF Security Token
	 * 
	 * @var string
	 */
	private $securityToken = '';

	/**
	 * The Cookie-Prefix defined in the WCF
	 * 
	 * @var string
	 */
	protected $cookiePrefix = 'wcf_';

	/**
	 * Used to specifie PostData to send
	 * 
	 * @var array<mixed>
	 */
	protected $request = array();

	/**
	 * Filedata to send when setUploadRequest is used
	 * 
	 * @var array<array>
	 */
	protected $files = array();

	/**
	 * Parses then URL and calculates the logindata
	 *
	 * @param	string	$url		URL of the WCF to access
	 * @param	integer	$userID		The UserID to use
	 * @param	string	$password	Used in combination with $salt to calculate $cookiePassword
	 * @param	string	$salt		Used in combination with $password to calculate $cookiePassword
	 * @param	string	$cookiePassword	Calculated Cookie-Password
	 * @param	string	$cookiePrefix	Which Prefix is defined, wcf_ is used when not given
	 * @return	void
	 */
	public function __construct($url, $userID, $password = null, $salt = null, $cookiePassword = null, $cookiePrefix = 'wcf_') {
		$this->url = parse_url($url);
		if ($this->url === false) {
			throw new Exception('Invalid URL');
		}
		
		$this->userID = $userID;
		$this->password = $password;
		$this->salt = $salt;
		$this->cookiePassword = $cookiePassword;
		if (($this->password === null || $this->salt === null) && $this->cookiePassword === null) {
			throw new Exception('Invalid Userdata');
		}
		else if ($this->password !== null && $this->salt !== null) {
			$this->cookiePassword = $this->buildCookiePassword();
		}
		
		$this->cookiePrefix = $cookiePrefix;
	}
	
	/**
	 * Builds a CookiePassword with given salt and password
	 *
	 * @return	string
	 */
	protected function buildCookiePassword() {
		return sha1($this->salt.sha1($this->password));
	}
	
	/**
	 * Does the request with given formdata / url
	 *
	 * @return	string		Request Answer
	 */
	protected function setRequest() {
		$fp = fsockopen($this->url['host'],((isset($this->url['port'])) ? $this->url['port'] : 80),$errno,$errstr,30);
		if (!$fp) {
			return;
		}

		$request = '';
		if (!empty($this->request)) {
			foreach ($this->request AS $k => $v) {
				if (is_array($v)) {
					foreach($v as $v2) {
						$request .= urlencode($k).'[]='.urlencode($v2).'&';
					}
				}
				else {
					$request .= urlencode($k).'='.urlencode($v).'&';
				}
			}
			$request = substr($request,0,-1);
		}
		fputs($fp, "POST ".$this->url['path'].((!empty($this->url['query'])) ? '?'.$this->url['query'] : '')." HTTP/1.1\r\n");
		fputs($fp, "Host: ".$this->url['host']."\r\n");
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($fp, "User-Agent: ".self::USERAGENT."\r\n");
		fputs($fp, "Content-length: ".strlen($request)."\r\n");
		fputs($fp, "Cookie: ".$this->cookiePrefix."userID=".$this->userID."; ".$this->cookiePrefix."password=".$this->cookiePassword.(($this->sessionID != '') ? "; ".$this->cookiePrefix."cookieHash=".$this->sessionID : '')."\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $request."\r\n\r\n");

		$result = '';
		while (!feof($fp)) {
			$result .= fgets($fp);
		}
		fclose($fp);

		// TODO: Add Header Validation for 404, 403, 401, 500 etc.
		return $result;
	}
	
	/**
	 * Validates an User by Username
	 *
	 * @param	string	$username	the username to validate
	 * @return	boolean			validuser
	 */
	public function validUser($username) {
		$this->url['query'] = 'page=PublicUserSuggest';
		$this->request= array(
			'query' => $username
		);
		$data = $this->setRequest();
		
		return stripos($data, '<user><![CDATA['.$username.']]></user>') !== false;
	}
	
	/**
	 * Tries to find out security token
	 *
	 * @return	string		security token
	 */
	public final function getSecurityToken() {
		if ($this->securityToken != '') return $this->securityToken;

		$data = $this->setRequest();
		preg_match('/Set-Cookie: '.$this->cookiePrefix.'cookieHash=([a-f0-9]{40}); /', $data, $matches);
		$this->sessionID = $matches[1];

		preg_match('/&amp;t=([a-f0-9]{40})/', $data, $matches);
		if (isset($matches[1])) $this->securityToken = $matches[1];

		return $this->securityToken;
	}
	
	public function joinChat() {
		$this->url['query'] = 'page=Chat';
		return $this->setRequest();
	}
	
	public function postMessage($message) {
		$this->url['query'] = 'form=Chat';
		$this->request = array(
			'text' => $message,
			'enablesmilies' => 1,
			'ajax' => 1
		);
		$this->setRequest();
	}
	
	public function readMessages($id) {
		$this->url['query'] = 'page=ChatMessage&id='.$id;
		$data = $this->setRequest();
		$data = substr($data, stripos($data, '{"users'));
		$data = substr($data, 0, strrpos($data, '}}')+2);
		$data = json_decode($data, true);
		return $data;
	}
	
	public function join($roomID) {
		$this->url['query'] = 'page=Chat&ajax=1&room='.$roomID;
		$data = $this->setRequest();
		return $data;
	}
	
	public function getRooms() {
		$this->url['query'] = 'page=ChatRefreshRoomList';
		$data = $this->setRequest();
		return $data;
	}

	public function leave() {
		$this->url['query'] = 'form=Chat&kill=1';
		$this->setRequest();
	}
}
