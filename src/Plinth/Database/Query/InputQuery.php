<?php

namespace Plinth\Database\Query;

use Plinth\Exception\PlinthException;

abstract class InputQuery extends BaseQuery
{
	const VALUES_COLUMNS = 0;
	const VALUES_ONLY = 1;
	const SELECT_ONLY = 2;
	const VALUES_MULTIPLE = 3;

	/**
	 * @var integer
	 */
	private $_type;
	
	/**
	 * @var array
	 */
	private $_columns = [];
	
	/**
	 * @var array
	 */
	private $_values = [];

	/**
	 * @var SelectQuery
	 */
	private $_selectQuery;

	/**
	 * @param $useType
	 * @return $this
	 * @throws PlinthException
	 */
	private function setType ($useType)
	{
		if ($this->_type === NULL) {
		    $this->_type = $useType !== self::VALUES_ONLY && $useType !== self::SELECT_ONLY && $useType !== self::VALUES_MULTIPLE ? self::VALUES_COLUMNS : $useType;
		} else {
		    if ($this->_type === self::SELECT_ONLY) throw new PlinthException('You already used insertSelectQuery/replaceSelectQuery, which cannnot be combined with other inserts/replaces.');
		        
			if (($this->_type === self::VALUES_COLUMNS && $useType === false)
			&&	($this->_type === self::VALUES_ONLY && $useType !== false))
			throw new PlinthException('Please only use values or a value-column combination for all your inserts/replaces.');
		}

		return $this;
	}

	/**
	 * @param $value
	 * @param int $column
	 * @return ReplaceQuery|InsertQuery
	 * @throws PlinthException
	 */
	protected function input ($value, $column = self::VALUES_ONLY)
	{
		if ($this->_type === self::VALUES_MULTIPLE) {
			if (!is_array($value)) throw new PlinthException('Please use an array for the column values');
		} else {
			if (is_array($value)) throw new PlinthException('Please use setColumns first before you insert multiple values');
			
			$this->setType($column);
		
			if ($this->_type === self::VALUES_COLUMNS)	$this->_columns[] = $column;
		}
		$this->_values[] = $value;
		
		return $this;
	}

	/**
	 * @param SelectQuery $query
	 * @param array $columns
	 * @return ReplaceQuery|InsertQuery
	 * @throws PlinthException
	 */
	protected function inputSelectQuery(SelectQuery $query, $columns = [])
	{
	    if ($this->_type === NULL) {
    	    $this->setType(self::SELECT_ONLY);

    	    $this->_columns = $columns;
    	    $this->_selectQuery = $query;
	    } else
	    	throw new PlinthException('insertSelectQuery/replaceSelectQuery can only be use once and cannot be combined with other inserts/replaces.');
	    
	    return $this;
	}
	
	/**
	 * @param string[] $columns
	 * @throws PlinthException
	 * @return InsertQuery|ReplaceQuery
	 */
	public function setColumns($columns = [])
	{
		if ($this->_type === NULL) {
    	    $this->setType(self::VALUES_MULTIPLE);
    	    
    	    $this->_columns = $columns;
	    } else
	    	throw new PlinthException('setColumns can only be use once and cannot be combined with other inserts/replaces.');
	    
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
	 * @throws PlinthException
	 * @return string
	 */
	protected function getData()
	{
		if ($this->_type === self::VALUES_COLUMNS) {
			if (count($this->_columns) === count($this->_values))
				$data = " (" . implode(',', $this->_columns) . ") VALUES (" . implode(',', $this->_values) . ")";
			else
				throw new PlinthException('Your columns and values must match');
		} elseif ($this->_type === self::VALUES_MULTIPLE) {
			$columns = count($this->_columns);
			$data = " (" . implode(',', $this->_columns) . ") VALUES ";
			$dataValues = [];
			
			if (!$this->hasData()) throw new PlinthException('Please insert/replace values');
						
			foreach ($this->_values as $i => $row) {
				if (count($row) === $columns)
					$dataValues[] = "(" . implode(',', $row) . ")";
				else
					throw new PlinthException('Your values must match the number of columns');
			}
			
			$data .= implode(', ', $dataValues);
		} elseif ($this->_type === self::SELECT_ONLY) {
		    $data = (count($this->_columns) > 0 ? " (" . implode(',', $this->_columns) . ") " : " ") . $this->_selectQuery->get(false);
		} else {
			$data = " VALUES (" . implode(',', $this->_values) . ")";
		}
		
		return $data !== false ? $data : " () VALUES ()";
	}
}