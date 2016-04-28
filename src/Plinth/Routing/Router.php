<?php

namespace Plinth\Routing;

use Plinth\Connector;
use Plinth\Common\Debug;
use Plinth\Exception\PlinthException;
use Plinth\Request\Request;

class Router extends Connector {
	
	/**
	 * @var string
	 */
	private $_path;
	
	/**
	 * @var Route
	 */
	private $_route;
	
	/**
	 * @var Route[]
	 */
	private $_routes = array();
	
	/**
	 * @var Route|boolean
	 */
	private $_defaultRoute = false;
	
	/**
	 * @var boolean
	 */
	private $_redirected = false;
	
	/**
	 * @param string $routesFile
	 * @param boolean $public Default public setting
	 * @throws PlinthException
	 */
	public function loadRoutes($routesFile, $public) {
		
		if (!file_exists($routesFile)) throw new PlinthException('Add routing.json to your application config');
		
		$routes = json_decode(file_get_contents($routesFile), true);
		
		if (!is_array($routes)) throw new PlinthException('Cannot parse routing.json config');
		
		foreach ($routes as $routeName => $routeInfo) {
			
			$newroute = new Route(array('name' => $routeName) + $routeInfo, $this->Main(), $public);
			$this->_routes[$routeName] = $newroute;
			
			if ($newroute->isDefault()) {
				if ($this->_defaultRoute !== false) throw new PlinthException('You can only define 1 default route');
				$this->_defaultRoute = $newroute;
			}
			
		}
		
	}
	
	/**
	 * @throws PlinthException
	 * @return boolean
	 */
	private function findRoute() {
		
		if ($this->_path === "" && $this->_defaultRoute !== false) {
			
			$this->_route = $this->_defaultRoute;
			return true;
			
		} else {
		
			foreach ($this->_routes as $routeName => $route) {
				
				$routeData 	= array();
				$routeRegex = '/^' . str_replace('/', '\/', $route->getPathRegex()) . '$/i';
								
				if(preg_match($routeRegex, $this->_path, $routeData) === 1) {
					
					if (count($routeData) > 1) {
						
						if ($route->hasPathData() < 1) throw new PlinthException('Please define your route data');
						
						foreach ($route->getPathData() as $label => $regex) {
							
							if (!isset($routeData[$label])) throw new PlinthException('Your route data indexes don\'t match');
								
							$route->addData($label, $routeData[$label]);
							
						}
						
					}
					
					$this->_route = $route;
					return true;
					
				}
				
			}
			
			return false;
		
		}
		
	}
	
	/**
	 * @param string $base
	 */
	public function handleRoute($base) {
	    
		$this->_path = Request::getRequestPath($base);
		
		$this->findRoute();
		
	}
	
	/**
	 * @param string $routeName
	 * @param array $routeData
	 * @throws PlinthException
	 */
	public function redirect($routeName) {
		
		if (!isset($this->_routes[$routeName])) throw new PlinthException('Can\'t redirect to non existing route');
		
		//TODO:: Maybe check if routeData is defined 
		
		$this->_route = $this->_routes[$routeName];
		$this->_redirected = true;
		
	}
	
	/**
	 * @param string|boolean $name
	 * @return boolean
	 */
	public function hasRoute($name=false) {

		if ($name !== false) return isset($this->_routes[$name]);

		return $this->_route !== null;
		
	}
	
	/**
	 * @throws PlinthException
	 * @return Route
	 */
	public function getRoute($name=false) {
		
	    if ($name === false) {

	        if (!$this->hasRoute()) throw new PlinthException('No route selected yet');
	        
	        return $this->_route;
	        
	    } else {
	        
	        if (isset($this->_routes[$name])) return $this->_routes[$name];
	        else throw new PlinthException('The route does not exist');
	        
	    }
		
	}
	
	/**
	 * @return boolean
	 */
	public function hasDefault() {
		
		return $this->_defaultRoute !== false;
		
	}
	
	/**
	 * @throws PlinthException
	 * @return Route|boolean
	 */
	public function getDefault() {
		
		if ($this->_defaultRoute === false)  throw new PlinthException('No default route defined');
			
		return $this->_defaultRoute;
		
	}
	
	/**
	 * Only allow defined methods for specific route. When a route is redirected it is always allowed
	 * 
	 * @return boolean
	 */
	public function isRouteAllowed() {
		
		return $this->_redirected || in_array($this->Main()->getRequest()->getRequestMethod(), $this->getRoute()->getMethods()) === true;
		
	}
	
	/**
	 * @return string
	 */
	public function getRequestPath() {
		
		return $this->_path;
		
	}
	
}