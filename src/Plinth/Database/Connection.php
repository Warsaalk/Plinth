<?php
/**
 * @package PHP-Collection
 * @license http://opensource.org/licenses/mit-license.php
 * @author Klaas Van Parys <https://github.com/Warsaalk>
 */

namespace Plinth\Database;

use \PDO;

use Plinth\Common\Info;

class Connection {

	const 	FETCH = 1,
			FETCH_ALL = 2,
			EXECUTE = 3;			
	
	/**
	 * @var PDO
	 */
	private $connection;
	
	/**
	 * @var boolean
	 */
	private $userTransaction = false;

	/**
	 * @param string $type
	 * @param string $db
	 * @param string $host
	 * @param string $name
	 * @param string $pass
	 */
	public function __construct($type, $db, $host, $name, $pass, $charset="utf8", $port="3306") {
		global $info;
		try {
		
			$this->connection = new PDO($type . ":dbname=".$db.";host=".$host.";port=".$port.";charset=".$charset, $name, $pass);
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				
		} catch(PDOException $e) {
		
			if( isset( $info ) && class_exists("Info") ) 	$info 			= new Info( 'No connection or connection limit reached, please contact admin', Info::ERROR );
				
		}
		
	}

	/**
	 * @param string $query
	 * @param array $array
	 * @param integer $action
	 * @param boolean|string $class
	 * @param array $const_args
	 * @return Ambiguous|mixed|multitype:|boolean
	 */
	public function exec($query, $array=array(), $action=self::EXECUTE, $class=false, array $const_args=null) {
			
		if ($this->connection != NULL)	{
	
			if ($action === self::FETCH) {
			
				return $this->fetch($query, $array, $class, $const_args);
			
			} elseif ($action === self::FETCH_ALL) {
			
				return $this->fetchAll($query, $array, $class, $const_args);
			
			} elseif ($action === self::EXECUTE) {
			
				return $this->execute($query, $array);
			
			}
			
		}
		
	}
	
	/**
	 * @param PDOStatement $result
	 * @param array $data
	 */
	private function bind($result, $data) {

		foreach ($data as $i => &$row) {
		
			if (is_array($row)) {
			
				$c = count($row);
				
				if ($c == 2) 		{ $result->bindParam($row[0], $row[1]); 					      }
				elseif ($c == 3) 	{ $result->bindParam($row[0], $row[1], $row[2]); 				  }
				elseif ($c == 4) 	{ $result->bindParam($row[0], $row[1], $row[2], $row[3]); 		  }
				elseif ($c == 5) 	{ $result->bindParam($row[0], $row[1], $row[2], $row[3], $row[4]);}
			
			} else {
						
				$result->bindParam($i, $row);
					
			}
		
		}

	}
	
	/**
	 * @param string $query
	 * @param array $data
	 * @param string $class
	 * @throws ConnectionException
	 * @return mixed
	 */
	private function fetch($query, $data, $class, $const_args) {
			
		try {
		
			$result = $this->connection->prepare($query);
			$this->bind($result, $data);
			$result->execute();
			if ($class === false)			return $result->fetch(PDO::FETCH_ASSOC);
			elseif ($const_args === null)	return $result->fetchObject($class);
			else 							return $result->fetchObject($class, $const_args);

		} catch(PDOException $e) {
		
			throw $e;
			
		}
	
	}
	
	/**
	 * @param string $query
	 * @param array $data
	 * @param string $class
	 * @throws ConnectionException
	 * @return multitype:
	 */
	private function fetchAll($query, $data, $class, $const_args) {
	
		try {
		
			$result = $this->connection->prepare($query);
			$this->bind($result, $data);
			$result->execute();
			if ($class === false)	return $result->fetchAll(PDO::FETCH_ASSOC);
			else					return $result->fetchAll(PDO::FETCH_CLASS, $class, $const_args);
		
		} catch(PDOException $e) {
		
			throw $e;
			
		}
			
	}
	
	/**
	 * @param string $query
	 * @param array $data
	 * @throws ConnectionException
	 * @return boolean
	 */
	private function execute($query, $data) {
	
		try {
		
			//if(!$this->userTransaction) $this->connection->beginTransaction();
			
			$result = $this->connection->prepare($query);
			$this->bind($result, $data);
			$return = $result->execute();
			
			//if (!$this->userTransaction) $this->connection->commit();
			
			return $return;
		
		} catch(PDOException $e) {
				
			//if (!$this->userTransaction) $this->connection->rollBack();
			throw $e;
			
		}
	
	}
	
	/**
	 * @param string $name
	 * @return string
	 */
	public function getLastInsertId($name = NULL) {
		
		return $this->connection->lastInsertId($name);
		
	}
	
	public function beginTransaction() {
	
		$this->userTransaction = true; //Activate transaction
		$this->connection->beginTransaction(); //Begin PDO transaction
	
	}
	
	public function commitTransaction() {
	
		if ($this->userTransaction) {

			$this->connection->commit(); //Commit PDO transaction
			$this->userTransaction = false; //Deactivate transaction
				
		}
	
	}
	
	public function rollBackTransaction() {
	
		if ($this->userTransaction) {
		
			$this->connection->rollBack(); //Rollback PDO transaction
			$this->userTransaction = false; //Deactivate transaction
				
		}
	
	}

	public function close() {
	    
		$this->connection = null;
		unset($this->connection);
		
	}
	
	function __destruct() {
	    
		$this->close();
			
	}
	
}