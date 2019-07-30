<?php

namespace Plinth\Request;

use Plinth\Connector;
use Plinth\Controller\Controller;
use Plinth\Routing\Route;
use Plinth\Validation\Property\ValidationFile;
use Plinth\Validation\Property\ValidationToken;
use Plinth\Validation\Property\ValidationUser;
use Plinth\Validation\Property\ValidationVariable;
use Plinth\Validation\Validator;
use Plinth\Common\Message;
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
			HTTP_OPTIONS= "OPTIONS",
			HTTP_ALL	= "ALL",
			HTTP_NOTSET	= NULL;

	const	ACTION_LOGIN = "login";

	const	TYPE_DEFAULT = 0,
			TYPE_LOGIN = 1;

	const
			CONTENT_TYPE_MULTIPART_FORM_DATA = "multipart/form-data",
			CONTENT_TYPE_APPLICATION_WWW_FORM = "application/x-www-form-urlencoded",
			CONTENT_TYPE_APPLICATION_JSON = "application/json",
			CONTENT_TYPE_NOTSET = NULL;

	/**
	 * Contains form data
	 *
	 * @var array
	 */
	private $_data;

	/**
	 * @var string
	 */
	private $contentType;

	/**
	 * @var string
	 */
	private $requestMethod;

	/**
	 * Contains file info
	 *
	 * @var array
	 */
	private $_files;

	/**
	 * @var integer
	 */
	private $_loginActionLabel = null;

	/**
	 * @var Route
	 */
	private $_route;

	/**
	 * @var Message[]
	 */
	private $_errors = [];

	/**
	 * Request constructor.
	 * @param Main $main
	 * @param Route $route
	 * @throws PlinthException
	 */
	public function __construct(Main $main, Route $route)
	{
		parent::__construct($main);

		$this->contentType = self::getContentType();
		$this->requestMethod = self::getRequestMethod();

		$this->_route = $route;
	}

	/**
	 * @throws PlinthException
	 */
	public function prepare()
	{
		$this->_data = $this->loadData();
		$this->_files = $this->loadFiles();

		// Check if there's a login action
		$actions = $this->getPossibleActions();
		for ($i = 0, $il = count($actions); $i < $il; $i++) {
			if (preg_match('/^'. self::ACTION_LOGIN . '\#?/', $actions[$i]) === 1) {
				if (!$this->hasLoginAction()) {
					$this->_loginActionLabel = str_replace(self::ACTION_LOGIN . "#", "", $actions[$i]);
				} else {
					throw new PlinthException("Your request of type {$this->requestMethod} for route {$this->_route->getName()} can only have 1 login action.");
				}
			}
		}
	}

	/**
	 * @return array
	 */
	private function loadFiles()
	{
		$files = [];

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
	 * @return array|string
	 */
	private function loadData()
	{
		$data = [];

		if (strcmp($this->contentType, self::CONTENT_TYPE_MULTIPART_FORM_DATA) === 0 && strcmp($this->requestMethod, self::HTTP_POST) === 0) {
			$data = $_POST;
		} elseif (strcmp($this->contentType, self::CONTENT_TYPE_APPLICATION_WWW_FORM) === 0) {
			switch ($this->requestMethod) {
				case self::HTTP_POST: $data = $_POST; break;
				case self::HTTP_PUT:
				case self::HTTP_DELETE: parse_str(file_get_contents('php://input'), $data);break;
				default;
			}
		} elseif (strcmp($this->contentType, self::CONTENT_TYPE_APPLICATION_JSON) === 0) {
			$data = json_decode(file_get_contents('php://input'), true);
		} elseif (strcmp($this->requestMethod, self::HTTP_POST) === 0 || strcmp($this->requestMethod, self::HTTP_PUT) === 0 || strcmp($this->requestMethod, self::HTTP_DELETE) === 0) {
			$data = file_get_contents('php://input');
		} elseif (strcmp($this->requestMethod, self::HTTP_GET) === 0) {
			$data = $_GET;
		}

		return $data;
	}

	/**
	 * @param string $actionClassName
	 * @param string $method
	 * @return ActionType|ActionTypeLogin
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

		if (class_exists($actionClassName, $legacyActionClassName !== $actionClassName)) {
			return new $actionClassName($this->main);
		} else {
			throw new PlinthException("Your action class, $actionClassName, cannot be found.");
		}
	}

	/**
	 * @param string $actionLabel
	 * @throws PlinthException
	 */
	private function validateAction($actionLabel)
	{
		$action = $this->getActionClass($actionLabel, $this->requestMethod);
		$actionValidations = [];
		$actionSettings = $action->getSettings();
		$actionLogin = in_array(ActionTypeLogin::class, class_parents($action));

		if ($actionLogin && strcmp($actionLabel, $this->_loginActionLabel) !== 0) throw new PlinthException("Your action, " . get_class($action) . ", implements " . ActionTypeLogin::class . ", only login actions can implement this action type.");

		$validator 	= $this->main->getValidator($actionLabel);
		$userservice= $this->main->getUserService();

		$invalid = $token = $user = false;

		// Validation settings using the set validations on the action
		$action->setValidations($actionValidations);
		foreach ($actionValidations as $actionValidation) {
			if ($actionValidation instanceof ValidationUser) {
				$user = $actionValidation;
			} else {
				if ($actionValidation instanceof ValidationToken) {
					$token = $actionValidation;
				}

				$validator->addValidation($actionValidation);
			}
		}

		// Validation settings using the array syntax returned by getSettings
		if (is_array($actionSettings)) {
			$variables = isset($actionSettings['variables']) ? $actionSettings['variables'] : false;
			$uploadfiles = isset($actionSettings['files']) ? $actionSettings['files'] : false;
			$tokenLegacy = isset($actionSettings['token']) ? ValidationToken::loadFromArray('token', $actionSettings['token']) : false;
			$userLegacy = isset($actionSettings['user']) ? ValidationUser::loadFromArray($actionSettings['user']) : false;

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

			if ($token === false && $tokenLegacy !== false) {
				$token = $tokenLegacy;
				$validator->addValidation($token);
			}

			if ($user === false && $userLegacy !== false) {
				$user = $userLegacy;
			}
		}

		// The actual action validation
		$validator->validate($this->_data, $this->_files);

		if ($validator->isValid()) {
			// Token validation
			if ($token !== false && $token->isRequired() && !$this->main->validateToken($validator->getVariable($token->getName()))) {
				if ($token->getMessage()) $action->addError($token->getMessage());
				$invalid = true;
			}

			// User validation
			if (strcmp($actionLabel, $this->_loginActionLabel) !== 0 && $user !== false && $user->isRequired()) {
				$callback = $user->getCallback();
				if (!$userservice->isSessionValid() || ($callback !== null && !$callback($userservice->getUser()))) {
					if ($user->getMessage()) $action->addError($user->getMessage());
					$invalid = true;
					header(Response::CODE_401);
				}
			}
		} else {
			// Add errors from the validations if a property was invalid
			foreach ($validator->getErrors() as $error) {
				$action->addError($error);
			}
			$invalid = true;
		}

		$actionLoginTemplateData = null;

		if ($invalid) {
			$actionTemplateData = $action->onError($validator->getValidations());
		} else {
			$actionTemplateData = $action->onFinish($validator->getVariables(), $validator->getFiles(), $validator->getValidations());

			if ($actionLogin) {
				$loginLabel = $action->getLoginLabel();
				$tokenLabel = $action->getTokenLabel();
				$tokenData = $validator->getVariable($tokenLabel);
				if ($tokenData === null) throw new PlinthException("The token label $tokenLabel can't be found in your login action, " . get_class($action) . ".");

				if ($loginLabel === null) {
					$loginSuccess = $userservice->loginWithToken($tokenData);
				} else {
					$loginData = $validator->getVariable($loginLabel);
					if ($loginData === null) throw new PlinthException("The login label $loginLabel can't be found in your login action, " . get_class($action) . ".");
					$loginSuccess = $userservice->login($loginData, $tokenData);
				}

				if ($loginSuccess) {
					$actionLoginTemplateData = $action->onLoginSuccess($validator->getVariables(), $validator->getValidations());
				} else {
					$actionLoginTemplateData = $action->onLoginFailed($validator->getVariables(), $validator->getValidations());
				}
			}
		}

		if (is_array($actionTemplateData)) {
			$this->_route->setTemplateData($actionTemplateData);
		}

		if (is_array($actionLoginTemplateData)) {
			$this->_route->setTemplateData($actionLoginTemplateData);
		}

		$actionFinallyTemplateData = $action->onFinally($validator->getValidations());

		if (is_array($actionFinallyTemplateData)) {
			$this->_route->setTemplateData($actionFinallyTemplateData);
		}

		if ($action->hasErrors()) {
			$this->addErrors($action->getErrors());
			if ($this->main->getSetting('requesterrorstomain')) {
				foreach ($this->_errors as $i => $error) {
					if ($error !== null) {
						$this->main->addMessage($error);
					}
				}
			}
		}
	}

	/**
	 * @return bool
	 */
	public function handlePreFlightRequest ()
	{
		return $this->requestMethod === self::HTTP_OPTIONS
			&& isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"])
			&& isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"])
			&& isset($_SERVER["HTTP_ORIGIN"])
			&& $this->_route->isCorsAllowed();
	}

	/**
	 * @param $method
	 * @return bool
	 */
	private function isRouteAllowedForMethod ($method)
	{
		return in_array($method, $this->_route->getMethods()) === true;
	}

	/**
	 * Only allow defined methods for specific route.
	 *
	 * @return bool
	 */
	public function isRouteAllowed()
	{
		return $this->isRouteAllowedForMethod($this->requestMethod);
	}

	/**
	 * @return boolean|string
	 * @throws PlinthException
	 */
	public function isRouteAuthorized()
	{
		$loginpage = $this->main->getSetting('loginpage');

		if (!$this->_route->isPublic()) {
			if (!$this->main->getUserService()->isSessionValid()) {
				if ($this->_route->getName() === $loginpage) throw new PlinthException('Please set your login page to public');

				return $loginpage;
			} else {
				if ($this->_route->hasRoles()) {
					$roles = $this->main->getUserService()->getUser()->getRouteRoles();

					if (!is_array($roles)) throw new PlinthException('The route roles for a user needs to return a array of scalar values.');
					if (!$this->main->getRouter()->isUserRoleAllowed($roles)) {
						$this->main->getResponse()->hardExit(Response::CODE_403);

						return $loginpage;
					}
				}
			}
		}

		return true;
	}

	/**
	 * @return array
	 * @throws PlinthException
	 */
	public function getPossibleActions()
	{
		$possibleActions = [];

		foreach ([self::HTTP_ALL, $this->requestMethod] as $method) {
			if ($this->_route->hasActions($method)) {
				$possibleActions = array_merge($possibleActions, $this->_route->getActions($method));
			}
		}

		return $possibleActions;
	}

	/**
	 * @throws PlinthException
	 */
	public function handleLoginRequest()
	{
		if ($this->hasLoginAction())
		{
			$this->main->addValidator($this->_loginActionLabel);
			$this->validateAction($this->_loginActionLabel);
		}
	}

	/**
	 * @throws PlinthException
	 */
	public function handleRequest()
	{
		$actions = $this->getPossibleActions();
		for ($i = 0, $il = count($actions); $i < $il; $i++) {
			$actionLabel = $actions[$i];
			// Login requests will be handled earlier in the flow
			if (preg_match('/^'. self::ACTION_LOGIN . '\#?/', $actionLabel) === 0) {
				// On a single action/the first action set the default validator as the action validator
				if ($il === 1 || $i === 0) {
					$this->main->addValidator($actionLabel, $this->main->getValidator());
				} else {
					$this->main->addValidator($actionLabel);
				}

				$this->validateAction($actionLabel);
			}
		}
	}

	/**
	 * @throws PlinthException
	 */
	public function handleController()
	{
		if ($this->_route->hasController()) {
			$controllerParts = explode("::", $this->_route->getController());
			if (count($controllerParts) !== 2 || !class_exists($controllerParts[0]) || !method_exists($controllerParts[0], $controllerParts[1])) {
				throw new PlinthException("Your controller::function, {$this->_route->getController()}, cannot be found.");
			}

			if (!in_array(Controller::class, class_parents($controllerParts[0]))) {
				throw new PlinthException("Your controller, {$controllerParts[0]}, must extend " . Controller::class);
			}

			$controller = new $controllerParts[0]($this->main);
			$data = $controller->{$controllerParts[1]}($this->_route);

			if (is_array($data)) {
				if (isset($data[0]) && is_array($data[0])) {
					$this->_route->setTemplateData($data[0]);
				}

				if (isset($data['template'])) $this->_route->setTemplate($data['template']);
			}
		}
	}

	/**
	 * @param Message[] $errors
	 * @return $this
	 */
	private function addErrors($errors)
	{
		$this->_errors = array_merge($this->_errors, $errors);

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function hasErrors()
	{
		return !empty($this->_errors);
	}

	/**
	 * @return Message[]
	 */
	public function getErrors()
	{
		return $this->_errors;
	}

	/**
	 * @return array|string
	 */
	public function getRawData()
	{
		return $this->_data;
	}

	/**
	 * @return bool
	 */
	private function hasLoginAction()
	{
		return $this->_loginActionLabel !== null;
	}

	/**
	 * @return string
	 */
	public static function getRequestMethod()
	{
		return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : self::HTTP_NOTSET;
	}

	/**
	 * @return array
	 */
	public static function getSupportedContentTypes()
	{
		return [
			self::CONTENT_TYPE_APPLICATION_WWW_FORM,
			self::CONTENT_TYPE_MULTIPART_FORM_DATA,
			self::CONTENT_TYPE_APPLICATION_JSON
		];
	}

	/**
	 * @return string|null
	 */
	public static function getContentType()
	{
		if (isset($_SERVER['CONTENT_TYPE'])) {
			$requestContentType = strtolower($_SERVER['CONTENT_TYPE']);
			foreach (self::getSupportedContentTypes() as $contentType) {
				if (stripos($requestContentType, $contentType) !== false) return $contentType;
			}
		}

		return self::CONTENT_TYPE_NOTSET;
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
	public static function get($var)
	{
		return isset($_GET[$var]) ? Validator::cleanInput($_GET[$var]) : null;
	}
}