<?php

namespace Plinth\Database\Query;

class DeleteQuery extends WhereQuery {

	/**
	 * @var array
	 */
	private $tablesToDelete = array();

	/**
	 * @return boolean
	 */
	protected function hasTablesToDelete()
	{
		return count($this->tablesToDelete) > 0;
	}

	/**
	 * Used when selecting multiple tables like joins
	 *
	 * @param $table
	 * @return $this
	 */
	public function delete($table)
	{
		if (in_array($table, $this->tablesToDelete) === false) {
			$this->tablesToDelete[] = $table;
		}

		return $this;
	}

	/**
	 * @return string
	 */
	private function getDelete()
	{
		return "DELETE";
	}

	/**
	 * @return string
	 */
	private function getTablesToDelete()
	{
		return " " . implode(', ', $this->tablesToDelete);
	}
	
	/**
	 * @return string
	 */
	private function getFrom()
	{
		return " FROM " . $this->getTable();
	}

	/**
	 * (non-PHPdoc)
	 * @see IQuery::get()
	 */
	public function get($end=true)
	{
		$return = $this->getDelete();

		if ($this->hasTablesToDelete())	$return .= $this->getTablesToDelete();

		$return .= $this->getFrom();
			
		if ($this->hasAs()) 	$return .= $this->getAs();
		if ($this->hasWhere()) 	$return .= $this->getWhere();
		if ($this->hasLimit()) 	$return .= $this->getLimit();
			
		return  $return . $this->getEnd($end);
	}
}