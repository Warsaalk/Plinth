<?php

namespace Plinth\Entity;

use Plinth\Common\ClassInfo;

abstract class DatabaseEntity {
	
	/**
	 * @var integer
	 */
	private static $_asCounter = 0;
	
	/**
	 * @var string
	 */
	private $_entity;

	/**
	 * @var string
	 */
	private $_as;
	
	/**
	 * @param string $toEntity
	 */
	public function __construct($entityfqcn) {
	
		$this->_entity 	= $entityfqcn;
		$this->_as 		= 'as' . self::$_asCounter++;
	
	}
	
	/**
	 * @return string
	 */
	public function getEntity() {
		
		return $this->_entity;
		
	}
	
	/**
	 * @return string
	 */
	public function getClassName($lower=false) {
		
		return $lower === false ? ClassInfo::getClassName($this->_entity) : ClassInfo::getLowerClassName($this->_entity);
		
	}
	
	/**
	 * @return string
	 */
	public function getTableName() {
		
		return $this->getClassName(true);
		
	}
	
	/**
	 * @return string
	 */
	public function getAs() {
		
		return $this->_as;
		
	}
	
}