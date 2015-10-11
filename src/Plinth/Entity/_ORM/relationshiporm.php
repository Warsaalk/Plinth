<?php

namespace Plinth\Entity\ORM;

use Plinth\Entity\ORM\Relationship\RelationshipFactory;

trait RelationshipORM {
	
	/**
	 * @var Relationship[]
	 */
	private $_relationships = array();
	
	/**
	 * @param string $toEntity
	 * @param integer $type
	 * @parem boolean $isMapper
	 * @return Ambigous <OneToOneRelationship, ManyToOneRelationship, ManyToManyRelationship>
	 */
	public function addRelationship($toEntity, $type, $isMapper = false) {
		
		$relationship = RelationshipFactory::create($toEntity, $type, $isMapper);
		
		$this->_relationships[] = $relationship;
		
		return $relationship;
		
	}
	
	/**
	 * @return boolean
	 */
	public function hasRelationships() {
		
		return count($this->_relationships) > 0;
		
	}
	
	/**
	 * @param string $entity
	 * @return boolean
	 */
	public function hasRelationshipWith($entity) {
		
		foreach ($this->_relationships as $i => $relationship) {
			
			if ($relationship->getEntity() === $entity) return true;
			
		}
		
		return false;
		
	}
	
	/**
	 * @return Relationship[]
	 */
	public function getRelationships() {
		
		return $this->_relationships;
		
	}
	
}