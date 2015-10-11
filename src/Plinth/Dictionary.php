<?php 

namespace Plinth;

class Dictionary extends Connector {
	
	CONST 	TYPE_PHP = 'php',
			TYPE_JSON = 'json';
	
    /**
     * @var string[]
     */
	private $_messages = array();
		
	/**
	 * @param string $languageCode
	 * @param string $type
	 */
	public function loadLanguage($languageCode, $type) {
	    
		if ($type === self::TYPE_JSON)	$extension = __EXTENSION_DICT_JSON;
	    else							$extension = __EXTENSION_DICT_PHP;

		$this->loadFile($languageCode, $type, false, $extension);
			
	}
	
	/**
	 * @param string $file
	 * @param string $type
	 * @param string $merge
	 * @param string $extension
	 * @param string $directory
	 */
	public function loadFile($file, $type, $merge = false, $extension = __EXTENSION_PHP, $directory = __DICTIONARY) {
		
		$lang = array();
		 
		if ($type === self::TYPE_JSON)
			$lang = json_decode(file_get_contents($directory . $file . $extension), true);
		else
			require($directory . $file . $extension);
						
		$this->_messages = $merge === true ? array_merge($this->_messages, $lang) : $lang;
		
	}
	
	/**
	 * @param string $i
	 * @param string $val
	 */
	public function set($i, $val) {
	
		$this->_messages[$i] = $val;
	
	}
	
	/**
	 * @param string $i
	 * @return string
	 */
	public function get($i) {
				
		$noTrans = 'No translation present';
		
		$n = func_num_args();
		
		if ($n > 0) {
		
			$s = func_get_arg(0);
			$s = isset($this->_messages[$s]) ? $this->_messages[$s] : 'No translation present';
		
			if ($n > 1) {
		
				$args = func_get_args();
				$args[0] = $s; //Replace original value with translated one
		
				return call_user_func_array('sprintf', $args);
		
			}
		
			return $s;
		
		}
		
		return $noTrans;
	
	}
	
	/**
	 * @param string[] $lang
	 */
	public function addValues($lang) {
	
		$temp = array_merge($this->_messages, $lang);

		$this->_messages = $temp;
	
	}

}