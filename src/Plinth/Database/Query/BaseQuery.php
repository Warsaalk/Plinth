<?php

namespace Plinth\Database\Query;

use Plinth\Exception\PlinthException;

abstract class BaseQuery implements IQuery
{
	/* Limit types */
	const LIMIT_MYSQL = 0;
	const LIMIT_POSTGRESQL = 1;

	/* Where */
	const WHERE_AND = " AND ";
	const WHERE_OR = " OR ";

	/**
	 * @var string
	 */
	private $table;
	
	/**
	 * @var string
	 */
	private $tableAs;
	
	/**
	 * @var string
	 */
	private $joins;
	
	/**
	 * @var integer
	 */
	private $limit;

	/**
	 * @param $table
	 * @param bool $as
	 */
	public function __construct($table, $as = false)
	{
		$this->joins 	= [];
		$this->table 	= $table;
		$this->tableAs 	= $as;
	}

	/**
	 * @param $limit
	 * @param bool $offset
	 * @param int $notation
	 * @return $this
	 */
	public function limit($limit, $offset = false, $notation = self::LIMIT_MYSQL)
	{
		if (!$this->hasLimit()) $this->limit = "";
			
		if ($offset !== false && $notation === self::LIMIT_MYSQL) $this->limit .= " $offset,";

		$this->limit .= " $limit";

		if ($offset !== false && $notation === self::LIMIT_POSTGRESQL) $this->limit .= " OFFSET $offset";		
		
		return $this;
	}

	/**
	 * @param SelectQuery|string $tojoin
	 * @param string $type
	 * @param bool $condition
	 * @param bool $as
	 * @return $this
	 * @throws PlinthException
	 */
	public function join($tojoin, $type = JoinQuery::JOIN, $condition = false, $as = false)
	{

		if ($tojoin instanceof SelectQuery || $tojoin instanceof UnionQuery || is_string($tojoin))
			$this->joins[] = new JoinQuery($tojoin, $type, $condition, $as);
		else
			throw new PlinthException("It's only possible to join a SelectQuery instance or a string");
		
		return $this;
	}

	/**
	 * @return boolean
	 */
	protected function hasAs()
	{
		return $this->tableAs !== false;
	}
	
	/**
	 * @return boolean
	 */
	protected function hasLimit()
	{
		return !is_null($this->limit);
	}
	
	/**
	 * @return boolean
	 */
	protected function hasJoins()
	{
		return count($this->joins) > 0;
	}

	/**
	 * @return string
	 */
	protected function getTable()
	{
		return $this->table;
	}
	
	/**
	 * @return string
	 */
	protected function getAs()
	{
		return " AS " . $this->tableAs;
	}
	
	/**
	 * @return string
	 */
	protected function getLimit()
	{
		return " LIMIT" . $this->limit;
	}
	
	/**
	 * @return string
	 */
	protected function getJoins()
	{
		$query = "";
		
		foreach ($this->joins as $join) {
			$query .= $join->get();
		}
		
		return $query;
	}
	
	protected function getEnd($end)
	{
		return $end ? self::END : self::NO_END;
	}

	/**
	 * @param bool $end
	 * @return string
	 */
	public abstract function get($end = true);
}