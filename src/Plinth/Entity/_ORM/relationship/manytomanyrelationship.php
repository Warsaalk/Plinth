<?php

namespace Plinth\Entity\ORM\Relationship;

use Plinth\Entity\ORM\Field;
use Plinth\Entity\ORM\FieldORM;
use Plinth\Entity\ORM\PrimaryORM;

class ManyToManyRelationship extends Relationship {
	
	use PrimaryORM;
	
	protected $type = self::MANY_TO_MANY;
	
	/**
	 * @param string $entityfqcn
	 * @param boolean $isMapper
	 */
	public function __construct($entityfqcn, $isMapper) {
	
		parent::__construct($entityfqcn, $isMapper);
		
		$this->setPrimary('id');
		
	}
	
	/**
	 * @var Field
	 */
	private $_contraryColumn;
	
	/**
	 * @param string $column
	 * @return ManyToManyRelationship
	 */
	public function setContraryColumn($column) {
		
		$this->_contraryColumn = new Field($column, $this->getAs(), true);
		$this->_contraryColumn->setType(Field::TYPE_INT);
		
		return $this;
		
	}
	
	/**
	 * @return Field
	 */
	public function getContraryColumn() {
		
		return $this->_contraryColumn;
		
	}
	
}