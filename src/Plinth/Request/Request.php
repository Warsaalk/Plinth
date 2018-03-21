<?php

namespace Plinth\Request;

use Plinth\Connector;
use Plinth\Routing\Route;
use Plinth\Validation\Property\ValidationFile;
use Plinth\Validation\Property\ValidationToken;
use Plinth\Validation\Property\ValidationUser;
use Plinth\Validation\Property\ValidationVariable;
use Plinth\Validation\Validator;
use Plinth\Common\Info;
use Plinth\Main;
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
	 * @var array
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

	/**
	 * @return array
	 */
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
		$token		= isset($actionSettings['token']) ? ValidationToken::loadFromArray('token', $actionSettings['token']) : false;
		$user		= isset($actionSettings['user']) ? ValidationUser::loadFromArray($actionSettings['user']) : false;
		$invalid	= false;

		if ($variables !== false) {
			foreach ($variables as $name => $settings) {
				$validator->addValidation(ValidationVariable::loadFromArray($name, $settings));
			}
		}

		if ($uploadfiles !== false) {
			foreach ($uploadfiles as $name => $settings) {
				$validator->addValidation(ValidationFile::loadFromArray($name, $settings));
			}
		}

		if ($token !== false) {
			$validator->addValidation($token);
		}

		$validator->validate($this->_data, $this->_files);

		if ($validator->isValid()) {
			if ($token !== false && $token->isRequired() && !$this->main->validateToken($validator->getVariable($token->getName()))) {
				if ($token->getMessage()) $this->addError($token->getMessage());
				$invalid = true;
			}

			if (!$this->isLoginRequest() && $user !== false && $user->isRequired()) {
				$callback = $user->getCallback();
				if (!$userservice->isSessionValid() || ($callback !== null && !$callback($userservice->getUser()))) {
					if ($user->getMessage()) $this->addError($user->getMessage());
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