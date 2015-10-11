<?php

namespace Plinth\Entity\ORM;

use Plinth\Entity\EntityFinder;
use Plinth\Database\Connection;
use Plinth\Entity\ORM\Field;

class Mapper {
	
	/**
	 * @param EntityFinder $finder
	 * @param array $row
	 * @return mixed
	 */
	private static function rowToEntity($finder, $row) {

		$entityName = $finder->getEntityFqcn();
		$entity = new $entityName();
		
		foreach ($finder->getEntityORM()->getFields(false, $finder->getUseEntities()) as $name => $field) {
			/* @var $field Field */
			if ($field->isVisible()) {
				$value = $row[$field->getColumn(false)];
			
				$setter = 'set' . ucfirst($name);
				$entity->$setter($value);
			}
			
		}
		
		return $entity;
		
	}
	
	/**
	 * @param EntityFinder $finder
	 * @param array $data
	 */
	public static function toEntity($finder, $data) {
		
		if ($finder->getFetchStyle() === Connection::FETCH)
			return self::rowToEntity($finder, $data);
		
		$entityArray = array();
		foreach ($data as $i => $row) {
			$entityArray[] = self::rowToEntity($finder, $row);
		}
		return $entityArray;
		
	}
	
}