<?php

namespace Plinth\Entity\ORM;

use Plinth\Entity\ORM\Relationship\ManyToManyRelationship;
use Plinth\Entity\ORM\Relationship\Relationship;
use Plinth\Common\Debug;

/**
 * 
 * As Class using FieldORM needs to extends DatabaseEntity
 *
 */
trait FieldORM {
	
	use RelationshipORM;

	/**
	 * @var Field[]
	 */
	private $_fields = array();

	/**
	 * @param string $name
	 * @return Field
	 */
	public function add($name) {
	
		$field = new Field($name, $this->getAs());
	
		$this->_fields[$name] = $field;
	
		return $field;
	
	}
	
	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasField($name) {
	
		$fields = $this->getFields();
		
		return isset($fields[$name]);
	
	}
	
	/**
	 * @param string $name
	 */
	public function getField($name) {
		
		$fields = $this->getFields();
				
		return $fields[$name];
	
	}
	
	/**
	 * @param boolean $includingRelationships
	 * @return Field[]
	 */
	public function getFields($includingRelationships=true, $useEnitities=false) {
		
		$fields = $this->_fields;
	
		if ($this->hasRelationships()) {
			foreach ($this->getRelationships() as $i => $relationship) {
				/* @var $relationship Relationship */
				if ($useEnitities === false || in_array($relationship->getEntity(), $useEnitities)) {
					if ($relationship->getType() === Relationship::MANY_TO_MANY) {
						/* @var $relationship ManyToManyRelationship */
						$fields += $relationship->getFields($includingRelationships);
						if ($includingRelationships === true)
							$fields += array($relationship->getContraryColumn()->getName() => $relationship->getColumn());
					}
					if ($includingRelationships === true)
						$fields += array($relationship->getColumn()->getName() => $relationship->getColumn());
				}
			}
		}
	
		return $fields;
	
	}
	
	/**
	 * @return Field[]
	 */
	public function getEntityFields() {
		
		return $this->_fields;
		
	}
	
}