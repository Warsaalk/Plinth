<?php

namespace Plinth\Database\Query;

class SelectQuery extends WhereQuery implements OrderByQuery {

	/**
	 * @var array
	 */
	private $select;
	
	/**
	 * @var array
	 */
	private $groupBy;
	
	/**
	 * @var array
	 */
	private $having;
	
	/**
	 * @var array
	 */
	private $orderby;
	
	/**
	 * @param string|SelectQuery $table
	 * @param string $as
	 */
	public function __construct($table, $as=false) {
	
		parent::__construct($table, $as);
	
		$this->select = array();
		$this->orderby= array();		
		
		return $this;
		
	}
	
	/**
	 * @param string $select
	 * @param string $selectAs
	 */
	public function select ($select, $selectAs=false) {
		
		if ($selectAs !== false) $select .= ' AS ' . $selectAs;

		$this->select[] = $select;		
		
		return $this;
		
	}
	
	/**
	 * @param string $column
	 */
	public function groupBy($column) {
		
		$this->groupBy[] = $column;		
		
		return $this;
		
	}
	
	/**
	 * @param string $statement
	 */
	public function having ($statement) {
		
		$this->having[] = $statement;		
		
		return $this;
		
	}
	
     /**
	 * (non-PHPdoc)
	 * @see OrderByQuery::orderBy()
	 */
	public function orderBy($column, $order=self::ORDER_DEFAULT) {
			
		if ($order === self::ORDER_DESC)	$column .= " DESC";
		elseif ($order === self::ORDER_ASC)	$column .= " ASC";
		
		$this->orderby[] = $column;		
		
		return $this;

	}
	
	/**
	 * @return boolean
	 */
	private function hasSelect()	{ return count($this->select) > 0; 	}

	/**
	 * @return boolean
	 */
	protected function hasGroupBy()	{ return count($this->groupBy) > 0; }

	/**
	 * @return boolean
	 */
	protected function hasHaving()	{ return count($this->having) > 0; 	}
	
	/**
	 * @return boolean
	 */
	private function hasOrderBy()	{ return count($this->orderby) > 0;	}
	
	/**
	 * @return string
	 */
	private function getSelect()	{ return "SELECT " . implode(',', $this->select);		}

	/**
	 * @return string
	 */
	protected function getGroupBy()	{ return  " GROUP BY " . implode(',', $this->groupBy);	}

	/**
	 * @return string
	 */
	protected function getHaving()	{ return  " HAVING " . implode(' AND', $this->having);	}
	
	/**
	 * @return string
	 */
	private function getOrderBy() 	{ return " ORDER BY " . implode(',', $this->orderby); 	}
	
	/**
	 * @return string
	 */
	private function getFrom()		{ return " FROM" . $this->getTable();					}

	/**
	 * Allow the table to be a SelectQuery
	 *
	 * @return string
	 */
	protected function getTable()
	{
		$table = parent::getTable();

		if ($table instanceof SelectQuery) return "(" . $table->get() . ")";

		return $table;
	}

	/** 
	 * (non-PHPdoc)
	 * @see IQuery::get()
	 */
	public function get($end=true) {
	
		$return = $this->getSelect() . $this->getFrom();
						
		if ($this->hasAs())       $return .= $this->getAs();
		if ($this->hasJoins())    $return .= $this->getJoins();		
		if ($this->hasIndex())    $return .= $this->getIndex();
		if ($this->hasWhere())    $return .= $this->getWhere();		
		if ($this->hasGroupBy())  $return .= $this->getGroupBy();
		if ($this->hasHaving())   $return .= $this->getHaving();
		if ($this->hasOrderBy())  $return .= $this->getOrderBy();
		if ($this->hasLimit()) 	  $return .= $this->getLimit();
		
		return  $return . $this->getEnd($end);
	
	}

}