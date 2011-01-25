<?php
/**
 * Contains string-related functions.
 * 
 * @author 	Marcel Werk
 * @copyright	2001-2009 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	util
 * @category 	Community Framework
 */
class StringUtil {
	const HTML_PATTERN = '~</?[a-z]+[1-6]?
			(?:\s*[a-z]+\s*=\s*(?:
			"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'|[^\s>]
			))*\s*/?>~ix';
	
	/**
	 * alias to php sha1() function.
	 *
	 * @param 	string 		$value
	 * @return 	string 		$hash
	 */
	public static function getHash($value) {
		return sha1($value);
	}

	/**
	 * Creates a random hash.
	 * 
	 * @return	string		a random hash
	 */
	public static function getRandomID() {
		return self::getHash(microtime() . uniqid(mt_rand(), true));
	}

	/**
	 * Converts dos to unix newlines.
	 *
	 * @param 	string 		$string
	 * @return 	string 		$string
	 */
	public static function unifyNewlines($string) {
		return preg_replace("%(\r\n)|(\r)%", "\n", $string);
	}

	/**
	 * alias to php trim() function
	 * 
	 * @param 	string 		$string
	 * @return 	string 		$string
	 */
	public static function trim($text) {
		return trim($text);
	}

	/**
	 * Converts html special characters.
	 *
	 * @param 	string 		$string
	 * @return 	string 		$string
	 */
	public static function encodeHTML($string) {
		if (is_object($string)) 
			$string = $string->__toString();
		
		return @htmlspecialchars($string, ENT_COMPAT, defined('CHARSET') ? CHARSET : 'UTF-8');
	}
	
	/**
	 * Decodes html entities.
	 *
	 * @param 	string 		$string
	 * @return 	string 		$string
	 */
	public static function decodeHTML($string) {
		if (is_object($string)) 
			$string = $string->__toString();
		
		$string = str_ireplace('&nbsp;', ' ', $string); // convert non-breaking spaces to ascii 32; not ascii 160
		return @html_entity_decode($string, ENT_COMPAT, defined('CHARSET') ? CHARSET : 'UTF-8');
	}
	
	/**
	 * Sorts an array of strings and maintain index association.
	 * 
	 * @param 	array		$strings 
	 * @return 	boolean
	 */
	public static function sort(&$strings) {
		return asort($strings, SORT_LOCALE_STRING);
	}
	
	/**
	 * alias to php str_replace() function.
	 */
	public static function replace($search, $replace, $subject, &$count = null) {
		return str_replace($search, $replace, $subject, $count);
	}
	
	/**
	 * Unescapes escaped characters in a string.
	 * 
	 * @param	string		$string
	 * @param	string		$chars
	 * @return 	string
	 */
	public static function unescape($string, $chars = '"') {
		for ($i = 0, $j = strlen($chars); $i < $j; $i++) {
			$string = self::replace('\\'.$chars[$i], $chars[$i], $string);
		}
		
		return $string;
	}
	
	/**
	 * Takes a numeric HTML entity value and returns the appropriate UTF-8 bytes.
	 * 
	 * @param	integer		$dec		html entity value
	 * @return	string				utf-8 bytes
	 */
	public static function getCharacter($dec) {
		if ($dec < 128) {
			$utf = chr($dec);
		}
		else if ($dec < 2048) {
			$utf = chr(192 + (($dec - ($dec % 64)) / 64));
			$utf .= chr(128 + ($dec % 64));
		}
		else {
			$utf = chr(224 + (($dec - ($dec % 4096)) / 4096));
			$utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64));
			$utf .= chr(128 + ($dec % 64));
		}
		return $utf;
	}
	
	/**
	 * Converts UTF-8 to Unicode
	 * @see		http://www1.tip.nl/~t876506/utf8tbl.html
	 *
	 * @param	string		$c
	 * @return	integer		unicode value of $c
	 */
	public static function getCharValue($c) {
		$ud = 0;
		if (ord($c{0}) >= 0 && ord($c{0}) <= 127) 
			$ud = ord($c{0});
		if (ord($c{0}) >= 192 && ord($c{0}) <= 223) 
			$ud = (ord($c{0}) - 192) * 64 + (ord($c{1}) - 128);
		if (ord($c{0}) >= 224 && ord($c{0}) <= 239) 
			$ud = (ord($c{0}) - 224) * 4096 + (ord($c{1}) - 128) * 64 + (ord($c{2}) - 128);
		if (ord($c{0}) >= 240 && ord($c{0}) <= 247) 
			$ud = (ord($c{0}) - 240) * 262144 + (ord($c{1}) - 128) * 4096 + (ord($c{2}) - 128) * 64 + (ord($c{3}) - 128);
		if (ord($c{0}) >= 248 && ord($c{0}) <= 251) 
			$ud = (ord($c{0}) - 248) * 16777216 + (ord($c{1}) - 128) * 262144 + (ord($c{2}) - 128) * 4096 + (ord($c{3}) - 128) * 64 + (ord($c{4}) - 128);
		if (ord($c{0}) >= 252 && ord($c{0}) <= 253) 
			$ud = (ord($c{0}) - 252) * 1073741824 + (ord($c{1}) - 128) * 16777216 + (ord($c{2}) - 128) * 262144 + (ord($c{3}) - 128) * 4096 + (ord($c{4}) - 128) * 64 + (ord($c{5}) - 128);
		if (ord($c{0}) >= 254 && ord($c{0}) <= 255) 
			$ud = false; // error
		return $ud;
	}
	
	/**
	 * Returns true, if the given string contains only ASCII characters.
	 * 
	 * @param	string		$string
	 * @return	boolean
	 */
	public static function isASCII($string) {
		return preg_match('/^[\x00-\x7F]*$/', $string);
	}
	
	/**
	 * Returns true, if the given string is utf-8 encoded.
	 * @see		http://www.w3.org/International/questions/qa-forms-utf-8
	 * 
	 * @param	string		$string
	 * @return	boolean
	 */
	public static function isUTF8($string) {
		/*return preg_match('/^(
				[\x09\x0A\x0D\x20-\x7E]*		# ASCII
			|	[\xC2-\xDF][\x80-\xBF]			# non-overlong 2-byte
			|	\xE0[\xA0-\xBF][\x80-\xBF]		# excluding overlongs
			|	[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}	# straight 3-byte
			|	\xED[\x80-\x9F][\x80-\xBF]		# excluding surrogates
			|	\xF0[\x90-\xBF][\x80-\xBF]{2}		# planes 1-3
			|	[\xF1-\xF3][\x80-\xBF]{3}		# planes 4-15
			|	\xF4[\x80-\x8F][\x80-\xBF]{2}		# plane 16
			)*$/x', $string);
		*/	
		return preg_match('/(
				[\xC2-\xDF][\x80-\xBF]			# non-overlong 2-byte
			|	\xE0[\xA0-\xBF][\x80-\xBF]		# excluding overlongs
			|	[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}	# straight 3-byte
			|	\xED[\x80-\x9F][\x80-\xBF]		# excluding surrogates
			|	\xF0[\x90-\xBF][\x80-\xBF]{2}		# planes 1-3
			|	[\xF1-\xF3][\x80-\xBF]{3}		# planes 4-15
			|	\xF4[\x80-\x8F][\x80-\xBF]{2}		# plane 16
			)/x', $string);
	}
	
	/**
	 * Extracts the class name from a standardised class path.
	 * 
	 * @param	string		$classPath
	 * @return	string		class name
	 */
	public static function getClassName($classPath) {
		return preg_replace('~(?:.*/)?([^/]+).class.php~i', '\\1', $classPath);
	}
	
	/**
	 * Escapes the closing cdata tag.
	 * 
	 * @param	string		$string
	 * @return	string
	 */
	public static function escapeCDATA($string) {
		return str_replace(']]>', ']]]]><![CDATA[>', $string);
	}
	
	/**
	 * Converts a string to requested character encoding.
	 * @see		mb_convert_encoding()
	 * 
	 * @param 	string		$inCharset
	 * @param 	string		$outCharset
	 * @param 	string		$string
	 * @return 	string		converted string
	 */
	public static function convertEncoding($inCharset, $outCharset, $string) {
		if ($inCharset == 'ISO-8859-1' && $outCharset == 'UTF-8') return utf8_encode($string);
		if ($inCharset == 'UTF-8' && $outCharset == 'ISO-8859-1') return utf8_decode($string);
		
		//return iconv($inCharset, $outCharset, $string);
		return mb_convert_encoding($string, $outCharset, $inCharset);
	}
	
	/**
	 * Strips HTML tags from a string.
	 * 
	 * @param	string		$string
	 * @return	string
	 */
	public static function stripHTML($string) {
		return preg_replace(self::HTML_PATTERN, '', $string);
	}
}
?>