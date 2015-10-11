<?php

namespace Plinth\Entity\ORM;

use Plinth\Entity\ORM\Relationship\RelationshipFactory;
use Plinth\Entity\DatabaseEntity;

abstract class EntityORM extends DatabaseEntity {
	
	use PrimaryORM;
	
	public function __construct($fqcn) {
		
		parent::__construct($fqcn);
		
		$this->setPrimary('id'); //Set id as default primary in case it won't be defined
		$this->map();
		
	}
	
	/**
	 * 
	 */
	public abstract function map();
	
}