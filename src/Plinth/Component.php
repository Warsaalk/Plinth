<?php

namespace Plinth;

use Plinth\Exception\PlinthException;
use Plinth\Common\Debug;

class Component {
		
	/**
	 * @var string
	 */
	private $base;
		
	/**
	 * @var string
	 */
	private $label;

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var string|boolean
	 */
	private $config = false;

	/**
	 * @var string|boolean
	 */
	private $routing = false;
	
	/**
	 * @var boolean
	 */
	private $mergeDefaultConfig = false;

	/**
	 * @var boolean
	 */
	private $mergeDefaultRouting = false;
		
	/**
	 * @param string $base
	 * @param string $label
	 * @param array $properties
	 * @throws PlinthException
	 */
	public static function loadFromArray($base, $label, $properties) {
				
		if (!isset($properties['path'])) throw new PlinthException('Please define the path for you component.');
		if (!isset($properties['config']) && !isset($properties['routing'])) throw new PlinthException('Please define at least a config or routing.');
		
		$self = new self($base, $label, $properties['path']);
		
		if (isset($properties['config'])) {
			if (!file_exists(__APP_CONFIG_PATH . $properties['config'])) throw new PlinthException('The config you defined does not exist.');
			$self->setConfig($properties['config']);
		}
		
		if (isset($properties['routing'])) {
			if (!file_exists(__APP_CONFIG_PATH . $properties['routing'])) throw new PlinthException('The routing you defined does not exist.');
			$self->setRouting($properties['routing']);
		}
		
		if (isset($properties['configMergeDefault']) && $properties['configMergeDefault']) $self->enableDefaultConfigMerge();
		if (isset($properties['routingMergeDefault']) && $properties['routingMergeDefault']) $self->enableDefaultRoutingMerge();
		
		return $self;
		
	}
	
	/**
	 * @param string $base
	 * @param string $label
	 * @param string $path
	 */
	public function __construct($base, $label, $path) {
		
		$this->base = $base;
		$this->label = $label;
		$this->path = $path;
		
	}
	
	public function matchesCurrentPath($currentPath) {
		
		$regex = '/^' . str_replace('/', '\/', $this->path). '($|\/)/';
				
		if ($this->path === false || preg_match($regex, $currentPath) === 1) return true;
		
		return false;
		
	}
	
	/**
	 * @return string
	 */
	public function getLabel() {
		
		return $this->label;
		
	}
	
	/**
	 * @return string
	 */
	public function getPath() {
		
		return $this->path;
		
	}
	
	/**
	 * @param string $config
	 */
	public function setConfig($config) {
		
		$this->config = $config;
		
	}
	
	/**
	 * @return boolean|string
	 */
	public function getConfig() {
		
		return $this->config;
		
	}

	/**
	 * @return bool
	 */
	public function hasConfig() {

		return $this->config !== false;

	}
	
	/**
	 * @return boolean|string
	 */
	public function getConfigPath() {
		
		return $this->base . $this->config;
		
	}
	
	/**
	 * @param string $routing
	 */
	public function setRouting($routing) {
		
		$this->routing = $routing;
		
	}
	
	/**
	 * @return boolean|string
	 */
	public function getRouting() {
		
		return $this->routing;
		
	}

	/**
	 * @return bool
	 */
	public function hasRouting() {

		return $this->routing !== false;

	}
	
	/**
	 * @return boolean|string
	 */
	public function getRoutingPath() {
		
		return $this->base . $this->routing;
		
	}
	
	public function enableDefaultConfigMerge() {
		
		$this->mergeDefaultConfig = true;
		
	}
	
	/**
	 * @return boolean
	 */
	public function getMergeDefaultConfig() {
		
		return $this->mergeDefaultConfig;
		
	}
	
	public function enableDefaultRoutingMerge() {
		
		$this->mergeDefaultRouting = true;
		
	}
	
	/**
	 * @return boolean
	 */
	public function getMergeDefaultRouting() {
		
		return $this->mergeDefaultRouting;
		
	}
	
	/**
	 * @return integer
	 */
	public function getDepth() {
		
		if ($this->path === false) return -1;
		
		return substr_count($this->path, '/');
		
	}
		
}