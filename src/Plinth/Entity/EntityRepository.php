<?php

namespace Plinth\Entity;

use Plinth\Connector;
use Plinth\Exception\PlinthException;

class EntityRepository extends Connector
{
	/**
	 * @var EntityRepository[]
	 */
	private $repositories;

	/**
	 * @param $fqcn
	 * @return EntityRepository
	 * @throws PlinthException
	 */
	public function getRepository($fqcn)
	{
		if (!in_array(self::class, class_parents($fqcn))) {
			$fqcn .= 'Repository'; // Legacy check
			if (!in_array(self::class, class_parents($fqcn))) {
				throw new PlinthException("Your repository, $fqcn, must extend " . self::class);
			}
		}

		if (!isset($this->repositories[$fqcn])) {
			$this->repositories[$fqcn] = new $fqcn($this->main);
		}

		return $this->repositories[$fqcn];
	}
}