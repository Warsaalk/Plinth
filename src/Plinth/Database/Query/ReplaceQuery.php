<?php

namespace Plinth\Database\Query;

use Plinth\Exception\PlinthException;

class ReplaceQuery extends InputQuery
{
	/**
	 * @param $value
	 * @param int $column
	 * @return $this
	 * @throws PlinthException
	 */
	public function replace ($value, $column = self::VALUES_ONLY)
	{
		return parent::input($value, $column);
	}

	/**
	 * @param SelectQuery $query
	 * @param array $columns
	 * @return $this
	 * @throws PlinthException
	 */
	public function replaceSelectQuery(SelectQuery $query, $columns = [])
	{
	    return parent::inputSelectQuery($query, $columns);
	}
	
	/**
	 * @return string
	 */
	private function getReplace()
	{
		return "REPLACE INTO ";
	}

	/**
	 * @param bool $end
	 * @return string
	 * @throws PlinthException
	 */
	public function get($end = true)
	{
		return $this->getReplace() . " " . $this->getTable() . $this->getData() . $this->getEnd($end);
	}
}