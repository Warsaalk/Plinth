<?php

namespace Plinth\Routing;

class CacheSettings {

	const TIME_FORMAT = "D, d M Y H:i:s T";
	
	const DEFAULT_CONTROL = "no-store, no-cache, must-revalidate, post-check=0, pre-check=0";
	
	/**
	 * @var string
	 */
	private $control;
	
	/**
	 * @var integer
	 */
	private $maxage;
	
	/**
	 * @var integer
	 */
	private $smaxage;
	
	/**
	 * @var string
	 */
	private $expires;
	
	/**
	 * @var boolean
	 */
	private $loaded = false;
	
	/**
	 * @param string $control
	 */
	public function __construct($control = self::DEFAULT_CONTROL) {
		
		$this->control = $control;
		
	}
	
	/**
	 * @param array $props
	 */
	public function load($props) {
		
		$this->loaded = true;
		
		if (isset($props['control'])) 	$this->control 	= $props['control'];
		if (isset($props['maxage'])) 	$this->maxage 	= $props['maxage'];
		if (isset($props['smaxage'])) 	$this->smaxage 	= $props['smaxage'];
		if (isset($props['expires']))	$this->expires 	= gmdate(self::TIME_FORMAT, strtotime($props['expires']));
		
	}
	
	/**
	 * @return boolean
	 */
	public function hasHeaders() {
		
		return $this->loaded;
		
	}
	
	/**
	 * @return array
	 */
	public function getHeaders() {
		
		$headers = array();
		
		if ($this->control)	$headers['Cache-Control'] = $this->control;
		
		if ($this->maxage) $headers['Cache-Control'] .= ", max-age=" . $this->maxage;
		
		if ($this->smaxage) $headers['Cache-Control'] .= ", s-maxage=" . $this->smaxage;
		
		if ($this->expires) $headers['Expires'] = $this->expires;
		
		return $headers;
		
	}
	
}