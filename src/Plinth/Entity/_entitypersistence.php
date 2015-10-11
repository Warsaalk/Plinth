<?php

namespace Plinth\Entity;

use Plinth\Common\Debug;
use Plinth\Entity\ORM\Field;
use Plinth\Common\ClassInfo;

class EntityPersistence {
	
	/**
	 * @var mixed[]
	 */
	private $_objects = array();
	
	/**
	 * @var string[]
	 */
	private $_queryVariables = array();
	
	/**
	 * @var string[]
	 */
	private $_queryData = array();
	
	/**
	 * @var integer
	 */
	private $_rowCount = 0;
	
	/**
	 * @param mixed $entity
	 */
	public function persist($entity) {
		
		$this->_objects[] = $entity;
			
	}
	
	public function flush() {
		
		//$this->getConnection()->beginTransaction();
		
		foreach ($this->_objects as $object) {
			
			$this->processObject($object);
			
		}
		
		Debug::dump($this->_queryVariables);
		Debug::dump($this->_queryData);
		
		//$this->getConnection()->commitTransaction();
		
	}
	
	private function processObject($object) {
		
		$entityfqcn = get_class($object);
		
		$entityORMName = $entityfqcn . '_ORM';
		$entityORM = new $entityORMName($entityfqcn);
		
		if (!isset($this->_queryVariables[$entityfqcn]))
			$this->_queryVariables[$entityfqcn] = $this->getEntityVariables($entityORM);
		
		$this->_queryData[$entityfqcn][] = $this->getEntityData($entityfqcn, $object);
		
		$this->processHas($entityORM, $object);
		
	}
	
	private function processHas($entityORM, $object) {
		
		$has = $entityORM->getHas();
		
		foreach ($has as $entityBox) {
			
			$className = ClassInfo::getClassName($entityBox->entity);
			$propertyMethod = 'get' . ucfirst($className);
			
			if ($entityBox->multiple === true) {
				$propertyMethod = $propertyMethod . 's';
				$propertyObjects = $object->$propertyMethod();
				foreach ($propertyObjects as $propertyObject) {
					$this->processObject($propertyObject);
				}
			} else {
				$this->processObject($object->$propertyMethod());	
			}
			
		}
		
	}
	
	private function getEntityVariables($entityORM) {
		
		$fields = $entityORM->getEntityFields();
		$columns = array();
		
		foreach ($fields as $name => $field) {
			/* @var $field Field */
			$columns[] = $field->getColumn(false);
		}
		
		return $columns;
		
	}
	
	private function getEntityData($entityfqcn, $object) {
		
		$fields = $this->_queryVariables[$entityfqcn];
		$data = array();
		
		foreach ($fields as $field) {
			
			$propertyGetter = 'get' . ucfirst($field);
			$column = ':' . $field . $this->_rowCount;
			
			$data[$column] = $object->$propertyGetter();
			
		}
		
		$this->_rowCount++;
		
		return $data;
		
	}
	
}