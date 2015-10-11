<?php

namespace Plinth\Entity\ORM;

class Primary extends Field {
	
	/**
	 * @var string
	 */
	protected $_type = self::TYPE_INT;
	
	/**
	 * @var boolean
	 */
	private $_autoincrement = false;
	
	/**
	 * @return Primary
	 */
	public function useAutoIncrement() {
		
		$this->_autoincrement = true;
		
		return $this;
		
	}
	
	/**
	 * @return boolean
	 */
	public function usesAutoIncrement() {
		
		return $this->_autoincrement;
		
	}
	
}