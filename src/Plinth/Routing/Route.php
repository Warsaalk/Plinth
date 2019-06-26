<?php

namespace Plinth\Routing;

use Plinth\Request\Request;
use Plinth\Settings\SettingsDefaults;
use Plinth\Common\Language;
use Plinth\Exception\PlinthException;
use Plinth\Connector;
use Plinth\Main;

class Route extends Connector
{
	const DATA_LANG = 'lang';
	
	/**
	 * Route types
	 */
	const 	TYPE_JSON = 'json',
			TYPE_PAGE = 'page',
			TYPE_HTML = 'html',
			TYPE_XML  = 'xml',
			TYPE_ERROR= 'error';

	const	TPL_EMPTY = null;

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
	private	$_pathData = [];
	
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
	private	$_template = self::TPL_EMPTY;
	
	/**
	 * @var boolean
	 */
	private $_default = false;
	
	/**
	 * @var string[]
	 */
	private $_headers = [];
	
	/**
	 * HTTP Request methods
	 * 
	 * @var string[]
	 */
	private $_methods = [Request::HTTP_GET];
	
	/**
	 * @var string[][]
	 */
	private $_actions = [];

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
	 * @var string
	 */
	private $_templateBase;

	/**
	 * @var string
	 */
	private $_templatePath;

	/**
	 * @var array
	 */
	private $_templateData;

	/**
	 * Fully qualified function name
	 *
	 * @var string
	 */
	private $_controller;
	
	/**
	 * @var CacheSettings
	 */
	private $_cacheSettings;
	
	/**
	 * @param array $args
	 * @param Main $main
	 * @param boolean $public Default public setting
	 * @param boolean $sessions Default sessions setting
	 * @param string $templateBase Template base setting
	 * @param string $templatePath Template base setting
	 * @throws PlinthException
	 */
	public function __construct($args, Main $main, $public = false, $sessions = false, $templateBase = SettingsDefaults::TEMPLATE_BASE, $templatePath = SettingsDefaults::TEMPLATE_PATH)
	{
		parent::__construct($main);
		
		$this->_name = $args['name'];
		$this->_public = $public;
		$this->_sessions = $sessions;
		$this->_templateBase = $templateBase;
		$this->_templatePath = $templatePath;
		$this->_templateData = [];
				
		if (!isset($args['path'])) throw new PlinthException('A route needs to have a path');
				
		$this->_path = $args['path'];

		if (isset($args['template'])) $this->_template = $args['template'];
		if (isset($args['methods'])) $this->_methods = $args['methods'];
		if (isset($args['type'])) $this->_type = $args['type'];
		if (isset($args['contentType'])) $this->_contentType = $args['contentType'];
		if (isset($args['pathData'])) $this->_pathData = $args['pathData'];
		if (isset($args['pathDefaultLang'])) $this->_pathDefaultLang = $args['pathDefaultLang'];
		if (isset($args['default'])) $this->_default = $args['default'];
		if (isset($args['headers'])) $this->_headers = $args['headers'];
		if (isset($args['public']))	$this->_public = $args['public'];
		if (isset($args['sessions'])) $this->_sessions = $args['sessions'];
		if (isset($args['roles'])) $this->_roles = $args['roles'];
		if (isset($args['rolesAllowed'])) $this->_rolesAllowed = $args['rolesAllowed'];
		if (isset($args['controller'])) $this->_controller = $args['controller'];

		if (isset($args['actions']) && is_array($args['actions'])) {
			foreach ($args['actions'] as $actionMethod => $actions) {
				$this->_actions[$actionMethod] = is_array($actions) ? $actions : [$actions];
			}
		}

		$this->_cacheSettings = new CacheSettings();
		
		if (isset($args['caching'])) $this->_cacheSettings->load($args['caching']);
		
		$this->_data = [];
	}

	/**
	 * @param $label
	 * @param $value
	 * @return $this
	 */
	public function addData($label, $value)
	{
		$this->_data[$label] = $value;

		return $this;
	}
	
	/**
	 * @param string $label
	 * @return mixed|boolean
	 */
	public function get($label)
	{
		return isset($this->_data[$label]) ? $this->_data[$label] : false;
	}
	
	/**
	 * @return number
	 */
	public function hasData()
	{
		return count($this->_data);
	}
	
	/**
	 * @return mixed[]
	 */
	public function getData()
	{
		return $this->_data;
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}
	
	/**
	 * @param array $data (optional)
	 * @return string
	 */
	public function getPath(array $data = [])
	{
		return $this->translatePath($this->_path, $data);
	}

	/**
	 * @param array $data (optional)
	 * @return string
	 */
	public function getDefaultLangPath(array $data = [])
	{
		return $this->translatePath($this->_pathDefaultLang, $data);
	}

	/**
	 * @param string $path
	 * @param array $data
	 * @return string
	 */
	private function translatePath($path, array $data)
	{
		$data = array_merge($this->_data, $data);

		if ($this->Main()->getSetting('autoroutelocale') === true && $this->hasPathDataWithLang() && !isset($data['lang'])) {
			$lang = $this->main->getLang();
			if ($lang) $data[self::DATA_LANG] = $lang;
		}
		
		$callb = function($match) use($data) { return isset($data[$match[1]]) ? $data[$match[1]] : '{'.$match[1].'}'; };
		return preg_replace_callback('/{([\w]+)}/', $callb, $path);
	}

	/**
	 * @return \stdClass
	 */
	public function getPathRegex()
	{
		$regex = new \stdClass();
		$regex->path = $this->_path;
		$regex->pathDefaultLang = $this->hasPathDataWithLang() && $this->_pathDefaultLang !== null ? $this->_pathDefaultLang : false;
		
		if ($this->hasPathData() > 0) {
			$data = $this->getPathData();
			$replaceMatches = function ($match) use ($data)
			{
				if (isset($data[$match[1]])) {
					if ($match[1] === self::DATA_LANG && $data[$match[1]] === true) {
						return '(?<' . $match[1] . '>(' . implode('|', Language::getLanguages()) . '))';
					}
					return '(?<' . $match[1] . '>' . $data[$match[1]] . ')';
				}
				return $match[1];
			};

			$regex->path = preg_replace_callback('/{(\w+)}/', $replaceMatches, $regex->path);
			
			if ($regex->pathDefaultLang !== false) {
				$regex->pathDefaultLang = preg_replace_callback('/{(\w+)}/', $replaceMatches, $regex->pathDefaultLang);
			}
		}
		
		return $regex;
	}

	/**
	 * @param bool $label
	 * @return string|null
	 */
	public function getPathData($label = false)
	{
		if ($label !== false) return isset($this->_pathData[$label]) ? $this->_pathData[$label] : null;
		
		return $this->_pathData;
	}
	
	/**
	 * @return number
	 */
	public function hasPathData()
	{
		return count($this->_pathData);
	}

	/**
	 * @return bool
	 */
	public function hasPathDataWithLang()
	{
		return $this->getPathData(self::DATA_LANG) === true;
	}
	
	/**
	 * @return string|boolean
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * @return string|boolean
	 */
	public function getContentType()
	{
		return $this->_contentType;
	}

	/**
	 * @return boolean
	 */
	public function hasContentType()
	{
		return $this->_contentType !== false;
	}

	/**
	 * @param string|null $template
	 * @return $this
	 */
	public function setTemplate($template = self::TPL_EMPTY)
	{
		$this->_template = $template;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplate()
	{
		return $this->_template;
	}
	
	/**
	 * @return boolean
	 */
	public function isDefault()
	{
		return $this->_default === true;
	}

	/**
	 * @param $header
	 * @return $this
	 */
	public function addHeader($header)
	{
		$this->_headers[] = $header;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function hasHeaders()
	{
		return !empty($this->_headers);
	}
	
	/**
	 * @return string[]
	 */
	public function getHeaders()
	{
		return $this->_headers;
	}
	
	/**
	 * @return string[]
	 */
	public function getMethods()
	{
		return $this->_methods;
	}

	/**
	 * @param string $method
	 * @param string $action
	 * @return $this
	 */
	public function addAction($method, $action)
	{
		if (!array_key_exists($method, $this->_actions)) {
			$this->_actions[$method] = [];
		}

		$this->_actions[$method][] = $action;

		return $this;
	}
	
	/**
	 * @param string|boolean $method
	 * @return boolean
	 */
	public function hasActions($method = false)
	{
		if ($method !== false) return array_key_exists($method, $this->_actions) ? !empty($this->_actions[$method]) : false;

		return !empty($this->_actions);
	}
	
	/**
	 * @param string|boolean $method
	 * @return string[]|string[][]
	 * @throws PlinthException
	 */
	public function getActions($method = false)
	{
		if ($method !== false) {
			if (array_key_exists($method, $this->_actions))
				return $this->_actions[$method];
			else
				throw new PlinthException("The method, $method, you are trying to address on route {$this->_name} its actions does not exist.");
		}

		return $this->_actions;
	}
	
	/**
	 * @return boolean
	 */
	public function isPublic()
	{
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
	 * @return string
	 */
	public function getTemplateBase()
	{
		return $this->_templateBase;
	}

	/**
	 * @return string
	 */
	public function getTemplatePath()
	{
		return $this->_templatePath;
	}

	/**
	 * @return array
	 */
	public function getTemplateData()
	{
		return $this->_templateData;
	}

	/**
	 * @param array $templateData
	 * @param bool $merge
	 * @return $this
	 */
	public function setTemplateData($templateData = [], $merge = true)
	{
		if ($merge)	$this->_templateData = array_merge($this->_templateData, $templateData);
		else		$this->_templateData = $templateData;

		return $this;
	}

	/**
	 * @param $templateDataKey
	 * @param mixed|null $templateDataValue
	 * @return $this
	 */
	public function addTemplateData($templateDataKey, $templateDataValue = null)
	{
		$this->_templateData[$templateDataKey] = $templateDataValue;

		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function hasCacheSettings()
	{
		return $this->_cacheSettings->hasHeaders();
	}
	
	/**
	 * @return CacheSettings
	 */
	public function getCacheSettings()
	{
		return $this->_cacheSettings;
	}

	/**
	 * @return boolean
	 */
	public function hasRoles()
	{
		return $this->_roles !== false && is_array($this->_roles);
	}

	/**
	 * @return boolean|array
	 */
	public function getRoles()
	{
		return $this->_roles;
	}

	/**
	 * @return boolean|array
	 */
	public function areRolesAllowed()
	{
		return $this->_rolesAllowed;
	}

	/**
	 * @return bool
	 */
	public function hasController()
	{
		return $this->_controller !== null;
	}

	/**
	 * @return string
	 */
	public function getController()
	{
		return $this->_controller;
	}
}