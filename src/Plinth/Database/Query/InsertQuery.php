<?php

namespace Plinth\Database\Query;

class InsertQuery extends BaseQuery {
	
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
	private $_columns = array();
	
	/**
	 * @var array
	 */
	private $_values = array();

	/**
	 * @var SelectQuery
	 */
	private $_selectQuery;
	/**
	 * On first insert set InsertQuery type
	 * Force a user to use only the values in his query, a value-column combination or setColumn with multiple values
	 * 
	 * @param string $useColumn
	 * @throws Exception
	 */
	private function setType ($useType) {
		
		if ($this->_type === NULL) {
		    $this->_type = $useType !== self::VALUES_ONLY && $useType !== self::SELECT_ONLY && $useType !== self::VALUES_MULTIPLE ? self::VALUES_COLUMNS : $useType;
		} else {
		    if ($this->_type === self::SELECT_ONLY) throw new Exception('InsertQuery:: You already used insertSelectQuery, which cannnot be combined with other inserts.');
		        
			if (($this->_type === self::VALUES_COLUMNS && $useType === false)
			&&	($this->_type === self::VALUES_ONLY && $useType !== false))
			throw new Exception('InsertQuery:: Please only use values or a value-column combination for all your inserts.');
		}
		
	}
	
	/**
	 * @param multitype $value
	 * @param string $column
	 */
	public function insert ($value, $column=self::VALUES_ONLY) {
		
		if ($this->_type === self::VALUES_MULTIPLE) {
			
			if (!is_array($value)) throw new Exception('InsertQuery:: Please use an array for the column values');
						
		} else {
		
			if (is_array($value)) throw new Exception('InsertQuery:: Please use setColumns first before you insert multiple values');
			
			$this->setType($column);
		
			if ($this->_type === self::VALUES_COLUMNS)	$this->_columns[] = $column;		
		
		}
		
		$this->_values[] = $value;
		
		return $this;
					
	}
	
	/**
	 * @param SelectQuery $query
	 * @param array $columns
	 */
	public function insertSelectQuery(SelectQuery $query, $columns=array()) {
	    
	    if ($this->_type === NULL) {
	    
    	    $this->setType(self::SELECT_ONLY);
    	    
    	    $this->_columns = $columns;
    	    
    	    $this->_selectQuery = $query;
	    
	    } else throw new Exception('InsertQuery:: insertSelectQuery can only be use once and cannot be combined with other inserts.');
	    
	    return $this;
	    
	}
	
	/**
	 * @param string[] $columns
	 * @throws Exception
	 * @return InsertQuery
	 */
	public function setColumns($columns=array()) {
		
		if ($this->_type === NULL) {
	    
    	    $this->setType(self::VALUES_MULTIPLE);
    	    
    	    $this->_columns = $columns;
	    
	    } else throw new Exception('InsertQuery:: setColumns can only be use once and cannot be combined with other inserts.');
	    
	    return $this;
		
	}

	/**
	 * @return boolean
	 */
	private function hasData()	{ return count($this->_values) > 0; }
	
	/**
	 * @throws Exception
	 * @return string
	 */
	private function getData()	{

		$data = false;
		
		if ($this->_type === self::VALUES_COLUMNS) {
			
			if (count($this->_columns) === count($this->_values)) {
				
				$data = " (" . implode(',', $this->_columns) . ") VALUES (" . implode(',', $this->_values) . ")";
				
			} else throw new Exception('InsertQuery:: Your columns and values must match');

		} elseif ($this->_type === self::VALUES_MULTIPLE) {
			
			$columns = count($this->_columns);
			
			$data = " (" . implode(',', $this->_columns) . ") VALUES ";
			$dataValues = array();
			
			if (!$this->hasData()) throw new Exception('InsertQuery:: Please insert values');
						
			foreach ($this->_values as $i => $row) {
				
				if (count($row) === $columns) $dataValues[] = "(" . implode(',', $row) . ")";
				else throw new Exception('InsertQuery:: Your values must match the number of columns');
				
			}
			
			$data .= implode(', ', $dataValues);
			
		} elseif ($this->_type === self::SELECT_ONLY) {
		
		    $data = (count($this->_columns) > 0 ? " (" . implode(',', $this->_columns) . ") " : " ") . $this->_selectQuery->get(false);
		    		    
		} else {
			
			$data = " VALUES (" . implode(',', $this->_values) . ")";
			
		}
		
		return $data !== false ? $data : " () VALUES ()"; 
		 	
	}
	
	/**
	 * @return string
	 */
	private function getInsert() { return "INSERT INTO "; }
	
	/** 
	 * (non-PHPdoc)
	 * @see IQuery::get()
	 */
	public function get($end=true) {

		return  $this->getInsert() . $this->getTable() . $this->getData() . $this->getEnd($end);

	}

}