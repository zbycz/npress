<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.info/
 * @package    nPress
 */


/** Service maintaining all language specific features
 *
 * @TODO currently not in use
 */
class I18n extends Object {

	private $opts;
	private $currentLang;
	function __construct($langs, $default, $langDomains){
		$this->opts = array(
					'langs' => $langs,
					'default' => $default,
					'langDomains' => $langDomains
				);
	}

	function setLang($lang){
		$this->currentLang = $lang;
	}

	function getLang(){
		return $this->currentLang;// ? $this->currentLang : $opts['default'];
	}

	function getDefaultLang(){
		return $this->opts['default'];
	}

	function getLangs(){
		return $this->opts['langs'];
	}

	function getLangDomains(){
		return $this->opts['langDomains'];

	}

}