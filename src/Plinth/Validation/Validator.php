<?php

namespace Plinth\Validation;

use Plinth\Common\Debug;
use Plinth\Connector;
use Plinth\Common\Info;

class Validator extends Connector {

	/**
	 * Possible validation types
	 */
	const	PARAM_STRING = 0,
			PARAM_INTEGER = 1,
			PARAM_DATE = 2,
			PARAM_EMAIL = 3,
			PARAM_JSON = 4,
			PARAM_PASSWORD = 5,
			PARAM_HTML = 6,
			PARAM_FILE = 7,
			PARAM_MULTIPLE = 100,
			PARAM_MULTIPLE_STRING = 101,
			PARAM_MULTIPLE_DATE = 102,
			PARAM_MULTIPLE_EMAIL = 103,
			PARAM_MULTIPLE_INTEGER = 104,
			PARAM_CHECKBOX = 200,
			PARAM_CHECKBOX_STRING = 201,
			PARAM_CHECKBOX_INTEGER = 202;
			
	/**
	 * Possible validation rules
	 */
	const	RULE_MAX_LENGTH = 'max_length',
			RULE_MAX_INTEGER = 'max_range',
			RULE_MIN_LENGTH = 'min_length',
			RULE_MIN_INTEGER = 'min_range',
			RULE_SELECT = 'select',
			RULE_REGEX = 'regex',
	        RULE_DEFAULT = 'default';
	
	const  MULTIPLE_MIN = 'min',
	       MULTIPLE_MAX = 'max';
	
	/**
	 * @var array
	 */
	private $_vars = array();
	
	/**
	 * @var array
	 */
	private $_files = array();
	
	/**
	 * @var array
	 */
	private $_validate	= array();
	
	/**
	 * @var boolean
	 */
	private $_valid = true;
	
	/**
	 * @var boolean
	 */
	private $_validated = false;
	
	/**
	 * @var FileValidator
	 */
	private $_fileValidator;
	
	/**
	 * @param Main $main
	 */
	public function __construct($main) {
	
		parent::__construct($main);
		
		$this->_fileValidator = new FileValidator();
	
	}
	
	/**
	 * @param array $props
	 * @return array
	 */
	private static function filterOptions($props) {
	    
	    $filter = FILTER_DEFAULT;
	    $options= array();
	    $flags  = FILTER_REQUIRE_SCALAR;
	    
	    /* Define types */
	    switch ($props['type']) {
	        case self::PARAM_MULTIPLE_INTEGER :
	        case self::PARAM_CHECKBOX_INTEGER :
	        case self::PARAM_INTEGER :
	            $filter = FILTER_VALIDATE_INT;
	            if (count($props['rules']) > 0)
	                $options = $props['rules'];
	            break;
	    
	        case self::PARAM_MULTIPLE_EMAIL :
	        case self::PARAM_EMAIL :
	            $filter = FILTER_VALIDATE_EMAIL;
	            break;
	    
	        case self::PARAM_MULTIPLE_DATE :
	        case self::PARAM_DATE :
	            $filter = FILTER_VALIDATE_REGEXP;
	            $options['regexp'] = '/\d{4}-\d{2}-\d{2}/'; //Format yyyy-mm-dd
	            break;
	    
	        case self::PARAM_HTML: //Strip script tags
	            $filter = FILTER_CALLBACK;
	            $htmlv = new HTMLValidator($props['rules']);
	            $options = array($htmlv, 'filter');
	            break;
	    
	        case self::PARAM_MULTIPLE_STRING :
	        case self::PARAM_CHECKBOX_STRING :
	        case self::PARAM_MULTIPLE :
	        case self::PARAM_CHECKBOX :
	        case self::PARAM_STRING :
	            $filter = FILTER_SANITIZE_STRING;
	            break;
	    }
	    
	    /* Define flags */
	    switch ($props['type']) {
	        case self::PARAM_MULTIPLE_STRING :
	        case self::PARAM_CHECKBOX_STRING :
	        case self::PARAM_MULTIPLE :
	        case self::PARAM_CHECKBOX :
	            $flags = FILTER_REQUIRE_ARRAY | FILTER_FLAG_NO_ENCODE_QUOTES;
	            break;
	    
	        case self::PARAM_MULTIPLE_INTEGER :
	        case self::PARAM_CHECKBOX_INTEGER :
	        case self::PARAM_MULTIPLE_EMAIL :
	        case self::PARAM_MULTIPLE_DATE :
	            $flags = FILTER_REQUIRE_ARRAY;
	            break;
	    
	        case self::PARAM_STRING:
	            $flags = FILTER_FLAG_NO_ENCODE_QUOTES;
	            break;
	    }
	    
	    $properties = array(
	        'filter' => $filter,
	        'flags' => $flags
	    );
	    
	    if (count($options) > 0) $properties['options'] = $options;
    	    
	    return $properties;
	    
	}
	
	/**
	 * @param mixed $var
	 * @param integer $type
	 * @return mixed
	 */
	public static function cleanInput($var, $type=self::PARAM_STRING) {
	
        $props = array(
            'type' => $type,
            'rules' => array()  
        );	
		$properties = self::filterOptions($props);

		return filter_var($var, $properties['filter'], $properties);
		
	}
	
	/**
	 * @param string $name
	 * @param array $rules
	 * @param integer $type
	 * @param boolean $required
	 * @param mixed $default
	 */
	public function addValidation($name, $rules=array(), $type=self::PARAM_STRING, $required=true, $default='', $multi=array(), $message=null, $preCallback=false, $postCallback=false) {
	
		$this->_validate[$name] = array( 
			'type' 	=> $type,
			'multi' => $multi,
			'rules' => $rules,
			'req'	=> $required,
			'error'	=> '',
			'raw'	=> false,
		    'default'=> $default,
		    'message'=> $message,
			'preCallback'	=> $preCallback,
			'postCallback'	=> $postCallback
		);

	}
	
	/**
	 * @param string $name
	 * @param string $index
	 * @return mixed|NULL
	 */
	public function getValidation($name, $index=false) {	
		
		if ($index !== false && (isset($this->_validate[$name][$index]) || $this->_validate[$name][$index] === NULL)) 
			return $this->_validate[$name][$index];	
		return isset($this->_validate[$name]) ? $this->_validate[$name] : null; //Avoid irritating notices		
			
	}
	
	/**
	 * @param string $name
	 * @return mixed|NULL
	 */
	public function getVariable($name) {	
		
		return isset($this->_vars[$name]) ? $this->_vars[$name] : null; //Avoid irritating notices		
			
	}
	
	/**
	 * @return array
	 */
	public function getVariables() {
		
		return $this->_vars;
	
	}
	
	/**
	 * @param string $name
	 * @return mixed|NULL
	 */
	public function getFile($name) {	
		
		return isset($this->_files[$name]) ? $this->_files[$name] : null; //Avoid irritating notices		
			
	}
	
	/**
	 * @return array
	 */
	public function getFiles() {
	    
	    return $this->_files;
	    
	}
	
	/**
	 * @return boolean
	 */
	public function isValid() {
		
		return $this->_valid;
	
	}
	
	/**
	 * 
	 */
	public function invalidate() {
		
		$this->_valid = false;
	
	}
	
	/**
	 * @return boolean
	 */
	public function isValidated() {	
		
		return $this->_validated;	
	
	}
	
	/**
	 * @param mixed $value
	 * @param mixed $callback Callback function
	 * @return mixed
	 */
	private function callbackAction($value, $callback) {
		
		if (is_array($value)) {
			foreach ($value as $i => $childValue) {
				$value[$i] = $this->callbackAction($childValue, $callback);
			}
			return $value;
		}
		
		return $callback($value);
		
	}
	
	/**
	 * @param array $form
	 * @param array $files
	 */
	public function validate($form, $files) {
		
	    $varArguments = array();
        $fileArguments = array();
        
		//Loop over all desired variables
		// watchout the properties of a variable are passed by reference
		foreach ($this->_validate as $name => &$props) {

		    $properties = self::filterOptions($props);
		    
		    if ($props['type'] !== self::PARAM_FILE)  $varArguments[$name] = $properties;
		    else                                      $fileArguments[$name] = $props['rules'];
		    
		    if ($props['preCallback'] !== false && is_callable($props['preCallback']) && isset($form[$name])) {
		    	$form[$name] = $this->callbackAction($form[$name], $props['preCallback']);
		    }

			if ($props['req'] === false && isset($form[$name]) && is_scalar($form[$name]) && strlen($form[$name]) === 0) {
				unset($form[$name]); // If the variable is not required and empty unset it!
			}
		}
		
		$this->_vars = filter_var_array($form, $varArguments);

		foreach ($this->_vars as $name => &$data) {
		    
		    $validData = true;
		    
		    if (is_array($data)) $validData = $this->checkMultipleValues($data, $this->_validate[$name]);
		    else	             $validData = $this->checkValue($data, $this->_validate[$name]);
		    
		    if ($validData === false) $this->invalidate();
		    else {
		    	
		    	$postProp = $this->_validate[$name]['postCallback'];
		    	if ($postProp !== false && is_callable($postProp)) {
		    		$data = $this->callbackAction($data, $postProp);
		    	}
		    	
		    }
		    
		}
				
		$this->_files = $this->_fileValidator->filter_array($files, $fileArguments);
		
		foreach ($this->_files as $label => &$filesArray) {
		    
		    if ($this->checkMultipleValues($filesArray, $this->_validate[$label]) === false) $this->invalidate();
		    
		}
				
		$this->_validated = true;

	}
	
	/**
	 * @param mixed $value
	 * @param array $props
	 */
	private function checkValue(&$value, &$props) {

       if ($value === FALSE) return $value = false; //Variable is invalid
       if ($props['req'] === true) {
           if ($value === NULL || $value === "") return $value = false; //Variable is required
       }
       //Only validate rules when the value isn't empty & when there are rules
       if (count($props['rules']) > 0 && $value !== NULL && $value !== "") {
           return $this->validateRules($value, $props);
       }
       return true;
	       	    
	}
	
	/**
	 * @param mixed $value
	 * @param array $props
	 */
	private function checkMultipleValues(&$array, &$props) {
	    
	    $counter       = 0;
	    $validmultiple = true;
	    
	    foreach ($array as $i => &$value) {
	        	        
	        if ($value === FALSE) return $value = false; //If a variable in the array is invalid always return fals
	        if ($value !== NULL && $value !== "") {
	            if ($this->validateRules($value, $props)) $counter++;
                else $validmultiple = false;
	        } else {
	            if ($props['req'] === false) $counter++;
	        }
	        
	    }
	    	    
	    if (isset($props['multi'][self::MULTIPLE_MIN]) || isset($props['multi'][self::MULTIPLE_MAX])) {
	        if (
	            (isset($props['multi'][self::MULTIPLE_MIN]) && $props['multi'][self::MULTIPLE_MIN] > $counter) ||  //If min is defined and min is higher than value
	            (isset($props['multi'][self::MULTIPLE_MAX]) && $props['multi'][self::MULTIPLE_MAX] < $counter)     //If max is deinfed and max is lower than value
	        ) {
	            $validmultiple = false;
	            $array = false;
	        }
	    } else {
	        if ($counter != count($array)) { //If no min or max are defined the counter must match the number of send variables
	            $validmultiple = false;
	            $array = false;
	        }
	    }
	    
	    return $validmultiple;
	    
	}

	/**
	 * @param mixed $value
	 * @param array $props
	 */
	public function validateRules(&$value, &$props) {
	     
	    $rules = $props['rules'];
	     
	    foreach ($rules as $rule => $ruleValue) {
	
	        switch ($rule) {
	             
	            case self::RULE_MAX_LENGTH 	: if (mb_strlen($value) > $ruleValue)				return $value = false;
	               break;
	            case self::RULE_MIN_LENGTH 	: if (!$value || mb_strlen($value) < $ruleValue)	return $value = false;
	               break;
	            case self::RULE_SELECT  	: if (mb_strlen($value) < $ruleValue)          	return $value = false;
	               break;
	            case self::RULE_REGEX   	: if (!preg_match($ruleValue, $value))			return $value = false;
	               break;
	
	        }
	
	    }
	    
	    return true;
	     
	}

	/**
	 * @return array
	 */
	public function getErrors() {
	    
	    $errors = array();
	    
	    $checkValid = function ($name, $data, $index=false) use (&$errors, &$checkValid) {
	        if (is_array($data)) {
	            foreach ($data as $deepName => $deepData) {
	               $checkValid($name, $deepData, $deepName);
	            }
	        } else {
    	        if ($data === false && $this->_validate[$name]['message'] instanceof Info) {
    	            $info = $this->_validate[$name]['message'];
    	            if (!$info->hasLabel()) $info->setLabel($name);
    	            $errors[$name] = $info;
    	        }
	        }
	    };
	    
	    foreach ($this->_vars as $name => $data) {
	        $checkValid($name, $data);
	    }
	    
	    foreach ($this->_files as $name => $data) {
	        $checkValid($name, $data);
	    }
	    
	    return $errors;
	    
	}

}