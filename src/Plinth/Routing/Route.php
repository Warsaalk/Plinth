<?php

namespace Plinth\Routing;

use Translate;
use Plinth\Common\Language;
use Plinth\Exception\PlinthException;
use Plinth\Connector;
use Plinth\Main;

class Route extends Connector {

	const DATA_LANG = 'lang';
	
	/**
	 * Route types
	 */
	const 	TYPE_JSON = 'json',
			TYPE_PAGE = 'page',
			TYPE_HTML = 'html',
			TYPE_XML  = 'xml',
			TYPE_ERROR= 'error';
	
	/**
	 * @var string
	 */
	private $_name;
	
	/**
	 * @var string
	 */
	private	$_path;
	
	/**
	 * @var array
	 */
	private	$_pathData;
	
	/**
	 * @var string
	 */
	private	$_pathDefaultLang;
	
	/**
	 * @var array
	 */
	private	$_data;
	
	/**
	 * @var string
	 */
	private	$_type = false;

	/**
	 * @var string
	 */
	private	$_contentType = false;
	
	/**
	 * @var string
	 */
	private	$_template;
	
	/**
	 * @var boolean
	 */
	private $_default = false;
	
	/**
	 * @var string[]
	 */
	private $_headers;
	
	/**
	 * HTTP Request methods
	 * 
	 * @var string[]
	 */
	private $_methods;
	
	/**
	 * @var string[]
	 */
	private $_actions;

	/**
	 * @var boolean|array
	 */
	private $_roles = false;

	/**
	 * @var boolean
	 */
	private $_rolesAllowed = true;

	/**
	 * @var boolean
	 */
	private $_public;

	/**
	 * @var boolean
	 */
	private $_sessions;
	
	/**
	 * @var CacheSettings
	 */
	private $_cacheSettings;
	
	/**
	 * @param array $args
	 * @param Main $main
	 * @param boolean $public Default public setting
	 * @param boolean $sessions Default sessions setting
	 * @throws PlinthException
	 */
	public function __construct($args, Main $main, $public = false, $sessions = false) {

		parent::__construct($main);
		
		$this->_name = $args['name'];
		$this->_public = $public;
		$this->_sessions = $sessions;
				
		if (!isset($args['path']) ||
			!isset($args['template']) || 
			!isset($args['methods'])) 
			throw new PlinthException('A route needs to have these parameters: path, template, methods');
				
		$this->_path = $args['path'];
		$this->_template = $args['template'];
		$this->_methods = $args['methods'];

		if (isset($args['type'])) $this->_type = $args['type'];
		if (isset($args['contentType'])) $this->_contentType = $args['contentType'];
		if (isset($args['pathData'])) $this->_pathData = $args['pathData'];
		if (isset($args['pathDefaultLang'])) $this->_pathDefaultLang = $args['pathDefaultLang'];
		if (isset($args['default'])) $this->_default = $args['default'];
		if (isset($args['headers'])) $this->_headers = $args['headers'];
		if (isset($args['actions'])) $this->_actions = $args['actions'];
		if (isset($args['public']))	$this->_public = $args['public'];
		if (isset($args['sessions'])) $this->_sessions = $args['sessions'];
		if (isset($args['roles'])) $this->_roles = $args['roles'];
		if (isset($args['rolesAllowed'])) $this->_rolesAllowed = $args['rolesAllowed'];
		
		$this->_cacheSettings = new CacheSettings();
		
		if (isset($args['caching'])) $this->_cacheSettings->load($args['caching']);
		
		$this->_data = array();
		
	}
	
	/**
	 * @param string $label
	 * @param mixed $value
	 */
	public function addData($label, $value) {
		
		$this->_data[$label] = $value;
		
	}
	
	/**
	 * @param string $label
	 * @return mixed|boolean
	 */
	public function get($label) {
		
		return isset($this->_data[$label]) ? $this->_data[$label] : false;
		
	}
	
	/**
	 * @return number
	 */
	public function hasData() {
		
		return count($this->_data);
		
	}
	
	/**
	 * @return mixed[]
	 */
	public function getData() {
		
		return $this->_data;
		
	}
	
	/**
	 * @return string
	 */
	public function getName() {
		
		return $this->_name;
		
	}
	
	/**
	 * @param array $data (optional)
	 * @return string
	 */
	public function getPath(array $data = array()) {
				
		return $this->translatePath($this->_path, $data);
		
	}

	/**
	 * @param array $data (optional)
	 * @return string
	 */
	public function getDefaultLangPath(array $data = array()) {
				
		return $this->translatePath($this->_pathDefaultLang, $data);
		
	}


	/**
	 * @param string $path
	 * @param array $data
	 * @return string
	 */
	private function translatePath($path, array $data) {
		
		if ($this->Main()->getSetting('autoroutelocale') === true && $this->getPathData('lang') === true && !isset($data['lang'])) {
			$lang = $this->Main()->getLang();
			if ($lang) $data['lang'] = $lang;
		}
		
		$callb = function($match) use($data) { return isset($data[$match[1]]) ? $data[$match[1]] : '{'.$match[1].'}'; };
		return preg_replace_callback('/{([\w]+)}/', $callb, $path);
		
	}

	/**
	 * @return string
	 */
	public function getPathRegex() {
	
		$regex = $this->_path;
		
		if ($this->hasPathData() > 0) {

			$data = $this->getPathData();
			$replaceMatches = function ($match) use ($data) {
				if (isset($data[$match[1]])) {
					if ($match[1] === self::DATA_LANG && $data[$match[1]] === true) {
						return '(?<' . $match[1] . '>(' . implode('|', Language::getLanguages()) . '))';
					}
					return '(?<' . $match[1] . '>' . $data[$match[1]] . ')';
				}
				return $match[1];
			};
			$regex = preg_replace_callback('/{(\w+)}/', $replaceMatches, $regex);
			
			if (isset($data[self::DATA_LANG]) && $data[self::DATA_LANG] === true && $this->_pathDefaultLang !== null) {
				$regex = '(?:' . $this->_pathDefaultLang . '|' . $regex . ')';
			}
		}
		
		return $regex;
	
	}
	
	/**
	 * @return array
	 */
	public function getPathData($label = false) {
		
		if ($label !== false) return isset($this->_pathData[$label]) ? $this->_pathData[$label] : null;
		
		return $this->_pathData;
		
	}
	
	/**
	 * @return number
	 */
	public function hasPathData() {
		
		return count($this->_pathData);
		
	}
	
	/**
	 * @return string|boolean
	 */
	public function getType() {
		
		return $this->_type;
		
	}

	/**
	 * @return string|boolean
	 */
	public function getContentType() {

		return $this->_contentType;

	}

	/**
	 * @return boolean
	 */
	public function hasContentType() {

		return $this->_contentType !== false;

	}
	
	/**
	 * @return string
	 */
	public function getTemplate() {
		
		return $this->_template;
		
	}
	
	/**
	 * @return boolean
	 */
	public function isDefault() {
		
		return $this->_default === true;
		
	}
	
	/**
	 * @return string[]
	 */
	public function getHeaders() {
		
		return $this->_headers;
		
	}
	
	/**
	 * @return boolean
	 */
	public function hasHeaders() {
		
		return is_array($this->_headers);
		
	}
	
	/**
	 * @return string[]
	 */
	public function getMethods() {
		
		return $this->_methods;
		
	}
	
	/**
	 * @return boolean
	 */
	public function hasActions() {
		
		return is_array($this->_actions);
		
	}
	
	/**
	 * @return string[]
	 */
	public function getActions() {
		
		return $this->_actions;
		
	}
	
	/**
	 * @return boolean
	 */
	public function isPublic() {
	    
	    return $this->_public;
	    
	}

	/**
	 * @return bool
	 */
	public function allowSessions()
	{
		return $this->_sessions;
	}
	
	/**
	 * @return boolean
	 */
	public function hasCacheSettings() {
		
		return $this->_cacheSettings->hasHeaders();
		
	}
	
	/**
	 * @return CacheSettings
	 */
	public function getCacheSettings() {
		
		return $this->_cacheSettings;
		
	}

	/**
	 * @return boolean
	 */
	public function hasRoles() {

		return $this->_roles !== false && is_array($this->_roles);

	}

	/**
	 * @return boolean|array
	 */
	public function getRoles() {

		return $this->_roles;

	}

	/**
	 * @return boolean|array
	 */
	public function areRolesAllowed() {

		return $this->_rolesAllowed;

	}
	
}