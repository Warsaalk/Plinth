<?php

namespace Plinth\Settings;

class Settings
{
	/**
	 * @var array
	 */
	private $settings;

	public function __construct()
	{
		$this->settings = [
			'forcelogin' 			=> SettingsDefaults::FORCE_LOGIN,
			'forcesession' 			=> SettingsDefaults::FORCE_SESSION,
			'userservice' 			=> SettingsDefaults::USER_SERVICE,
			'usersession' 			=> SettingsDefaults::USER_SESSION,
			'userclass' 			=> SettingsDefaults::USER_CLASS,
			'userrehash' 			=> SettingsDefaults::USER_REHASH,
			'loginpage' 			=> SettingsDefaults::LOGIN_PAGE,
			'defaultlocale' 		=> SettingsDefaults::DEFAULT_LOCALE,
			'fallbacklocale'		=> SettingsDefaults::FALLBACK_LOCALE,
			'autoroutelocale' 		=> SettingsDefaults::AUTO_ROUTE_LOCALE,
			'localetype' 			=> SettingsDefaults::LOCALE_TYPE,
			'localeget' 			=> SettingsDefaults::LOCALE_GET,
			'localeaccept' 			=> SettingsDefaults::LOCALE_ACCEPT,
			'localecookie'			=> SettingsDefaults::LOCALE_COOKIE,
			'localesubdomain'		=> SettingsDefaults::LOCALE_SUBDOMAIN,
			'localedomain'			=> SettingsDefaults::LOCALE_DOMAIN,
			'dictionaryservice'		=> SettingsDefaults::DICTIONARY_SERVICE,
			'dictionarymerge'		=> SettingsDefaults::DICTIONARY_MERGE,
			'tokenexpire' 			=> SettingsDefaults::TOKEN_EXPIRE,
			'sessionregenerate' 	=> SettingsDefaults::SESSION_REGENERATE,
			'sessionsavepath'		=> SettingsDefaults::SESSION_SAVE_PATH,
			'templatebase' 			=> SettingsDefaults::TEMPLATE_BASE,
			'templatepath' 			=> SettingsDefaults::TEMPLATE_PATH,
			'assetpath' 			=> SettingsDefaults::ASSET_PATH,
			'route403' 				=> SettingsDefaults::ROUTE_403,
			'route404' 				=> SettingsDefaults::ROUTE_404,
			'route405' 				=> SettingsDefaults::ROUTE_405,
			'characterencoding' 	=> SettingsDefaults::CHARACTER_ENCODING,
			'requesterrorstomain'	=> SettingsDefaults::REQUEST_ERRORS_TO_MAIN,
			'executabledirectory'	=> SettingsDefaults::EXECUTABLE_DIRECTORY,
			'executablephp'			=> SettingsDefaults::EXECUTABLE_PHP
		];
	}

	/**
	 * @param array $settings
	 * @return $this
	 */
	public function loadSettings($settings = [])
	{
		$this->settings = array_merge($this->settings, $settings);

		return $this;
	}

	/**
	 * @param $label
	 * @return bool|mixed
	 */
	public function getSetting($label)
	{
		return isset($this->settings[$label]) ? $this->settings[$label] : false;
	}
}