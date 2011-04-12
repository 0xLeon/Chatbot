<?php

class ModuleGoogle extends Module {
	public function destruct() {

	}

	public function handle(Bot $bot) {
		if ($bot->message['id'] % 500 == 0) $this->cache = array();
		if ($bot->message['usernameraw'] == NAME) return;
		if (substr($bot->message['text'], 0, 8) == '!google ') {
			$data = $this->search(substr($bot->message['text'], 8));
			Bot::queue('Google ['.substr($bot->message['text'], 8).']: http://google.de/search?q='.rawurlencode(substr($bot->message, 8)).' Ungefähr '.number_format($data['responseData']['cursor']['estimatedResultCount'], 0, Core::language()->decimal_point, Core::language->thousand_separator).' Ergebnisse');
			Bot::queue('#1: '.strip_tags($data['responseData']['results'][0]['title']).': '.$data['responseData']['results'][0]['unescapedUrl']);
		}
	}
	
	public function search($query, $requestData = array()) {
		$fp = fsockopen('ajax.googleapis.com',80,$errno,$errstr,30);
		if (!$fp) {
			throw new Exception('Connection could not be established');
		}

		fputs($fp, "GET /ajax/services/search/web?v=1.0&q=".rawurlencode($query)." HTTP/1.1\r\n");
		fputs($fp, "Host: ajax.googleapis.com\r\n");
		fputs($fp, "User-Agent: Mozilla/5.0 (X11; U; Linux i686; de; rv:1.9.2.12) Gecko/20101027 Ubuntu/10.10 (maverick) Firefox/3.6.12\r\n");
		fputs($fp, "Connection: close\r\n\r\n");

		$result = '';
		while (!feof($fp)) {
			$result .= fgets($fp);
		}
		fclose($fp);
		$result = substr($result, stripos($result, "\r\n\r\n")+4);
		return json_decode($result, true);
	}
}
?>
