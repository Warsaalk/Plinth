<?php 

namespace Plinth\User;

use Plinth\Exception\PlinthException;
abstract class User {

	/**
	 * @return mixed;
	 */
	public abstract function getID();

	/**
	 * @return mixed
	 */
	public abstract function getToken();
	
	/**
	 * @return mixed|boolean
	 */
	public function getLogin() {
		
		return false;
		
	}
	
	/**
	 * @return boolean
	 */
	public function canLogin() {
		
		return true;
		
	}
	
	/**
	 * @return mixed|boolean
	 */
	public function getRole() {
		
		return false;
		
	}
	
	/**
	 * @return mixed|boolean
	 */
	public function getSession() {
		
		throw new PlinthException('Please implement the getSession method in your User class');
		
	}
	
	/**
	 * @param mixed $session
	 * @return boolean
	 */
	public function setSession($session) {
		
		throw new PlinthException('Please implement the setSession method in your User class');
		
	}

}