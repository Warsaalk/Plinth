<?php

namespace Plinth\Request;

use Plinth\Connector;
use Plinth\Request\ActionType;
use Plinth\Routing\Route;
use App\Action;
use Plinth\Validation\Validator;
use Plinth\Common\Info;
use Plinth\Main;
use Plinth\Common\Debug;
use Plinth\Response\Response;
use Plinth\Exception\PlinthException;

class Request extends Connector
{

	/**
	 * HTTP request methods
	 */
	const	HTTP_GET 	= "GET",
			HTTP_PUT 	= "PUT",
			HTTP_POST 	= "POST",
			HTTP_DELETE = "DELETE",
			HTTP_NOTSET	= NULL;

	const	ACTION_LOGIN = "login";

	/**
	 * Contains form data
	 *
	 * @var array
	 */
	private $_data;

	/**
	 * Contains file info
	 *
	 * @var File[]
	 */
	private $_files;

	/**
	 * @var string
	 */
	private $_method;

	/**
	 * @var string|boolean
	 */
	private $_action = false;

	/**
	 * @var Info[]
	 */
	private $_errors = array();

	/**
	 * @var array
	 */
	private $_getData = array();

	private static $defaultVariableSettings = array(
		'rules' 	=> array(),
		'multiple'  => array(),
		'type' 		=> Validator::PARAM_STRING,
		'required' 	=> true,
		'default' 	=> '',
		'message'   => null,
		'preCallback'	=> false,
		'postCallback'	=> false
	);

	private static $defaultFileSettings = array(
		'rules' 	=> array(),
		'multiple'  => array(),
		'type' 		=> Validator::PARAM_FILE,
		'required' 	=> true,
		'message'   => null
	);

	private static $defaultTokenSettings = array(
		'required'	=> false,
		'message'	=> null
	);

	private static $defaultUserSettings = array(
		'required'	=> false,
		'callback'	=> false,
		'message'	=> null
	);

	/**
	 * @param Main $main
	 */
	public function __construct(Main $main)
	{
		parent::__construct($main);

		$this->_data = array();
		$this->_files = array();
		$this->_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : self::HTTP_NOTSET;
	}

	/**
	 * @param array $get
	 */
	public function initRequest($get)
	{
		foreach ($get as $id => $val) {
			$this->_getData[$id] = Validator::cleanInput($val);
		}
	}

	private function loadFiles()
	{
		$files = array();

		foreach ($_FILES as $label => $info) {
			if (is_array($info['name'])) {
				foreach ($info['name'] as $i => $name) {
					$files[$label][] = new UploadedFile($info['name'][$i], $info['tmp_name'][$i], $info['type'][$i], $info['error'][$i], $info['size'][$i]);
				}
			} else {
				$files[$label][] = new UploadedFile($info['name'], $info['tmp_name'], $info['type'], $info['error'], $info['size']);
			}
		}

		return $files;
	}

	/**
	 * @param string $method
	 * @return array|string
	 */
	private function loadData($method)
	{
		$data	= array();

		switch ($method) {
			case self::HTTP_GET:	$data = $_GET; break;
			case self::HTTP_POST:	$data = $_POST; break;
			case self::HTTP_PUT:
			case self::HTTP_DELETE:	parse_str(file_get_contents('php://input'), $data); break;
			default;
		}

		return $data;
	}

	/**
	 * @param Route $route
	 * @param boolean $redirected (optional)
	 */
	public function loadRequest(Route $route, $redirected = false)
	{
		if ($redirected === true) {
			//Reset action when redirecting
			$this->_action = false;
		} else {
			$method	= $this->getRequestMethod();

			$this->_data = $this->loadData($method);
			$this->_files = $this->loadFiles();

			if ($route->hasActions()) {
				$actions = $route->getActions();

				if (array_key_exists($method, $actions)) {
					$this->_action = $actions[$method];
				}
			}
		}
	}

	/**
	 * @param string $actionClassName
	 * @param string $method
	 * @return ActionType
	 * @throws PlinthException
	 */
	private function getActionClass($actionClassName, $method)
	{
		$legacyActionClassName = ucfirst($actionClassName) . ucfirst(strtolower($method));
		$legacyActionClassPath = __APP_ACTION . $legacyActionClassName . __EXTENSION_PHP;
		if (file_exists($legacyActionClassPath)) {
			$actionClassName = $legacyActionClassName;

			require $legacyActionClassPath;
		}

		$self = $this;

		if (class_exists($actionClassName, $legacyActionClassName !== $actionClassName)) {
			return new $actionClassName($this->main, function (Info $error) use ($self) {
				$self->addError($error);
			});
		} else {
			throw new PlinthException("Your action class, $actionClassName, cannot be found.");
		}
	}

	/**
	 * @param Route $route
	 * @param ActionType $action
	 */
	private function validateAction(Route $route, ActionType $action)
	{
		$actionTemplateData = false;
		$actionSettings = $action->getSettings();

		//if (!isset($actionSettings['variables'])) throw new PlinthException("Please defined your action variables");

		$validator 	= $this->main->getValidator();
		$userservice= $this->main->getUserService();
		$variables 	= isset($actionSettings['variables']) ? $actionSettings['variables'] : false;
		$uploadfiles= isset($actionSettings['files']) ? $actionSettings['files'] : false;
		$token		= isset($actionSettings['token']) ? array_merge(self::$defaultTokenSettings, $actionSettings['token']) : false;
		$user		= isset($actionSettings['user']) ? array_merge(self::$defaultUserSettings, $actionSettings['user']) : false;
		$invalid	= false;

		if ($variables !== false) {
			foreach ($variables as $name => $settings) {
				$settings = array_merge(self::$defaultVariableSettings, $settings);
				$validator->addValidation($name, $settings['rules'], $settings['type'], $settings['required'], $settings['default'], $settings['multiple'], $settings['message'], $settings['preCallback'], $settings['postCallback']);
			}
		}

		if ($uploadfiles !== false) {
			foreach ($uploadfiles as $name => $settings) {
				$settings = array_merge(self::$defaultFileSettings, $settings);
				$validator->addValidation($name, $settings['rules'], $settings['type'], $settings['required'], false, $settings['multiple'], $settings['message'], false, false);
			}
		}

		if ($token !== false) {
			$tokensettings = array_merge(self::$defaultVariableSettings, $token);
			$validator->addValidation('token', $tokensettings['rules'], Validator::PARAM_STRING, $tokensettings['required'], false, array(), $tokensettings['message'], $tokensettings['preCallback'], $tokensettings['postCallback']);
		}

		$validator->validate($this->_data, $this->_files);

		if ($validator->isValid()) {
			if ($token !== false && $token['required'] === true && !$this->main->validateToken($validator->getVariable('token'))) {
				if ($token['message']) $this->addError($token['message']);
				$invalid = true;
			}

			if (!$this->isLoginRequest() && $user !== false && $user['required'] === true) {
				$callback = $user['callback'];
				if (!$userservice->isSessionValid() || ($callback !== false && !$callback($userservice->getUser()))) {
					if ($user['message']) $this->addError($user['message']);
					$invalid = true;
					header(Response::CODE_401);
				}
			}

			if (!$this->hasErrors() && !$invalid) {
				$actionTemplateData = $action->onFinish($validator->getVariables(), $validator->getFiles());
			}
		} else {
			foreach ($validator->getErrors() as $error) {
				$this->addError($error);
			}
			$invalid = true;
		}

		if ($this->hasErrors() || $invalid) {
			$actionTemplateData = $action->onError();

			if ($this->main->getSetting('requesterrorstomain')) {
				foreach ($this->_errors as $i => $error) {
					if ($error !== null) {
						$this->main->addInfo($error);
					}
				}
			}
		}

		if (is_array($actionTemplateData)) {
			$route->setTemplateData($actionTemplateData);
		}
	}

	/**
	 * @param Info $error
	 */
	private function addError(Info $error)
	{
		$this->_errors[] = $error;
	}

	/**
	 * @return boolean
	 */
	public function hasErrors()
	{
		return !empty($this->_errors);
	}

	/**
	 * @return Info[]
	 */
	public function getErrors()
	{
		return $this->_errors;
	}

	/**
	 * @param Route $route
	 * @throws PlinthException
	 */
	public function isRouteAuthorized(Route $route)
	{
		$loginpage = $this->main->getSetting('loginpage');

		if (!$route->isPublic()) {
			if (!$this->main->getUserService()->isSessionValid()) {
				if ($route->getName() === $loginpage) throw new PlinthException('Please set your login page to public');

				$this->disableAction();
				$this->main->getRouter()->redirect($loginpage);
				$this->main->handleRequest(true);
			} else {
				if ($route->hasRoles()) {
					$roles = $this->main->getUserService()->getUser()->getRouteRoles();

					if (!is_array($roles)) throw new PlinthException('The route roles for a user needs to return a array of scalar values.');
					if (!$this->main->getRouter()->isUserRoleAllowed($roles)) {
						$this->main->getResponse()->hardExit(Response::CODE_403);
					}
				}
			}
		}
	}

	private function disableAction()
	{
		$this->_action = false;
	}

	/**
	 * @param Route $route
	 * @throws PlinthException
	 */
	public function handleRequest(Route $route)
	{
		if ($this->_action !== false) {
			$this->validateAction($route, $this->getActionClass($this->_action, $this->getRequestMethod()));
		}
	}

	/**
	 * @return boolean
	 */
	public function isLoginRequest()
	{
		return preg_match('/^'. self::ACTION_LOGIN . '/', $this->_action) === 1;
	}

	/**
	 * @return string
	 */
	public function getRequestMethod()
	{
		return $this->_method;
	}

	/**
	 * @param string $base
	 * @param boolean $stripGET (optional)
	 * @return string
	 */
	public static function getRequestPath ($base, $stripGET = true)
	{
		$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

		$regex	= '/^'. str_replace('/', '\/', $base) .'/';
		$path	= preg_replace($regex, '', $request_uri);
		return	$stripGET === true ? preg_replace('/\?(.*)$/', '', $path) : $path; //Strip GET path from URI
	}

	/**
	 * @param string $var
	 * @return mixed|null
	 */
	public function get($var)
	{
		return isset($this->_getData[$var]) ? $this->_getData[$var] : null;
	}
}