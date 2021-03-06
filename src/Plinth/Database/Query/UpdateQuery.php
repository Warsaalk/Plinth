<?php

namespace Plinth\Database\Query;

use Plinth\Exception\PlinthException;

class UpdateQuery extends WhereQuery
{
	/**
	 * @var array
	 */
	private $_columns = [];
	
	/**
	 * @var array
	 */
	private $_values = [];

	/**
	 * @param $value
	 * @param $column
	 * @return $this
	 */
	public function update($value, $column)
	{
		$this->_columns[] 	= $column;
		$this->_values[] 	= $value;		
		
		return $this;
	}

	/**
	 * @return boolean
	 */
	private function hasData()
	{
		return count($this->_values) > 0;
	}

	/**
	 * @return string
	 * @throws PlinthException
	 */
	private function getData()
	{
		if (count($this->_columns) === count($this->_values))
			$data = implode(',', array_map(function($c,$v) { return "$c=$v"; }, $this->_columns, $this->_values));
		else
			throw new PlinthException('Your columns and values must match');

		return $data !== false ? $data : "";
	}

	/**
	 * @return string
	 */
	private function getUpdate()
	{
		return "UPDATE ";
	}

	/**
	 * @param bool $end
	 * @return string
	 * @throws PlinthException
	 */
	public function get($end = true)
	{
		$return = $this->getUpdate() . " " . $this->getTable();
								
		if ($this->hasAs()) $return .= $this->getAs();
		
		$return .= " SET " . $this->getData();
		
		if ($this->hasIndex()) 	$return .= $this->getIndex();
		if ($this->hasWhere()) 	$return .= $this->getWhere();		
		if ($this->hasLimit()) 	$return .= $this->getLimit();
		
		return $return . $this->getEnd($end);
	}
}