<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace system\classes;

class BlackWord {
	/**
	 * 黑名单替换
	 *
	 * @param string $string
	 * @param string $replace
	 *
	 * @return string
	 */
	public static function replace(string $string, $replace = '***'): string {
		$string = self::santize($string);
		$keys   = self::scws($string, 500);
		if ($keys) {
			foreach ($keys as $key) {
				$string = str_replace($key, $replace, $string);
			}
		}

		return $string;
	}

	/**
	 * 返回敏感词组
	 *
	 * @param string $string
	 * @param int    $count
	 *
	 * @return array
	 */
	public static function black(string $string, int $count = 10000): array {
		return self::scws(self::santize($string), $count);
	}

	/**
	 * 是否有黑词
	 *
	 * @param string $string
	 *
	 * @return bool
	 */
	public static function has(string $string): bool {
		$keys = self::scws(self::santize($string), 1);

		return !empty($keys);
	}

	/**
	 * 得到关键词列表.
	 *
	 * @param string $string
	 * @param int    $count 分词数量
	 *
	 * @return array
	 */
	private static function scws(string $string = '', int $count = 1000): array {
		$keywords = [];
		if (extension_loaded('scws') && $string) {
			$attr = null;
			$dict = APPROOT . STORAGE_DIR . DS . 'black_words.xdb';
			if (is_file($dict)) {
				$scws1 = scws_new();
				$scws1->set_charset('utf8');
				@$scws1->set_dict($dict, SCWS_XDICT_XDB);
				$scws1->set_multi(15);
				$keywords = self::doit($scws1, $string, $count, $attr);
				$scws1->close();
			}
		}

		return $keywords;
	}

	/**
	 * 对字符进行一般消毒.
	 *
	 * @param string $string
	 *
	 * @return string 消毒后的字符串.
	 */
	public static function santize(string $string): string {
		return preg_replace('/([^a-z\d])(\s|\*|\+|\-|_|,|\\\\|\/)+([^a-z\d])/i', '\1\3', $string);
	}

	/**
	 * 分词
	 *
	 * @param resource   $scws
	 * @param string     $string
	 * @param string|int $count
	 * @param string     $attr
	 *
	 * @return array
	 */
	private static function doit($scws, $string, $count, $attr) {
		$keywords = [];
		$scws->set_duality(false);
		$scws->set_ignore(true);
		$scws->send_text($string);
		$tmp = $scws->get_tops($count, $attr);
		if ($tmp) {
			foreach ($tmp as $keyword) {
				$keywords [] = $keyword ['word'];
			}
		}

		return $keywords;
	}
}