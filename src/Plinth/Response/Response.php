<?php

namespace Plinth\Response;

use Plinth\Connector;
use Plinth\Routing\Route;
use Plinth\Main;

class Response extends Connector {
	
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
	private $_base;
	
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
	public function __construct($main) {
	
		parent::__construct($main);
		
		$this->assetVersion = $main->config->get('assets:version');
		$this->assetPath = $main->getSetting('assetpath');
		$this->_data = array();
	
		$this->_base = $main->getSetting('templatebase');
		$this->_path = $main->getSetting('templatepath');
		
	}
    
    /**
     * @param string $name
     * @param mixed $value
     * @param string $time
     */
    public function saveCookie($name, $value, $time = NULL) {
        		
    	if ($time !== NULL)	setcookie($name, $value, time() + $time);
    	else    			setcookie($name, $value);
    		
    	$_COOKIE[$name] = $value;
    
    }
	
	/**
	 * @param string $asset
	 * @return string
	 */
	public function getAsset($asset) {

		$external = preg_match('/^https?:\/\//', $asset) === 1;

		if ($this->assetVersion) $asset .= '?v=' . $this->assetVersion;

		if ($this->assetPath !== false && $external !== true) {
			$asset = $this->assetPath . $asset;
		}

		return $asset;
	
	}
	
	/**
	 * @param string $script
	 * @return string
	 */
	public function getScriptTag($script) {

		return '<script type="text/javascript" src="' . $this->getAsset($script) . '"></script>';
	
	}
	
	/**
	 * @param string $css
	 * @param string|boolean $cond
	 * @param array $media
	 * @return string
	 */
	public function getCssTag($css, $cond=false, $media=array('screen')) {
	
		$cssTag = '<link rel="stylesheet" type="text/css" href="' . $this->getAsset( $css ) . '" media="' . implode(',', $media) . '" />';
	
		return $cond !== false ? '<!--[if '. $cond .']>' . $cssTag . '<![endif]-->' : $cssTag;
	
	}
	
	/**
	 * @return Dictionary
	 */
	public function getDict() {
	
		return $this->Main()->getDict();
	
	}
	
	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function addData($key, $value) {
		
		$this->_data[$key] = $value;
		
	}
	
	/**
	 * @return boolean
	 */
	public function hasData() {
		
		return !empty($this->_data);
		
	}
	
	/**
	 * @return array
	 */
	public function getData() {
		
		return $this->_data;
		
	}
	
	/**
	 * @param string $tpl
	 * @param string $path
	 * @return string
	 */
	public function getTemplate($tpl, $path = '') {
		
		$path = $this->_path . $path;
		
		return Parser::parse($this,	$tpl, $path, __EXTENSION_TEMPLATE, $this->getDict());
	
	}
	
	/**
	 * @param string $routeName
	 * @param array $data
	 */
	public function hardRedirect($routeName, array $data = array()) {
	    
	    header('Location: ' . __BASE_URL . $this->Main()->getRouter()->getRoute($routeName)->getPath($data));
	    exit;
	    
	}

	/**
	 * @param string $code This is a HTTP response code
	 */
	public function hardExit($code) {

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
	 */
	public function render() {
	
		$router = $this->Main()->getRouter();
		$route = $router->getRoute();
		 
		if ($route->hasCacheSettings()) {
			foreach ($route->getCacheSettings()->getHeaders() as $property => $value) {
				header($property . ': ' . $value);
			}
		}
		
		if ($route->hasHeaders()) {
			foreach ($route->getHeaders() as $header) {
				header($header);
			}
		}
		 
		$contentType = ResponseHelper::getContentType($route->getType());

		if ($contentType !== false) header('Content-type: '. $contentType .'; charset=' . $this->Main()->getSetting('characterencoding'));
	
		$this->content = $this->getTemplate($route->getTemplate());
	
		if ($route->getType() !== Route::TYPE_PAGE && $route->getType() !== Route::TYPE_ERROR) {
	
			return $this->content;
			 
		} else {
	
			return $this->getTemplate($this->_base);
	
		}
			
	}

	/**
	 * @param $filePath
	 * @param bool $exit
	 * @return bool
	 */
	public function renderFile($filePath, $exit = true) {

		if (file_exists($filePath)) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . basename($filePath));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($filePath));
			ob_clean();
			flush();
			readfile($filePath);
			if ($exit) exit;
		} else {
			return false;
		}

	}
	
}