<?php

namespace Plinth\Entity\ORM\Relationship;

class RelationshipFactory {
	
	/**
	 * @param string $toEntity
	 * @param integer $type
	 * @param boolean $isMapper
	 */
	public static function create($toEntity, $type, $isMapper = false) {
		
		switch ($type) {
			
			case Relationship::ONE_TO_ONE: 		return new OneToOneRelationship($toEntity, $isMapper);
			case Relationship::MANY_TO_ONE: 	return new ManyToOneRelationship($toEntity, $isMapper);
			case Relationship::MANY_TO_MANY: 	return new ManyToManyRelationship($toEntity, $isMapper);
			
		}
		
	}
	
}