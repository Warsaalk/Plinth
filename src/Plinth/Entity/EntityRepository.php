<?php

namespace Plinth\Entity;

use Plinth\Connector;

class EntityRepository extends Connector {
	
	/**
	 * @param string $fqcn
	 * @return mixed
	 */
	public function getRepository($fqcn) {
		
		$repositoryClass = $fqcn . 'Repository';
		
		return new $repositoryClass($this->Main());
		
	}
	
}