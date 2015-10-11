<?php

namespace Plinth\Response;

use Plinth\Connector;
use Plinth\Routing\Route;

class Response extends Connector {
	
	const  CODE_201 = "HTTP/1.0 201 Created",
	       CODE_204 = "HTTP/1.0 204 No Content",
	       CODE_403 = "HTTP/1.0 403 Forbidden",
	       CODE_404 = "HTTP/1.0 404 Not Found",
	       CODE_405 = "HTTP/1.0 405 Method Not Allowed";
	
	/**
	 * @var string|boolean
	 */
	private $_assetVersion = false;
	
	/**
	 * @var array
	 */
	private $_data;
	
	/**
	 * @var string
	 */
	public $content;
	
	/**
	 * @param Main $main
	 */
	public function __construct($main) {
	
		parent::__construct($main);
		
		$this->_assetVersion = $this->Main()->config->get('assets:version');
		$this->_data = array();
	
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
	
		if ($this->_assetVersion) $asset .= '?v=' . $this->_assetVersion;
	
		return $asset;
	
	}
	
	/**
	 * @param string $image
	 * @return string
	 */
	public function getImageUrl($image) {
	    
	    return __IMAGES . $this->getAsset($image);
	    
	}
	
	/**
	 * @param string $script
	 * @param string $media
	 * @return string
	 */
	public function getScripts($script, $media='screen') {
	
		$external = preg_match('/^https?:\/\//', $script) === 1;
		
		return '<script type="text/javascript" src="' . ($external ? '' : __JAVASCRIPT) . $this->getAsset($script) . '" media="' . $media . '"></script>';
	
	}
	
	/**
	 * @param string $css
	 * @param string $cond
	 * @return string
	 */
	public function getCSS($css, $cond=false, $media=array('screen')) {
	
		$cssTag = '<link rel="stylesheet" type="text/css" href="'. __CSS . $this->getAsset( $css ) .'" media="'. implode(',', $media) .'" />';
	
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
	public function getTemplate($tpl, $path = __TEMPLATE) {
		
		return Parser::parse($this,	$tpl, $path, __EXTENSION_TEMPLATE, $this->getDict());
	
	}
	
	/**
	 * @param string $routeName
	 */
	public function hardRedirect($routeName) {
	    
	    header('Location: ' . __BASE_URL . $this->Main()->getRouter()->getRoute($routeName)->getPath());
	    exit;
	    
	}
	
	/**
	 * @return string
	 */
	public function render() {
	
		$router = $this->Main()->getRouter();
	
		if (!$router->hasRoute()) 		$router->redirect('error_404');
		if (!$router->isRouteAllowed()) $router->redirect('error_405');
		 
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
		 
		$contentType= ResponseHelper::getContentType($route->getType());
		$location 	= ResponseHelper::getTemplatePath($route->getType());
	
		if ($contentType !== false) header('Content-type: '. $contentType .'; charset=UTF-8');
	
		$this->content = $this->getTemplate($route->getTemplate(), $location);
	
		if ($route->getType() !== Route::TYPE_PAGE && $route->getType() !== Route::TYPE_ERROR) {
	
			return $this->content;
			 
		} else {
	
			return $this->getTemplate('base');
	
		}
			
	}
	
}