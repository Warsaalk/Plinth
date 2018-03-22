<?php
namespace Plinth\Common;

trait Rest {
	
	/**
	 * @param mixed $value
	 * @return mixed
	 */
	private function getPropValue($value, $used) {
		
		if (is_object($value)) {
			
			if (isset($used[spl_object_hash($value)])) return "*RECURSION*";
				
			return  $value->getPropertyDataArray($used);
		}
		return $value;
		
	}
	
	/**
	 * @param string $class_name
	 * @return array
	 */
	public function getPropertyDataArray($used = array()) {
		
		$class_name = __CLASS__;
		$properties = get_class_vars($class_name);
		$methods	= get_class_methods($class_name);
		$data		= [];
	
		$used[spl_object_hash($this)] = true;
				
		foreach ($properties as $property => $default) {
			
			$getpropertyname = 'get' . ucfirst($property);
			if (in_array($getpropertyname, $methods)) { //Replace in_array with isset maybe
				$propvalue = $this->$getpropertyname();				
			    if (!is_array($propvalue)) {
				    $data[$property] = $this->getPropValue($propvalue, $used);
			    } else {
			    	foreach ($propvalue as $value) {
			    		$data[$property][] = $this->getPropValue($value, $used);
			    	}
			    }
			}
				
		}
	
		return $data;
		
	}
	
	abstract public function getId();
		
}