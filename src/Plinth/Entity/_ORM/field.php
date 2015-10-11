<?php

namespace Plinth\Entity\ORM;

use PDO;

class Field {
	
	const 	TYPE_STRING = 'varchar',
			TYPE_TEXT = 'text',
			TYPE_INT = 'int',
			TYPE_TINYINT = 'tinyint',
			TYPE_BLOB = 'mediumblob',
			TYPE_TIMESTAMP = 'timestamp';
	
	/**
	 * @var string
	 */
	private $_name;
	
	/**
	 * @var string
	 */
	private $_column;
	
	/**
	 * @var string
	 */
	protected $_type = self::TYPE_STRING;
	
	/**
	 * @var integer
	 */
	private $_length = false;
	
	/**
	 * @var boolean
	 */
	private $_empty = false;
	
	/**
	 * @var mixed
	 */
	private $_default;
	
	/**
	 * Whether to hide the field when fetching it
	 * 
	 * @var boolean
	 */
	private $_visible = true;
	
	/**
	 * @var string
	 */
	private $_as;
	
	/**
	 * @var boolean
	 */
	private $_referenceId;
	
	/**
	 * @param string $name
	 */
	public function __construct($name, $as, $referenceId=false) {
		
		if (!is_string($name)) throw new Exception('Field::set $name can only be of type string');
		
		$this->_name = $name;
		$this->_column = $name;
		$this->_as = $as;
		
		$this->_referenceId = $referenceId;
		
	}
	
	/**
	 * @param string $column
	 * @throws Exception
	 * @return Field
	 */
	public function useColumn($column = false) {
		
		if ($column !== false && !is_string($column)) throw new Exception('Field::useColumn $column can only be of type string');
		
		$this->_column = $column;
		
		return $this;
		
	}
	
	/**
	 * @param string $type
	 * @return Field
	 */
	public function setType($type = self::TYPE_STRING) {
		
		if (!is_string($type)) throw new Exception('Field::setType $type can only be a constant of type Field::TYPE_*');
		
		$this->_type = $type;
		
		return $this;
		
	}
	
	/**
	 * @param integer $length
	 * @throws Exception
	 * @return Field
	 */
	public function setLength($length = false) {
		
		if ($length !== false && (!is_int($length) || $length < 1)) throw new Exception('Field::setLength $length can only be of type integer and must be positive');
		
		$this->_length = $length;
		
		return $this;
		
	}
	
	/**
	 * @param boolean $empty
	 * @throws Exception
	 * @return Field
	 */
	public function allowEmpty($empty = false) {
		
		if (!is_bool($empty)) throw new Exception('Field::allowEmpty $empty can only be of type boolean');
		
		$this->_empty = $empty;
		
		return $this;
		
	}
	
	/**
	 * @param mixed $default
	 */
	public function setDefault($default) {
		
		$this->_default = $default;
		
	}
	
	/**
	 * @return string
	 */
	public function getName() {
		
		return $this->_name;
		
	}
	
	/**
	 * @return string
	 */
	public function getColumn($withAs=true) {
		
		$column = $this->_column;
		
		if ($this->isReferenceId()) $column .= '_id';
		if ($withAs === true) $column = $this->_as . '.' . $column;
		
		return $column;
		
	}
	
	/**
	 * @return string
	 */
	public function getType() {
		
		return $this->_type;
		
	}
	
	/**
	 * @return integer
	 */
	public function getLength() {
		
		return $this->_length;
		
	}
	
	/**
	 * @return boolean
	 */
	public function emptyAllowed() {
		
		return $this->_empty;
		
	}
	
	/**
	 * @return mixed
	 */
	public function getDefault() {
		
		return $this->_default;
		
	}
	
	/**
	 * Hide the field
	 */
	public function hide() {
		
		$this->_visible = false;
		
	}
	
	/**
	 * @return boolean
	 */
	public function isVisible() {
		
		return $this->_visible;
		
	}
	
	/**
	 * @return boolean
	 */
	public function isReferenceId() {

		return $this->_referenceId;
		
	}
	
	/**
	 * @return integer
	 */
	public function getPDOType() {
				
		switch ($this->getType()) {
			
			case self::TYPE_BLOB:
				return PDO::PARAM_LOB;
			
			case self::TYPE_INT:
			case self::TYPE_TINYINT:
				return PDO::PARAM_INT;
			
			case self::TYPE_TIMESTAMP:
			case self::TYPE_TEXT:
			case self::TYPE_STRING:
			default:
				return PDO::PARAM_STR;
			
		}
				
	}
	
}