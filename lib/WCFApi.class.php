<?php
if (!defined('USERAGENT')) define('USERAGENT', 'PHP/'.phpversion().' ('.php_uname('s').' '.php_uname('r').') WCFApi/1.0');
/**
 * WCFApi provides methods to externally access a WCF
 *
 * @author	Tim Düsterhus
 * @copyright	2010 Tim Düsterhus
 * @licence	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class WCFApi {
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
		$fp = fsockopen($this->url['host'], ((isset($this->url['port'])) ? $this->url['port'] : 80), $errno, $errstr, 30);
		if (!$fp) {
			throw new Exception('Connection could not be established');
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
		fputs($fp, "User-Agent: ".USERAGENT."\r\n");
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
		return $this->unchunkHTTP($result);
	}

	/**
	 * Does the request with given formdata / url and uploads given files
	 *
	 * @return	string		Request Answer
	 */
	protected function setUploadRequest() {
		$fp = fsockopen($this->url['host'], ((isset($this->url['port'])) ? $this->url['port'] : 80), $errno, $errstr, 30);
		if (!$fp) {
			throw new Exception('Connection could not be established');
		}
		
		$boundary = '';
		for ($i = 0; $i < 27; $i++) {
			$boundary .= rand(0, 9);
		}

		$request = '';
		if (!empty($this->request)) {
			foreach ($this->request AS $k => $v) {
				if (is_array($v)) {
					foreach($v as $v2) {
						$request .= "--".$boundary."\r\n";
						$request .= "Content-Disposition: form-data; name=\"".$k."[]\"\r\n\r\n";
						$request .= $v2."\r\n";
					}
				}
				else {
					$request .= "--".$boundary."\r\n";
					$request .= "Content-Disposition: form-data; name=\"".$k."\"\r\n\r\n";
					$request .= $v."\r\n";
				}
			}
		}

		if (!empty($this->files)) {
			foreach ($this->files AS $k => $v) {
				$request .= "--".$boundary."\r\n";
				$request .= "Content-Disposition: form-data; name=\"".$k."\"; filename=\"".$v['filename']."\"\r\n";
				$request .= "Content-Type: ".$v['type']."\r\n\r\n";
				$request .= $v['content']."\r\n";
			}
		}
		$request .= "--".$boundary."--";


		fputs($fp, "POST ".$this->url['path'].((!empty($this->url['query'])) ? '?'.$this->url['query'] : '')." HTTP/1.0\r\n");
		fputs($fp, "Host: ".$this->url['host']."\r\n");
		fputs($fp, "Content-Type: multipart/form-data; boundary=".$boundary."\r\n");
		fputs($fp, "User-Agent: ".self::USERAGENT."\r\n");
		fputs($fp, "Content-length: ".strlen($request)."\r\n");
		fputs($fp, "Cookie: ".$this->cookiePrefix."userID=".$this->userID."; ".$this->cookiePrefix."password=".$this->cookiePassword.(($this->sessionID != '') ? "; ".$this->cookiePrefix."cookieHash=".$this->sessionID : '')."\r\n\r\n");
		fputs($fp, $request."\r\n\r\n");

		$result = '';
		while (!feof($fp)) {
			$result .= fgets($fp);
		}
		fclose($fp);

		// TODO: Add Header Validation for 404, 403, 401, 500 etc.
		return $this->unchunkHTTP($result);
	}

	/**
	 * unchunk the data for http1.1 compatibility
	 *
	 * @param	 string	$data	answer
	 * @return	string			unchunked answer
	 */
	public function unchunkHTTP($data) {
		$header =  substr($data, 0, (strpos($data, "\r\n\r\n")+4));
		$data = substr($data, (strpos($data, "\r\n\r\n")+4));
		if (strpos(strtolower($header), "transfer-encoding: chunked") === false) return $header.$data;

		$fp = 0;
		$outData = "";
		while ($fp < strlen($data)) {
			$rawnum = substr($data, $fp, strpos(substr($data, $fp), "\r\n") + 2);
			$num = hexdec(trim($rawnum));
			$fp += strlen($rawnum);
			$chunk = substr($data, $fp, $num);
			$outData .= $chunk;
			$fp += strlen($chunk);
		}
		return $header.$outData;
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
	 * Looks up the userID
	 *
	 * @param	string	$username	The username to check
	 * @return	integer				The matching userID
	 */
	public function lookUpUserID($username) {
		$old = $this->url;
		$this->url['query'] = 'page=User&username='.rawurlencode($username);
		$data = $this->setRequest();
		
		// seo-plugin
		if (preg_match('~Location: (.*)~', $data, $matches)) {
			$this->url = parse_url('http://'.$this->url['host'].substr($matches[1],0,-1));
			$data = $this->setRequest();
		}
		$this->url = $old;
		if (!preg_match('~<input type="hidden" name="userID" value="([0-9]+)" />~', $data, $matches)) {
			return 0;	
		}
		return $matches[1];
	}

	/**
	 * Sends a PM
	 *
	 * @param	array<string> 	$recipients	the recipients form field
	 * @param	string		$subject	the subject of the pm
	 * @param	string		$text		the text of the pm
	 * @param	boolean		$draft		should the pm be saved in draft
	 * @param	array<string>	$blindCopies	like the recipients, but for BCC
	 * @param	integer		$parseURL	should URLs be automatically parsed
	 * @param	integer		$enableSmilies	are smileys active for this PM
	 * @param	integer		$enableBBCodes	are BBCodes active for this PM
	 * @param	integer		$enableHtml	is HTML active for this PM
	 * @param	integer		$showSignature	should the signature be shown
	 * @param	array<mixed>	$files		the files to attach
	 * @return	boolean				success
	 */
	public function sendPM(Array $recipients, $subject, $text, $draft = false, Array $blindCopies = array(), $parseURL = 1, $enableSmilies = 1, $enableBBCodes = 1, $enableHtml = 0, $showSignature = 1, Array $files = array()) {
		$this->url['query'] = 'form=PMNew';

		$this->request = array(
			'recipients' => implode(',', $recipients),
			'blindCopies' => implode(',', $blindCopies),
			'subject' => $subject,
			'text' => $text,
			'parseURL' => $parseURL,
			'enableSmilies' => $enableSmilies,
			'enableBBCodes' => $enableBBCodes,
			'enableHtml' => $enableHtml,
			'showSignature' => $showSignature,
			'pmID' => '0',
			'forwarding' => '0',
			'reply' => '0',
			'replyToAll' => '0',
			'idHash' => self::getRandomID(),
			($draft ? 'draft' : 'send') => 'true'
		);

		$this->files = $files;
		$data = $this->setUploadRequest();

		// no way to get the pmID
		return stripos($data, 'Location: ') !== false;
	}

	/**
	 * Edits a PM
	 * @see	WCFApi::sendPM()
	 */
	public function editPM($isInDraft, $pmID, Array $recipients, $subject, $text, $draft = false, Array $blindCopies = array(), $parseURL = 1, $enableSmilies = 1, $enableBBCodes = 1, $enableHtml = 0, $showSignature = 1, Array $files = array()) {
		if (!$isInDraft) {
			$this->url['query'] = 'page=PM&action=edit&pmID='.$pmID.'&t='.$this->getSecurityToken();
			$this->setRequest();
		}
		$this->url['query'] = 'form=PMNew';

		$this->request = array(
			'recipients' => implode(',', $recipients),
			'blindCopies' => implode(',', $blindCopies),
			'subject' => $subject,
			'text' => $text,
			'parseURL' => $parseURL,
			'enableSmilies' => $enableSmilies,
			'enableBBCodes' => $enableBBCodes,
			'enableHtml' => $enableHtml,
			'showSignature' => $showSignature,
			'pmID' => '0',
			'forwarding' => '0',
			'reply' => '0',
			'replyToAll' => '0',
			'idHash' => self::getRandomID(),
			'pmID' => $pmID,
			($draft ? 'draft' : 'send') => 'true'
		);

		$this->files = $files;
		$data = $this->setUploadRequest();

		return stripos($data, 'Location: ') !== false;
	}

	/**
	 * Deletes a PM
	 *
	 * @param	integer	$pmID	PM to delete
	 * @return	void
	 */
	public function deletePM($pmID) {
		$this->url['query'] = 'page=PM&action=delete&pmID='.$pmID.'&t='.$this->getSecurityToken();
		$this->setRequest();
	}

	/**
	 * Recovers a PM
	 *
	 * @param	integer	$pmID	PM to recover
	 * @return	void
	 */
	public function recoverPM($pmID) {
		$this->url['query'] = 'page=PM&action=recover&pmID='.$pmID.'&t='.$this->getSecurityToken();
		$this->setRequest();
	}

	/**
	 * Cancels a PM
	 *
	 * @param	integer	$pmID	PM to cancel
	 * @return	void
	 */
	public function cancelPM($pmID) {
		$this->url['query'] = 'page=PM&action=cancel&pmID='.$pmID.'&t='.$this->getSecurityToken();
		$this->setRequest();
	}

	/**
	 * Empties the PM recycle bin
	 *
	 * @return	void
	 */
	public function emptyPMRecycleBin() {
		$this->url['query'] = 'page=PM&action=emptyRecycleBin&t='.$this->getSecurityToken();
		$this->setRequest();
	}

	/**
	 * Does a search
	 *
	 * @param	string		$q		Search Query
	 * @param	array<string>	$types		MessageTypes to search
	 * @param	string		$author		Get Messages by this author
	 * @param	integer		$nameExactly	Has the Name to match exactly
	 * @return	string				Request Answer without Header
	 */
	public function search($q, Array $types, $author = '', $nameExactly = 0) {
		$this->url['query'] = 'form=Search';

		$this->request = array(
			'q' => $q,
			'username' => $author,
			'nameExactly' => $nameExactly,
			'types' => array()
		);

		foreach($types as $type) {
			$this->request['types'][] = $type;
		}
		$data = $this->setRequest();

		if (stripos($data, 'Location: ') !== false) {
			preg_match('/&searchID=([0-9]*?)&/', $data, $matches);
			$searchID = $matches[1];
		}
		else {
			return false;
		}
		$this->request = array();
		$this->url['query'] .= '&searchID='.$searchID;

		$data = $this->setRequest();
		$data = substr($data, stripos($data, '<?xml'));

		// TODO: get better data;
		return $data;
	}

	/**
	 * Creates an unique ID
	 * Same as StringUtil::getRandomID() from WCF
	 *
	 * @return	string
	 */
	public static function getRandomID() {
		return sha1(microtime() . uniqid(mt_rand(), true));
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
}
?>