<?php

namespace Plinth\Database\Query;

use Plinth\Exception\PlinthException;

class InsertQuery extends InputQuery
{
	/**
	 * Column => Data for ON DUPLICATE KEY UPDATE
	 *
	 * @var array
	 */
	private $_update = [];

	/**
	 * @param $value
	 * @param int $column
	 * @return $this
	 * @throws PlinthException
	 */
	public function insert ($value, $column = self::VALUES_ONLY)
	{
		return parent::input($value, $column);
	}

	/**
	 * @param SelectQuery $query
	 * @param array $columns
	 * @return $this
	 * @throws PlinthException
	 */
	public function insertSelectQuery(SelectQuery $query, $columns = [])
	{
	    return parent::inputSelectQuery($query, $columns);
	}
	
	/**
	 * @return string
	 */
	private function getInsert()
	{
		return "INSERT INTO ";
	}

	/**
	 * @return string
	 */
	private function getOnDuplicateKeyUpdate()
	{
		return " ON DUPLICATE KEY UPDATE ";
	}

	/**
	 * @param $column
	 * @param $value
	 * @return $this
	 */
	public function updateOnDuplicateKey ($column, $value)
	{
		$this->_update[$column] = $value;

		return $this;
	}

	/**
	 * @return bool
	 */
	private function hasUpdateData()
	{
		return count($this->_update) > 0;
	}

	/**
	 * @return string
	 */
	private function getUpdateData()
	{
		$updateData = [];
		foreach ($this->_update as $column => $value) {
			$updateData[] = $column . "=" . $value;
		}

		return implode(', ', $updateData);
	}

	/**
	 * @param bool $end
	 * @return string
	 * @throws PlinthException
	 */
	public function get($end = true)
	{
		$insert = $this->getInsert() . " " . $this->getTable() . $this->getData();

		if ($this->hasUpdateData()) {
			$insert .= $this->getOnDuplicateKeyUpdate() . $this->getUpdateData();
		}

		return $insert . $this->getEnd($end);
	}
}