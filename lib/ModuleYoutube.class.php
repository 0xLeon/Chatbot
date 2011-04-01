<?php

class ModuleYoutube extends Module {
	protected $cache = array();
	public function destruct() {

	}

	public function handle(Bot $bot) {
		if ($bot->message['id'] % 500 == 0) $this->cache = array();
		if ($bot->message['usernameraw'] == NAME) return;
		if (preg_match_all('~http://(?:.+)\.youtube\.com/watch\?(?:.*)v=([a-zA-Z0-9_-]+?)~U', $bot->message['text'], $matches))
		foreach ($matches[1] as $id) {
			$title = $this->lookUp($id);
			if ($title !== false) {
				$bot->queue('Youtube ['.$id.']: '.$title);
			}
		}
	}
	
	protected function lookUp($id) {
		if (!isset($this->cache[$id])) {
			$fp = fsockopen('gdata.youtube.com',80,$errno,$errstr,30);
			if (!$fp) {
				return false;
			}

			$request = '';
			if (!empty($requestData)) {
				foreach ($requestData AS $k => $v) {
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
			fputs($fp, "GET /feeds/api/videos/".$id."?v=2 HTTP/1.1\r\n");
			fputs($fp, "Host: gdata.youtube.com\r\n");
			fputs($fp, "User-Agent: Mozilla/5.0 (X11; U; Linux i686; de; rv:1.9.2.12) Gecko/20101027 Ubuntu/10.10 (maverick) Firefox/3.6.12\r\n");
			fputs($fp, "Content-length: ".strlen($request)."\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $request."\r\n\r\n");

			$result = '';
			while (!feof($fp)) {
				$result .= fgets($fp);
			}
			fclose($fp);
			
			if (!preg_match('~<title>(.*)</title>~U', $result, $matches)) {
				$this->cache[$id] = false;
			}
			else {
				$this->cache[$id] = $matches[1];
			}
		}
		
		return $this->cache[$id];
	}
}
?>
