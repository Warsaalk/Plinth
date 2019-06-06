<?php

namespace Plinth\Validation;

use Plinth\Exception\PlinthException;
use Plinth\Main;
use Plinth\Connector;
use Plinth\Common\Message;
use Plinth\Validation\Property\ValidationFile;
use Plinth\Validation\Property\ValidationProperty;
use Plinth\Validation\Property\ValidationVariable;

class Validator extends Connector
{
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
			PARAM_URL = 8,
			PARAM_IP = 9,
			PARAM_MAC = 10,
			PARAM_DOMAIN = 11,
			PARAM_FLOAT = 12,
			PARAM_BOOLEAN = 13,
			PARAM_MULTIPLE = 100,
			PARAM_MULTIPLE_STRING = 101,
			PARAM_MULTIPLE_DATE = 102,
			PARAM_MULTIPLE_EMAIL = 103,
			PARAM_MULTIPLE_INTEGER = 104,
			PARAM_MULTIPLE_URL = 105,
			PARAM_MULTIPLE_IP = 106,
			PARAM_MULTIPLE_MAC = 107,
			PARAM_MULTIPLE_DOMAIN = 108,
			PARAM_MULTIPLE_FLOAT = 109,
			PARAM_MULTIPLE_BOOLEAN = 110,
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
			RULE_DECIMAL = 'decimal',
			RULE_SELECT = 'select',
			RULE_REGEX = 'regex',
	        RULE_DEFAULT = 'default';
	
	const  MULTIPLE_MIN = 'min',
	       MULTIPLE_MAX = 'max';
	
	/**
	 * @var array
	 */
	private $_vars = [];
	
	/**
	 * @var array
	 */
	private $_files = [];
	
	/**
	 * @var ValidationProperty[]
	 */
	private $_validate	= [];
	
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
	public function __construct($main)
	{
		parent::__construct($main);
		
		$this->_fileValidator = new FileValidator();
	}

	/**
	 * @param ValidationProperty $validationProperty
	 * @return array
	 */
	private static function filterOptions(ValidationProperty $validationProperty)
	{
	    $filter = FILTER_DEFAULT;
	    $options= [];
	    $flags  = FILTER_REQUIRE_SCALAR;
	    
	    /* Define types */
	    switch ($validationProperty->getType()) {
	        case self::PARAM_MULTIPLE_INTEGER :
	        case self::PARAM_CHECKBOX_INTEGER :
	        case self::PARAM_INTEGER :
	            $filter = FILTER_VALIDATE_INT;
	            break;

			case self::PARAM_MULTIPLE_URL :
			case self::PARAM_URL :
				$filter = FILTER_VALIDATE_URL;
				break;

			case self::PARAM_MULTIPLE_IP :
			case self::PARAM_IP :
				$filter = FILTER_VALIDATE_IP;
				break;

			case self::PARAM_MULTIPLE_MAC :
			case self::PARAM_MAC :
				$filter = FILTER_VALIDATE_MAC;
				break;

			case self::PARAM_MULTIPLE_DOMAIN :
			case self::PARAM_DOMAIN :
				$filter = FILTER_VALIDATE_DOMAIN;
				break;

			case self::PARAM_MULTIPLE_FLOAT :
			case self::PARAM_FLOAT :
				$filter = FILTER_VALIDATE_FLOAT;
				break;
	    
	        case self::PARAM_MULTIPLE_EMAIL :
	        case self::PARAM_EMAIL :
	            $filter = FILTER_VALIDATE_EMAIL;
	            break;

			case self::PARAM_MULTIPLE_BOOLEAN :
			case self::PARAM_BOOLEAN :
				$filter = FILTER_VALIDATE_BOOLEAN;
				break;
	    
	        case self::PARAM_MULTIPLE_DATE :
	        case self::PARAM_DATE :
	            $filter = FILTER_VALIDATE_REGEXP;
	            $options['regexp'] = '/\d{4}-\d{2}-\d{2}/'; //Format yyyy-mm-dd
	            break;
	    
	        case self::PARAM_HTML: //Strip script tags
	            $filter = FILTER_CALLBACK;
	            $htmlv = new HTMLValidator($validationProperty->getRules());
	            $options = [$htmlv, 'filter'];
	            break;
	    
	        case self::PARAM_MULTIPLE_STRING :
	        case self::PARAM_CHECKBOX_STRING :
	        case self::PARAM_MULTIPLE :
	        case self::PARAM_CHECKBOX :
	        case self::PARAM_STRING :
	            $filter = FILTER_SANITIZE_STRING;
	            break;
	    }

		/* Define options */
		switch ($validationProperty->getType()) {
			case self::PARAM_MULTIPLE_INTEGER :
			case self::PARAM_CHECKBOX_INTEGER :
			case self::PARAM_INTEGER :
			case self::PARAM_MULTIPLE_FLOAT :
			case self::PARAM_FLOAT :
				if ($validationProperty->hasRules())
					$options = array_merge($options, $validationProperty->getRules());
				break;
		}
	    
	    /* Define flags */
	    switch ($validationProperty->getType()) {
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
			case self::PARAM_MULTIPLE_URL :
			case self::PARAM_MULTIPLE_IP :
			case self::PARAM_MULTIPLE_MAC :
			case self::PARAM_MULTIPLE_DOMAIN :
			case self::PARAM_MULTIPLE_FLOAT :
	            $flags = FILTER_REQUIRE_ARRAY;
	            break;
	    
	        case self::PARAM_STRING:
	            $flags = FILTER_FLAG_NO_ENCODE_QUOTES;
	            break;

			case self::PARAM_MULTIPLE_BOOLEAN :
				$flags = FILTER_REQUIRE_ARRAY | FILTER_NULL_ON_FAILURE;
				break;

			case self::PARAM_BOOLEAN :
				$flags = FILTER_NULL_ON_FAILURE;
				break;
	    }

	    /* Add custom flags */
		if ($validationProperty->hasFlags()) {
			$flags |= $validationProperty->getFlags();
		}
	    
	    $properties = ['filter' => $filter, 'flags' => $flags];
	    
	    if (!empty($options)) $properties['options'] = $options;
    	    
	    return $properties;
	}
	
	/**
	 * @param mixed $var
	 * @param integer $type
	 * @return mixed
	 */
	public static function cleanInput($var, $type = self::PARAM_STRING)
	{
        $validation = (new ValidationVariable("tmp"))->setType($type);
		$properties = self::filterOptions($validation);

		return filter_var($var, $properties['filter'], $properties);
	}

	/**
	 * @param ValidationProperty $validationProperty
	 * @return $this
	 */
	public function addValidation(ValidationProperty $validationProperty)
	{
		$this->_validate[$validationProperty->getName()] = $validationProperty;

		return $this;
	}
	
	/**
	 * @param string $name
	 * @param string|bool $member
	 * @return null|ValidationProperty
	 * @throws PlinthException
	 */
	public function getValidation($name, $member = false)
	{
		if (!isset($this->_validate[$name])) return null;

		if ($member !== false) {
			$getter = "get" . ucfirst($member);
			if (method_exists($this->_validate[$name], $getter)) {
				return $this->_validate[$name]->$getter();
			} else {
				throw new PlinthException("$member is not a valid member of {$this->_validate[$name]}");
			}
		}

		return $this->_validate[$name];
	}

	/**
	 * @return ValidationProperty[]
	 */
	public function getValidations()
	{
		return $this->_validate;
	}
	
	/**
	 * @param string $name
	 * @return mixed|NULL
	 */
	public function getVariable($name)
	{
		return isset($this->_vars[$name]) ? $this->_vars[$name] : null; //Avoid irritating notices
	}
	
	/**
	 * @return array
	 */
	public function getVariables()
	{
		return $this->_vars;
	}
	
	/**
	 * @param string $name
	 * @return mixed|NULL
	 */
	public function getFile($name)
	{
		return isset($this->_files[$name]) ? $this->_files[$name] : null; //Avoid irritating notices
	}
	
	/**
	 * @return array
	 */
	public function getFiles()
	{
	    return $this->_files;
	}
	
	/**
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->_valid;
	}

	/**
	 * @return $this
	 */
	public function invalidate()
	{
		$this->_valid = false;

		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function isValidated()
	{
		return $this->_validated;
	}
	
	/**
	 * @param mixed $value
	 * @param mixed $callback Callback function
	 * @return mixed
	 */
	private function callbackAction($value, $callback)
	{
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
	public function validate($form, $files)
	{
	    $varArguments = [];
        $fileArguments = [];
        
		//Loop over all desired variables
		// watchout the properties of a variable are passed by reference
		foreach ($this->_validate as $name => &$validationProperty) {

			$validationProperty->setValue(isset($form[$name]) ? $form[$name] : null);

		    $properties = self::filterOptions($validationProperty);
		    
		    if ($validationProperty instanceof ValidationFile)
				$fileArguments[$name] = $validationProperty->getRules();
		    else
				$varArguments[$name] = $properties;
		    
		    if ($validationProperty->hasPreCallback() && isset($form[$name])) {
		    	$form[$name] = $this->callbackAction($form[$name], $validationProperty->getPreCallback());
		    }

			if (!$validationProperty->isRequired() && isset($form[$name]) && is_scalar($form[$name]) && strlen($form[$name]) === 0) {
				unset($form[$name]); // If the variable is not required and empty unset it!
			}
		}
		
		$this->_vars = filter_var_array($form, $varArguments);

		foreach ($this->_vars as $name => &$data) {
		    if (is_array($data)) $validData = $this->checkMultipleValues($data, $this->_validate[$name]);
		    else	             $validData = $this->checkValue($data, $this->_validate[$name]);
		    
		    if ($validData === false) $this->invalidate();
		    else {
				$this->_validate[$name]->setValid();
		    	if ($this->_validate[$name]->hasPostCallback()) {
		    		$data = $this->callbackAction($data, $this->_validate[$name]->getPostCallback());
		    	}
		    }
		}
				
		$this->_files = $this->_fileValidator->filter_array($files, $fileArguments);
		
		foreach ($this->_files as $label => &$filesArray) {
		    if ($this->checkMultipleValues($filesArray, $this->_validate[$label]) === false) {
		    	$this->invalidate();
			} else {
				$this->_validate[$label]->setValid();
			}
		}
				
		$this->_validated = true;
	}

	/**
	 * @param $value
	 * @param ValidationProperty $validationProperty
	 * @return bool
	 */
	private function checkValue(&$value, ValidationProperty &$validationProperty)
	{
       if ($this->isValueInvalid($value, $validationProperty)) return $value = false; //Variable is invalid

       if ($validationProperty->isRequired()) {
           if ($value === NULL || $value === "") return $value = false; //Variable is required
       }

       //Only validate rules when the value isn't empty & when there are rules
       if ($validationProperty->hasRules() && $value !== NULL && $value !== "") {
           return $this->validateRules($value, $validationProperty);
       }

       return true;
	}

	/**
	 * @param $array
	 * @param ValidationProperty $validationProperty
	 * @return bool
	 */
	private function checkMultipleValues(&$array, ValidationProperty &$validationProperty)
	{
	    $counter       = 0;
	    $validmultiple = true;
	    
	    foreach ($array as $i => &$value) {
	        if ($this->isValueInvalid($value, $validationProperty)) return $value = false; //If a variable in the array is invalid always return fals
	        if ($value !== NULL && $value !== "") {
	            if ($this->validateRules($value, $validationProperty)) $counter++;
                else $validmultiple = false;
	        } else {
	            if (!$validationProperty->isRequired()) $counter++;
	        }
	    }

	    $multiple = $validationProperty->getMultiple();
	    if (isset($multiple[self::MULTIPLE_MIN]) || isset($multiple[self::MULTIPLE_MAX])) {
	        if (
	            (isset($multiple[self::MULTIPLE_MIN]) && $multiple[self::MULTIPLE_MIN] > $counter) ||  //If min is defined and min is higher than value
	            (isset($multiple[self::MULTIPLE_MAX]) && $multiple[self::MULTIPLE_MAX] < $counter)     //If max is deinfed and max is lower than value
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
	 * @param $value
	 * @param ValidationProperty $validationProperty
	 * @return bool
	 */
	private function isValueInvalid($value, ValidationProperty $validationProperty)
	{
		if ($validationProperty->getType() === Validator::PARAM_BOOLEAN ||
			$validationProperty->getType() === Validator::PARAM_MULTIPLE_BOOLEAN) {
			return $value === NULL;
		}

		return $value === FALSE;
	}

	/**
	 * @param $value
	 * @param ValidationProperty $validationProperty
	 * @return bool
	 */
	public function validateRules(&$value, ValidationProperty &$validationProperty)
	{
	    foreach ($validationProperty->getRules() as $rule => $ruleValue) {
	        switch ($rule) {
	            case self::RULE_MAX_LENGTH 	: if (mb_strlen($value) > $ruleValue)				return $value = false;
	               break;
	            case self::RULE_MIN_LENGTH 	: if (!$value || mb_strlen($value) < $ruleValue)	return $value = false;
	               break;
	            case self::RULE_SELECT  	: if (mb_strlen($value) < $ruleValue)          		return $value = false;
	               break;
	            case self::RULE_REGEX   	: if (!preg_match($ruleValue, $value))				return $value = false;
	               break;
	        }
	    }
	    
	    return true;
	}

	/**
	 * @return array
	 */
	public function getErrors()
	{
	    $errors = [];
	    
	    $checkValid = function ($name, $data) use (&$errors, &$checkValid) {
	        if (is_array($data)) {
	            foreach ($data as $deepName => $deepData) {
	               $checkValid($name, $deepData);
	            }
	        } else {
    	        if ($data === false && $this->_validate[$name]->getMessage() instanceof Message) {
    	            $message = $this->_validate[$name]->getMessage();
    	            if (!$message->hasActionLabel()) $message->setActionLabel($name);
    	            $errors[$name] = $message;
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