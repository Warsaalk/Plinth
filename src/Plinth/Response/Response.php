<?php

namespace Plinth\Response;

use Plinth\Connector;
use Plinth\Dictionary;
use Plinth\Routing\Route;
use Plinth\Main;

class Response extends Connector
{
	const  CODE_201 = "HTTP/1.0 201 Created",
	       CODE_204 = "HTTP/1.0 204 No Content",
	       CODE_401 = "HTTP/1.0 401 Unauthorized",
	       CODE_403 = "HTTP/1.0 403 Forbidden",
	       CODE_404 = "HTTP/1.0 404 Not Found",
	       CODE_405 = "HTTP/1.0 405 Method Not Allowed";

	/**
	 * @var string|boolean
	 */
	private $assetVersion = false;

	/**
	 * @var string|boolean
	 */
	private $assetPath;
	
	/**
	 * @var array
	 */
	private $_data;
	
	/**
	 * @var string
	 */
	private $_path;
	
	/**
	 * @var string
	 */
	public $content;
	
	/**
	 * @param Main $main
	 */
	public function __construct($main)
	{
		parent::__construct($main);
		
		$this->assetVersion = $main->config->get('assets:version');
		$this->assetPath = $main->getSetting('assetpath');

		$this->_data = [];
		$this->_path = $main->getSetting('templatepath');
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param string $time
	 * @return $this
	 */
    public function saveCookie($name, $value, $time = NULL)
	{
    	if ($time !== NULL)	setcookie($name, $value, time() + $time);
    	else    			setcookie($name, $value);
    		
    	$_COOKIE[$name] = $value;

    	return $this;
    }
	
	/**
	 * @param string $asset
	 * @return string
	 */
	public function getAsset($asset)
	{
		$external = preg_match('/^https?:\/\//', $asset) === 1;

		if ($this->assetVersion) $asset .= '?v=' . $this->assetVersion;

		if ($this->assetPath !== false && $external !== true) {
			$asset = $this->assetPath . $asset;
		}

		return $asset;
	}

	/**
	 * @param array $attributes
	 * @return string
	 */
	public function createHTMLAttributes ($attributes = [])
	{
		$copy = $attributes;
		array_walk($copy, function (&$value, $key) {
			$value = $key . '="' . addcslashes($value, '"') . '"';
		});
		return implode(" ", $copy);
	}
	
	/**
	 * @param string $script
	 * @param array $attributes
	 * @return string
	 */
	public function getScriptTag($script, $attributes = [])
	{
		return '<script type="text/javascript" src="' . $this->getAsset($script) . '" ' . $this->createHTMLAttributes($attributes) . '></script>';
	}
	
	/**
	 * @param string $css
	 * @param string|boolean $cond
	 * @param array $media
	 * @param array $attributes
	 * @return string
	 */
	public function getCssTag($css, $cond = false, $media = ['screen'], $attributes = [])
	{
		$cssTag = '<link rel="stylesheet" type="text/css" href="' . $this->getAsset( $css ) . '" media="' . implode(',', $media) . '" ' . $this->createHTMLAttributes($attributes) . ' />';
	
		return $cond !== false ? '<!--[if '. $cond .']>' . $cssTag . '<![endif]-->' : $cssTag;
	}
	
	/**
	 * @return Dictionary
	 */
	public function getDict()
	{
		return $this->main->getDict();
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	public function addData($key, $value)
	{
		$this->_data[$key] = $value;

		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function hasData()
	{
		return !empty($this->_data);
	}
	
	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->_data;
	}

	/**
	 * @param string $template
	 * @param array $templateData
	 * @param string|bool $templatePath
	 * @return string
	 */
	public function getTemplate($template, $templateData = [], $templatePath = false)
	{
		if ($template === Route::TPL_EMPTY) return ""; // If there's no template return an empty string

		$path = $templatePath !== false ? $templatePath : $this->_path;
		
		return $this->parse($template, $templateData, $path, __EXTENSION_TEMPLATE);
	}

	/**
	 * @param Route $route
	 * @return string
	 */
	public function getTemplateByRoute(Route $route)
	{
		return $this->getTemplate($route->getTemplate(), $route->getTemplateData(), $route->getTemplatePath());
	}

	/**
	 * @param $routeName
	 * @return string
	 * @throws \Plinth\Exception\PlinthException
	 */
	public function getTemplateByRouteName($routeName)
	{
		return $this->getTemplateByRoute($this->main->getRouter()->getRoute($routeName));
	}

	/**
	 * @param $routeName
	 * @param array $data
	 * @throws \Plinth\Exception\PlinthException
	 */
	public function hardRedirect($routeName, array $data = [])
	{
	    header('Location: ' . __BASE_URL . $this->Main()->getRouter()->getRoute($routeName)->getPath($data));
	    exit;
	}

	/**
	 * @param string $code This is a HTTP response code
	 * @throws \Plinth\Exception\PlinthException
	 */
	public function hardExit($code)
	{
		$router = $this->Main()->getRouter();
		$exitRoute = false;

		switch ($code) {
			case self::CODE_403: $exitRoute = $this->Main()->getSetting('route403'); break;
			case self::CODE_404: $exitRoute = $this->Main()->getSetting('route404'); break;
			case self::CODE_405: $exitRoute = $this->Main()->getSetting('route405'); break;
		}

		header($code);
		if ($exitRoute !== false && $router->hasRoute($exitRoute)) {
			$router->redirect($exitRoute);
		} else {
			exit;
		}
	}

	/**
	 * @return string
	 * @throws \Plinth\Exception\PlinthException
	 */
	public function render()
	{
		$router = $this->main->getRouter();
		$route = $router->getRoute();
		 
		if ($route->hasCacheSettings()) {
			foreach ($route->getCacheSettings()->getHeaders() as $property => $value) {
				header($property . ': ' . $value);
			}
		}

		if ($route->isCorsAllowed()) {
			if ($route->getCors() === true) {
				$route->addHeader("Access-Control-Allow-Origin: *");
			} elseif (in_array($_SERVER['HTTP_ORIGIN'], $route->getCors())) {
				$route->addHeader("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
			}
		}
		
		if ($route->hasHeaders()) {
			foreach ($route->getHeaders() as $header) {
				header($header);
			}
		}

		$contentType = ResponseHelper::getContentType($route);

		if ($contentType !== false) header('Content-type: '. $contentType .'; charset=' . $this->Main()->getSetting('characterencoding'));

		$this->content = $this->getTemplateByRoute($route);

		if ($route->getType() !== Route::TYPE_PAGE && $route->getType() !== Route::TYPE_ERROR) {
			return $this->content;
		} else {
			return $this->getTemplate($route->getTemplateBase(), $route->getTemplateData(), $route->getTemplatePath());
		}
	}

	/**
	 * @param $filePath
	 * @param $fileName
	 * @param bool $exit
	 * @return bool
	 */
	public function renderFile($filePath, $fileName, $exit = true)
	{
		if (file_exists($filePath)) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . basename($fileName));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($filePath));
			ob_clean();
			flush();
			readfile($filePath);
			if ($exit) exit;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param string $template
	 * @param array $templateData
	 * @param string $path
	 * @param string $tplExt
	 * @return string
	 */
	public function parse($template, $templateData = [], $path = "", $tplExt = __EXTENSION_PHP)
	{
		$fullPath = $path . $template . $tplExt;

		if (!file_exists($fullPath)) return false;

		/*
		 * Create shorthand for translating string via the dictionary
		 */
		if ($this->getDict() !== null) {
			$dictionary = $this->getDict();
			$__ = function () use ($dictionary) {
				return call_user_func_array([$dictionary, 'get'], func_get_args());
			};
		}

		/*
		 * Push data into variables
		 */
		if ($this->hasData()) {
			$templateData = array_merge($templateData, $this->getData());
		}

		foreach ($templateData as $cantoverride_key => $cantoverride_value) {
			${$cantoverride_key} = $cantoverride_value;
		}
		unset($cantoverride_key);
		unset($cantoverride_value);

		$self = $this; // Legacy support $self contains the Response instance

		ob_start();
		require $fullPath;
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
}