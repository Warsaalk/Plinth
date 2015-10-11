<?php 

namespace Plinth;

abstract class Connector {
	
    /**
     * @var Main
     */
	private $_main;

	/**
	 * @param Main $main
	 */
	public function __construct(Main $main) { 
		
		$this->_main = $main; 
	
	}
	
    /**
     * @return Main
     */
	public function Main() { 
		
		return $this->_main;	
	
	}
			
}