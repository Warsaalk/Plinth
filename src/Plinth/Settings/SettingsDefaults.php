<?php

namespace Plinth\Settings;


final class SettingsDefaults
{

	CONST FORCE_LOGIN = false;

	CONST USER_SERVICE = false;

	CONST USER_SESSION = false;

	CONST USER_CLASS = false;

	CONST USER_REHASH = false;

	CONST LOGIN_PAGE = 'page_login';

	CONST DEFAULT_LOCALE = false;

	CONST FALLBACK_LOCALE = false;

	CONST AUTO_ROUTE_LOCALE = true;

	CONST LOCALE_TYPE = 'php';

	CONST LOCALE_GET = false;

	CONST LOCALE_ACCEPT = false;

	CONST LOCALE_COOKIE = false;

	CONST TOKEN_EXPIRE = 300;

	CONST SESSION_REGENERATE = 300;

	CONST TEMPLATE_BASE = 'base';

	CONST TEMPLATE_PATH = __TEMPLATE;

	CONST ASSET_PATH = false;

	CONST ROUTE_403 = false;

	CONST ROUTE_404 = false;

	CONST ROUTE_405 = false;

	CONST CHARACTER_ENCODING = 'UTF-8';

	private function __construct() {}

}