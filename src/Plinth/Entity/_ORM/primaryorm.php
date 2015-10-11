<?php

namespace Plinth\Entity\ORM;

/**
 *
 * As Class using Primary needs to extends DatabaseEntity
 *
 */
trait PrimaryORM {
	
	use FieldORM;
	
	/**
	 * @var Primary
	 */
	private $_primary;
	
	/**
	 * @param string $name
	 * @return Primary
	 */
	public function setPrimary($name) {
	
		$this->_primary = new Primary($name, $this->getAs());
	
		$this->_fields[$name] = $this->_primary;
	
		return $this->_primary;
	
	}
	
	/**
	 * @return boolean
	 */
	public function hasPrimary() {
	
		return $this->_primary !== null;
	
	}
	
	/**
	 * @return Primary
	 */
	public function getPrimary() {
	
		return $this->_primary;
	
	}
	
}