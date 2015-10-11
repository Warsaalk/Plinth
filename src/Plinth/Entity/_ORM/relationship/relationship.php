<?php

namespace Plinth\Entity\ORM\Relationship;

use Plinth\Entity\ORM\RelationshipORM;
use Plinth\Entity\ORM\Field;
use Plinth\Entity\DatabaseEntity;

abstract class Relationship extends DatabaseEntity {
	
	const 	ONE_TO_ONE = 0,
			MANY_TO_ONE = 1,
			MANY_TO_MANY = 2;
	
	protected $type = null;
	
	/**
	 * @var Field
	 */
	private $_column;
	
	/**
	 * @var boolean
	 */
	private $_isMapper = false;
	
	/**
	 * @param string $entityfqcn
	 * @param boolean $isMapper
	 */
	public function __construct($entityfqcn, $isMapper) {
		
		parent::__construct($entityfqcn);
		
		$this->_isMapper = $isMapper;
		
	}
	
	/**
	 * @return integer
	 */
	public function getType() {
		
		return $this->type;
		
	}
	
	/**
	 * @param string $column
	 * @return Relationship
	 */
	public function setColumn($column) {
		
		$this->_column = new Field($column, $this->getAs(), true);
		$this->_column->setType(Field::TYPE_INT);
		
		return $this;
		
	}
	
	/**
	 * @return Field
	 */
	public function getColumn() {
		
		return $this->_column;
		
	}
	
	/**
	 * @return boolean
	 */
	public function isMapper() {
		
		return $this->_isMapper;
		
	}
	
}