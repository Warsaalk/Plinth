<?php 

namespace Plinth\User;

use Plinth\Entity\EntityRepository;
use Plinth\Exception\PlinthException;

abstract class UserRepository extends EntityRepository {

	/**
	 * This function should return a User for $ID or NULL if no user was found
	 * 
	 * @param mixed $ID
	 * @return User|NULL
	 */
	public abstract function find($ID);
	
	/**
	 * This function should be used to lookup a user who matches with the send $login or false on failure
	 * Used when calling UserService::login
	 * Example 1: The $login could be a email address which the user needs to give up to login.
	 * 
	 * @param mixed $login
	 * @return User|boolean
	 */
	public function findUserWithLogin($login) {
		
		throw new PlinthException('Please implement the findUserWithLogin method in your UserRepository');
		
	}
	
	/**
	 * This function should be used to lookup a user who matches with the send $token or false on failure
	 * Used when calling UserService::loginWithToken
	 * Example 1: The $token could be a single code which the user needs to give up to login.
	 * 
	 * @param mixed $token
	 * @return User|boolean
	 */
	public function findUserWithToken($token) {
		
		throw new PlinthException('Please implement the findUserWithToken method in your UserRepository');
		
	}

	/**
	 * This function will be called when you have enabled the userrehash option in you application config.
	 * It will be called when the user password needs to be updated with a stronger algorithm.
	 * http://php.net/manual/en/function.password-needs-rehash.php
	 *
	 * @param $userID
	 * @param $newToken
	 * @throws PlinthException
	 */
	public function updateUserToken($userID, $newToken) {

		throw new PlinthException('Please implement the updateUserToken method in your UserRepository');

	}
	
	/**
	 * @param mixed $ID
	 * @param mixed $session
	 * @return boolean
	 */
	public function updateUserSession($ID, $session) {
		
		throw new PlinthException('Please implement the updateUserSession method in your UserRepository');
		
	}

}