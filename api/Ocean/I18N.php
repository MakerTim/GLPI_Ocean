<?php


namespace Ocean;


class I18N {

	public static function parseLang($lang) {
		if (!$lang) {
			$lang = FastConfig::getConfig()['language'];
		}
		$lang = strpos($lang, '_') === false ? $lang : explode('_', $lang)[0];
		if (!$lang) {
			return 'en';
		}
		return $lang;
	}

	public static function translate($text, $lang) {
		$lang = self::parseLang($lang);
		if (file_exists('../src/assets/i18n.json')) {
			$i18n = json_decode(file_get_contents('../src/assets/i18n.json'));
		} else if (file_exists('../dist/client/assets/i18n.json')) {
			$i18n = json_decode(file_get_contents('../dist/client/assets/i18n.json'));
		} else if (file_exists('../assets/i18n.json')) {
			$i18n = json_decode(file_get_contents('../dist/client/assets/i18n.json'));
		} else {
			die(file_get_contents('404.png'));
		}
		if (!key_exists($lang, $i18n)) {
			$lang = 'default';
		}
		$i18nDefault = $i18n->default;
		$i18n = $i18n->$lang;

		$exploded = explode(' ', $text);
		$key = array_shift($exploded);
		if (key_exists($key, $i18n)) {
			$text = $i18n->$key;
		} else if (key_exists($key, $i18nDefault)) {
			$text = $i18nDefault->$key;
		}
		if (count($exploded) > 0) {
			for ($i = 0; $i < count($exploded); $i++) {
				$text = preg_replace("/\{$i\}/m", $exploded[$i], $text);
			}
		}

		return $text;
	}
}
