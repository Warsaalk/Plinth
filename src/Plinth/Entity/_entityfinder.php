<?php

namespace Plinth\Entity;

use Plinth\Database\Query\Select_Query;
use Plinth\Database\Connection;
use Plinth\Entity\ORM\Field;
use Plinth\Entity\ORM\EntityORM;
use Plinth\Common\ClassInfo;
use App\Entity\Project\Project;
use Plinth\Entity\ORM\Relationship\Relationship;
use Plinth\Entity\ORM\Relationship\ManyToManyRelationship;
use Plinth\Database\Query\Join_Query;
use Plinth\Common\Debug;

class EntityFinder {
	
	/**
	 * @var Select_Query
	 */
	private $_query;
	
	/**
	 * @var array
	 */
	private $_data = array();
	
	/**
	 * @var integer
	 */
	private $_dataCounter = 0;
	
	/**
	 * @var string
	 */
	private $_entity;
	
	/**
	 * @var EntityORM;
	 */
	private $_entityORM;
	
	/**
	 * @var array
	 */
	private $_useEntities = array();
	
	/**
	 * @var integer
	 */
	private $_fetchStyle;
	
	/**
	 * @param string $entityfqcn Fully Qualified class name
	 */
	public function __construct($entityfqcn, $single = false) {

		$entityormName = $entityfqcn . '_ORM';
		
		$this->_entityORM 	= new $entityormName($entityfqcn);
		$this->_entity 		= $entityfqcn;
		
		$tablename 	= $this->_entityORM->getTableName();
		$tableas	= $this->_entityORM->getAs();
				
		$this->_query = new Select_Query($tablename, $tableas);
		
		$this->_fetchStyle = $single === false ? Connection::FETCH_ALL : Connection::FETCH; 
		
	}
	
	private function handleSelect() {
		
		foreach ($this->_entityORM->getFields(false, $this->getUseEntities()) as $name => $field) {
			if ($field->isVisible()) {
				/* @var $field Field */
				$this->_query->select($field->getColumn());
			}
		}
			
	}
	
	/**
	 * @param EntityORM $entityorm
	 * @parem string $prevTable
	 */
	private function handleRelationships($entityorm, $prevTable=false) {
								
		if ($entityorm->hasRelationships()) {
				
			foreach ($entityorm->getRelationships() as $i => $relationship) {
				/* @var $relationship Relationship */
				
				if (isset($this->_useEntities[$relationship->getEntity()])) {
					
					if ($relationship->getType() === Relationship::MANY_TO_MANY) {
					/* @var $relationship ManyToManyRelationship */
					
						$table	= $entityorm->getTableName();
						$tojoin = $relationship->getTableName();
						$current= '_' . $entityorm->getTableName();
						$tojoin .= $current;
						if ($prevTable !== false) {
							$tojoin .= $prevTable;
						}
						$type	= Join_Query::JOIN;
							
						$condition = $entityorm->getPrimary()->getColumn() . '=' . $relationship->getContraryColumn()->getColumn();
							
						$this->_query->join($tojoin, $type, $condition, $relationship->getAs());
						
						$this->handleRelationships($relationship, $current);
						
					}
					
				}
				
			}
				
		}
		
	}
	
	/**
	 * @param string $property
	 * @param string $operator Where_Query OPERATOR_* constant
	 * @param mixed $value
	 * @param string $seperator Where_Query WHERE_* constant
	 */
	public function filter($property, $operator, $value, $seperator=false) {
		
		$field = $this->_entityORM->getField($property);
		
		$param = ':param_' . $this->_dataCounter++;
		
		$this->_query->where($field->getColumn(), $operator, $param, $seperator);
		$this->_data[] = array($param, $value, $field->getPDOType());
		
	}
	
	/**
	 * @param string $property
	 * @param string $seperator Where_Query WHERE_* constant
	 * @param mixed ...$values
	 */
	public function filterIn($property, $seperator=false, ...$values) {
		
		$field = $this->_entityORM->getField($property);
		$params= array();
		
		foreach ($values as $i => $value) {
			$param = ':param_' . $this->_dataCounter++;
			$params[] = $param;
			$this->_data[] = array($param, $value, $field->getPDOType());
		}
		
		$this->_query->whereIn($field->getColumn(), $seperator, ...$params);
		
	}
	
	/**
	 * @param string $fqcn
	 * @param string $operator Where_Query OPERATOR_* constant
	 * @param mixed $value
	 * @param string $seperator Where_Query WHERE_* constant
	 */
	public function filterByEntity($fqcn, $operator, $value, $seperator=false) {
		
		$this->_useEntities[$fqcn] = true;
		
		$this->filter(ClassInfo::getLowerClassName($fqcn), $operator, $value, $seperator);
		
	}
	
	public function finish() {

		$this->handleSelect();
		$this->handleRelationships($this->_entityORM);
		
	}
	
	/**
	 * @return string
	 */
	public function getEntityFqcn() {
		
		return $this->_entity;
		
	}
	
	/**
	 * @return EntityORM;
	 */
	public function getEntityORM() {
		
		return $this->_entityORM;
		
	}
	
	/**
	 * @return Select_Query
	 */
	public function getQuery() {
		
		return $this->_query;
		
	}
	
	/**
	 * @return array
	 */
	public function getData() {
		
		return $this->_data;
		
	}
	
	/**
	 * @return array
	 */
	public function getUseEntities() {
		
		return $this->_useEntities;
		
	}
	
	/**
	 * @return integer
	 */
	public function getFetchStyle() {
		
		return $this->_fetchStyle;
		
	}

}