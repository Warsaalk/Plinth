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
			HTTP_ALL	= "ALL",
			HTTP_NOTSET	= NULL;

	const	ACTION_LOGIN = "login";

	const	TYPE_DEFAULT = 0,
			TYPE_LOGIN = 1;

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
	 * @var boolean
	 */
	private $_actionDisabled = false;

	/**
	 * @var integer
	 */
	private $_type = self::TYPE_DEFAULT;

	/**
	 * @var Route
	 */
	private $_route;

	/**
	 * @var Info[]
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

		$this->_data = $this->loadData($this->getRequestMethod());
		$this->_files = $this->loadFiles();

		$this->_type = self::TYPE_DEFAULT;
		$this->_route = $route;

		// Check if there's a login action
		$actions = $this->getPossibleActions();
		for ($i = 0, $il = count($actions); $i < $il; $i++) {
			if (preg_match('/^'. self::ACTION_LOGIN . '\#?/', $actions[$i]) === 1) {
				if ($il > 1) throw new PlinthException("Your request of type {$this->getRequestMethod()} can only have 1 action when using a login action.");

				$this->_type = self::TYPE_LOGIN;
				break;
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
	 * @param string $method
	 * @return array|string
	 */
	private function loadData($method)
	{
		$data = [];

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
		$action = $this->getActionClass($actionLabel, $this->getRequestMethod());
		$actionValidations = [];
		$actionSettings = $action->getSettings();

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
			$token = isset($actionSettings['token']) ? ValidationToken::loadFromArray('token', $actionSettings['token']) : false;
			$user = isset($actionSettings['user']) ? ValidationUser::loadFromArray($actionSettings['user']) : false;

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
		}

		// The actual action validation
		$validator->validate($this->_data, $this->_files);

		// Error collection for this action / validation
		$errors = [];

		if ($validator->isValid()) {
			// Token validation
			if ($token !== false && $token->isRequired() && !$this->main->validateToken($validator->getVariable($token->getName()))) {
				if ($token->getMessage()) $errors[] = $token->getMessage();
				$invalid = true;
			}

			// User validation
			if (!$this->isLoginRequest() && $user !== false && $user->isRequired()) {
				$callback = $user->getCallback();
				if (!$userservice->isSessionValid() || ($callback !== null && !$callback($userservice->getUser()))) {
					if ($user->getMessage()) $errors[] = $user->getMessage();
					$invalid = true;
					header(Response::CODE_401);
				}
			}
		} else {
			// Add errors from the validations if a property was invalid
			foreach ($validator->getErrors() as $error) {
				$errors[] = $error;
			}
			$invalid = true;
		}

		if ($invalid) {
			$actionTemplateData = $action->onError($validator->getValidations());
		} else {
			$actionTemplateData = $action->onFinish($validator->getVariables(), $validator->getFiles(), $validator->getValidations());
		}

		if (is_array($actionTemplateData)) {
			$this->_route->setTemplateData($actionTemplateData);
		}

		$actionFinallyTemplateData = $action->onFinally($validator->getValidations());

		if (is_array($actionFinallyTemplateData)) {
			$this->_route->setTemplateData($actionFinallyTemplateData);
		}

		if ($action->hasErrors()) {
			// Add errors added from the Action
			foreach ($action->getErrors() as $error) {
				$errors[] = $error;
			}
		}

		if (!empty($errors)) {
			$this->addErrors($errors);
			if ($this->main->getSetting('requesterrorstomain')) {
				foreach ($this->_errors as $i => $error) {
					if ($error !== null) {
						$this->main->addInfo($error);
					}
				}
			}
		}
	}

	/**
	 * @throws PlinthException
	 */
	public function isRouteAuthorized()
	{
		$loginpage = $this->main->getSetting('loginpage');

		if (!$this->_route->isPublic()) {
			if (!$this->main->getUserService()->isSessionValid()) {
				if ($this->_route->getName() === $loginpage) throw new PlinthException('Please set your login page to public');

				$this->disableAction();
				$this->main->getRouter()->redirect($loginpage);
				$this->main->handleRequest();
			} else {
				if ($this->_route->hasRoles()) {
					$roles = $this->main->getUserService()->getUser()->getRouteRoles();

					if (!is_array($roles)) throw new PlinthException('The route roles for a user needs to return a array of scalar values.');
					if (!$this->main->getRouter()->isUserRoleAllowed($roles)) {
						$this->main->getResponse()->hardExit(Response::CODE_403);
					}
				}
			}
		}
	}

	/**
	 * @return array
	 * @throws PlinthException
	 */
	public function getPossibleActions()
	{
		$possibleActions = [];

		foreach ([self::HTTP_ALL, $this->getRequestMethod()] as $method) {
			if ($this->_route->hasActions($method)) {
				$possibleActions = array_merge($possibleActions, $this->_route->getActions($method));
			}
		}

		return $possibleActions;
	}

	/**
	 * @throws PlinthException
	 */
	public function handleRequest()
	{
		if (!$this->isActionDisabled()) {
			$actions = $this->getPossibleActions();
			for ($i = 0, $il = count($actions); $i < $il; $i++) {
				$actionLabel = str_replace(self::ACTION_LOGIN . "#", "", $actions[$i]); // Strip the login# from the action name/action fqcn

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
				throw new PlinthException("You controller, {$controllerParts[0]}, must extend " . Controller::class);
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
	 * @param Info $error
	 * @return $this
	 */
	private function addError(Info $error)
	{
		$this->_errors[] = $error;

		return $this;
	}

	/**
	 * @param Info[] $errors
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
	 * @return Info[]
	 */
	public function getErrors()
	{
		return $this->_errors;
	}

	/**
	 * @return $this
	 */
	private function disableAction()
	{
		$this->_actionDisabled = true;

		return $this;
	}

	/**
	 * @return bool
	 */
	private function isActionDisabled()
	{
		return $this->_actionDisabled;
	}

	/**
	 * @return bool
	 */
	public function isLoginRequest()
	{
		return $this->_type === self::TYPE_LOGIN;
	}

	/**
	 * @return string
	 */
	public static function getRequestMethod()
	{
		return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : self::HTTP_NOTSET;
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