<?php 

namespace Plinth\Entity;

use \PDO;

use Plinth\Database\Connection;
use Plinth\Database\Query\Select_Query;
use Plinth\Database\Query\Update_Query;
use Plinth\Database\Query\Delete_Query;
use Plinth\Database\Query\Insert_Query;
use Plinth\Database\Query\Where_Query;
use Plinth\Database\Query\Join_Query;

use Plinth\Connector;

use App\Entity\User\User;
use App\Entity\Project\Project;
use App\Entity\Locale\Locale;
use Plinth\Entity\ORM\Mapper;
use Plinth\Common\Debug;

class EntityManager extends Connector {
	
	/**
	 * @var []
	 */
	private $entities = array();
	
	/**
	 * @var EntityPersistence
	 */
	private $persister;
	
	public function __construct($main) {
		
		parent::__construct($main);
		
		$this->persister = new EntityPersistence();
		
	}
	
	/**
	 * @return Connection
	 */
	private function getConnection(){
		
		return $this->Main()->getConnection();
		
	}
	
	/**
	 * @param EntityFinder $finder
	 * @return mixed
	 */
	public function findEntity($finder) {
		
		$finder->finish();
		
		$queryData = $this->getConnection()->exec($finder->getQuery()->get(), $finder->getData(), $finder->getFetchStyle());

		return Mapper::toEntity($finder, $queryData);
		
	}
	
	/**
	 * @param mixed $entity
	 */
	public function persist($entity) {
		
		$this->persister->persist($entity);
		
	}
	
	/**
	 * @return boolean
	 */
	public function flush() {
		
		return $this->persister->flush();
		
	}
	
}