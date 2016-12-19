<?php

namespace Plinth\Database\Query;

class JoinQuery implements IQuery {

		/* Join types */
		const JOIN 				= " JOIN";
		const JOIN_FROM 		= ", ";
		const JOIN_INNER 		= " INNER JOIN";
		const JOIN_CROSS 		= " CROSS JOIN";
		const JOIN_LEFT 		= " LEFT JOIN";
		const JOIN_LEFT_OUTER 	= " LEFT OUTER JOIN";
		const JOIN_RIGHT 		= " RIGHT JOIN";
		const JOIN_RIGHT_OUTER 	= " RIGHT OUTER JOIN";
		
		/**
		 * @var string|SelectQuery
		 */
		private $tojoin;
		
		/**
		 * @var string
		 */
		private $type;
		
		/**
		 * @var string
		 */
		private $condition;
		
		/**
		 * @var string
		 */
		private $joinAs;
		
		/**
		 * @param SelectQuery|string $tojoin
		 * @param string $type
		 * @param string $condition
		 * @param string $as
		 */
		public function __construct($tojoin, $type=self::JOIN, $condition=false, $as=false)
		{
			if ($type === self::JOIN_FROM && $condition !== false) $condition = false; // The from "join" can't have a condition
		
			$this->tojoin = $tojoin;
			$this->type = $type;
			$this->condition = $condition;
			$this->joinAs = $as;
		}
		
		/**
		 * @return boolean
		 */
		private function hasJoinAs()	{ return $this->joinAs !== false; 		}
		
		/**
		 * @return boolean
		 */
		private function hasCondition()	{ return $this->condition !== false;	}
		
		/**
		 * @return string
		 */
		private function getToJoin() 	{
			
			if ($this->tojoin instanceof SelectQuery ||
			    $this->tojoin instanceof UnionQuery)	      return " (" . $this->tojoin->get(false) . ")";
			else										      return " " . $this->tojoin;
		
		}
		
		/**
		 * @return string
		 */
		private function getType()		{ return $this->type; 						}
		
		/**
		 * @return string
		 */
		private function getJoinAs() 	{ return " AS " . $this->joinAs; 			}
		
		/**
		 * @return string
		 */
		private function getCondition()	{ return " ON (" . $this->condition . ")"; 	}
		
		/** 
		 * (non-PHPdoc)
		 * @see IQuery::get()
		 */
		public function get() {
		
			$query = $this->getType() . $this->getToJoin();
			
			if ($this->hasJoinAs())	     $query .= $this->getJoinAs();
			if ($this->hasCondition())   $query .= $this->getCondition();
			
			return $query;
		
		}

}