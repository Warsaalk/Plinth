<?php 

namespace Plinth;

abstract class Connector {
	
    /**
     * @var Main
     */
	protected $main;

	/**
	 * @param Main $main
	 */
	public function __construct(Main $main) { 
		
		$this->main = $main;
	
	}
	
    /**
     * @return Main
     */
	public function Main() { 
		
		return $this->main;
	
	}
			
}