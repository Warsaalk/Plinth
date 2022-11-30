<?php 

namespace Plinth;

use Plinth\Exception\PlinthException;

class Dictionary extends Connector
{
	CONST 	TYPE_PHP = 'php',
			TYPE_JSON = 'json';

	/**
	 * @var string[]
	 */
	private $_fallback = [];
	
    /**
     * @var string[]
     */
	private $_messages = [];

	/**
	 * @param string $languageCode
	 * @param string $type
	 * @param boolean $fallback (optional)
	 * @return Dictionary
	 * @throws PlinthException
	 */
	public function loadLanguage($languageCode, $type, $fallback = false)
	{
		if ($type === self::TYPE_JSON)	$extension = __EXTENSION_DICT_JSON;
	    else							$extension = __EXTENSION_DICT_PHP;

		return $this->loadFile($languageCode, $type, false, $extension, __DICTIONARY, $fallback);
	}

	/**
	 * @param $file
	 * @param $type
	 * @param bool $merge
	 * @param string $extension
	 * @param string $directory
	 * @param bool $fallback
	 * @return $this
	 * @throws PlinthException
	 */
	public function loadFile($file, $type, $merge = false, $extension = __EXTENSION_PHP, $directory = __DICTIONARY, $fallback = false)
	{
		$lang = [];
		$path = $directory . $file . $extension;
		
		if (file_exists($path)) {
			if ($type === self::TYPE_JSON)
				$lang = json_decode(file_get_contents($path), true);
			else
				require($path);
			
			return $this->loadFromArray($lang, $merge, $fallback);
		} else {
			throw new PlinthException("Dictionary: Please create your language file, $file");
		}
	}

	/**
	 * @param array $lang
	 * @param bool $merge
	 * @param bool $fallback
	 * @return $this
	 */
	public function loadFromArray($lang = [], $merge = false, $fallback = false)
	{
		if ($fallback === true) $this->_fallback = $merge === true ? array_merge($this->_fallback, $lang) : $lang;
		else					$this->_messages = $merge === true ? array_merge($this->_messages, $lang) : $lang;

		return $this;
	}

	/**
	 * @param string $i
	 * @param string $val
	 * @return $this
	 */
	public function set($i, $val)
	{
		$this->_messages[$i] = $val;

		return $this;
	}
	
	/**
	 * @param string $i
	 * @return string
	 */
	public function get($i)
	{
		$n = func_num_args();
		
		if ($n > 0) {
			$s = func_get_arg(0);
			$s = isset($this->_messages[$s]) ? $this->_messages[$s] : (isset($this->_fallback[$s]) ? $this->_fallback[$s] : $s);
		
			if ($n > 1) {
				$args = func_get_args();
				$args[0] = $s; //Replace original value with translated one
		
				return call_user_func_array('sprintf', $args);
			}
			return $s;
		}
		return $i;
	}

    /**
     * @return string[]
     */
    public function getAll ()
    {
        return $this->_messages;
    }

	/**
	 * @param string[] $lang
	 */
	public function addValues($lang)
	{
		$temp = array_merge($this->_messages, $lang);

		$this->_messages = $temp;
	}
}