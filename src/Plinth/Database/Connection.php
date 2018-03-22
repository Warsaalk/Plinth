<?php
namespace Plinth\Database;

use \PDO;
use Plinth\Exception\PlinthException;

class Connection
{
	const 	FETCH = 1,
			FETCH_ALL = 2,
			EXECUTE = 3;			

	CONST	DEFAULT_TYPE = 'mysql',
			DEFAULT_HOST = '127.0.0.1',
			DEFAULT_USER = 'root',
			DEFAULT_PASS = '',
			DEFAULT_PORT = '3306',
			DEFAULT_CHARSET = 'utf8';

	/**
	 * @var PDO
	 */
	private $connection;
	
	/**
	 * @var boolean
	 */
	private $userTransaction = false;

	/**
	 * @param array $connectionData
	 * @return Connection
	 * @throws PlinthException
	 */
	public static function initializeFromArray($connectionData)
	{
		$defaultConnectionData = [
			'type' => self::DEFAULT_TYPE,
			'host' => self::DEFAULT_HOST,
			'user' => self::DEFAULT_USER,
			'pass' => self::DEFAULT_PASS,
			'port' => self::DEFAULT_PORT,
			'charset' => self::DEFAULT_CHARSET
		];

		$connectionData = array_merge($defaultConnectionData, $connectionData);

		if (!isset($connectionData['db'])) throw new PlinthException("You must defined a database name when using databases.");

		// Legacy override
		if (isset($connectionData['name'])) {
			$connectionData['user'] = $connectionData['name'];
			unset($connectionData['name']);
		}

		return new self(
			$connectionData['type'],
			$connectionData['db'],
			$connectionData['host'],
			$connectionData['user'],
			$connectionData['pass'],
			$connectionData['charset'],
			$connectionData['port']
		);
	}

	/**
	 * @param string $type
	 * @param string $db
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 * @param string $charset
	 * @param string $port
	 * @throws PlinthException
	 */
	public function __construct($type, $db, $host, $user, $pass, $charset = self::DEFAULT_CHARSET, $port = self::DEFAULT_PORT)
	{
		try {
			$this->connection = new PDO($type . ":dbname=".$db.";host=".$host.";port=".$port.";charset=".$charset, $user, $pass);
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(\PDOException $e) {
			throw new PlinthException('No connection or connection limit reached, please contact admin.');
		}
	}

	/**
	 * @param string $query
	 * @param array $array
	 * @param integer $action
	 * @param boolean|string $class
	 * @param array $const_args
	 * @return mixed|boolean
	 * @throws \PDOException
	 * @throws PlinthException
	 */
	public function exec($query, $array = [], $action = self::EXECUTE, $class = false, array $const_args = [])
	{
		if ($this->connection !== NULL)	{
			if ($action === self::FETCH) {
				return $this->fetch($query, $array, $class, $const_args);
			} elseif ($action === self::FETCH_ALL) {
				return $this->fetchAll($query, $array, $class, $const_args);
			} else {
				return $this->execute($query, $array);
			}
		} else {
			throw new PlinthException("No connection found.");
		}
	}
	
	/**
	 * @param \PDOStatement $result
	 * @param array $data
	 */
	private function bind($result, $data)
	{
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
	 * @param array $ctor_args
	 * @return mixed
	 * @throws \PDOException
	 */
	private function fetch($query, $data, $class, array $ctor_args = [])
	{
		try {
			$result = $this->connection->prepare($query);
			$this->bind($result, $data);
			$result->execute();
			if ($class === false)		return $result->fetch(PDO::FETCH_ASSOC);
			elseif (empty($ctor_args))	return $result->fetchObject($class);
			else 						return $result->fetchObject($class, $ctor_args);
		} catch(\PDOException $e) {
			throw $e;
		}
	}
	
	/**
	 * @param string $query
	 * @param array $data
	 * @param string $class
	 * @param array $ctor_args
	 * @return array
	 * @throws \PDOException
	 */
	private function fetchAll($query, $data, $class, array $ctor_args = [])
	{
		try {
			$result = $this->connection->prepare($query);
			$this->bind($result, $data);
			$result->execute();
			if ($class === false)		return $result->fetchAll(PDO::FETCH_ASSOC);
			elseif (empty($ctor_args))	return $result->fetchAll(PDO::FETCH_CLASS, $class);
			else						return $result->fetchAll(PDO::FETCH_CLASS, $class, $ctor_args);
		} catch(\PDOException $e) {
			throw $e;
		}
	}
	
	/**
	 * @param string $query
	 * @param array $data
	 * @return boolean
	 * @throws \PDOException
	 */
	private function execute($query, $data)
	{
		try {
			$result = $this->connection->prepare($query);
			$this->bind($result, $data);
			$return = $result->execute();
			
			return $return;
		} catch(\PDOException $e) {
			throw $e;
		}
	}
	
	/**
	 * @param string $name
	 * @return string
	 */
	public function getLastInsertId($name = NULL)
	{
		return $this->connection->lastInsertId($name);
	}

	/**
	 * Start a transaction on the PDO connection
	 */
	public function beginTransaction()
	{
		$this->userTransaction = true; //Activate transaction
		$this->connection->beginTransaction(); //Begin PDO transaction
	}

	/**
	 * Commit the started transaction on the PDO connection
	 */
	public function commitTransaction()
	{
		if ($this->userTransaction) {
			$this->connection->commit(); //Commit PDO transaction
			$this->userTransaction = false; //Deactivate transaction
		}
	}

	/**
	 * Rollback the started transaction on the PDO connection
	 */
	public function rollBackTransaction()
	{
		if ($this->userTransaction) {
			$this->connection->rollBack(); //Rollback PDO transaction
			$this->userTransaction = false; //Deactivate transaction
		}
	}

	/**
	 * Close the database connection
	 */
	public function close()
	{
		$this->connection = null;
		unset($this->connection);
	}

	/**
	 * Destroy the class
	 */
	function __destruct()
	{
		$this->close();
	}
}