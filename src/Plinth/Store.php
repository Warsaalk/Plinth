<?php 

namespace Plinth;

class Store extends Connector {
   
    /**
     * @var array
     */
	private $_data;
			
	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key, $value) { 
	    
	    $this->_data[$key] = $value; 
	
	}
	
	/**
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) { 
	    
	    return isset($this->_data[$key]) ? $this->_data[$key] : null; 
	
	}
	
	/**
	 * @param string $key
	 */
	public function destroy($key) {
	    
	    unset($this->_data[$key]);
	    
	}

}