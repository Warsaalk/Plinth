<?php

namespace Plinth\Common;

class ClassInfo {
	
	/**
	 * @param string $fqcn
	 * @return string
	 */
	public static function getClassName($fqcn) {
		
		return substr(strrchr($fqcn, '\\'), 1);
		
	}
	
	/**
	 * @param string $fqcn
	 * @return string
	 */
	public static function getLowerClassName($fqcn) {
		
		return strtolower(self::getClassName($fqcn));
		
	}
	
}