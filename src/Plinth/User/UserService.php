<?php 

namespace Plinth\User;

use Plinth\Database\Connection;
use Plinth\Connector;
use Plinth\Exception\PlinthException;
use Plinth\Validation\Validator;
use Plinth\Common\Debug;

class UserService extends Connector
{
	/**
	 * @var User
	 */
	private $user = NULL;
	
	/**
	 * @var UserRepository
	 */
	private $userrepository;
	
	/**
	 * @var boolean
	 */
	private $validSession = false;

	/**
	 * @var boolean
	 */
	private $loggedOut = false;
	
	/**
	 * @return User
	 */
	public function getUser()
	{
		return $this->user;
	}
	
	/**
	 * @return boolean
	 */
	public function hasUser()
	{
		return $this->user !== NULL;
	}
	
	/**
	 * @param UserRepository $userrepository
	 */
	public function setUserRepository(UserRepository $userrepository)
	{
		$this->userrepository = $userrepository;
	}
	
	/**
	 * @return boolean
	 */
	public function isSessionValid()
	{
		return $this->validSession;
	}
	
	/**
	 * @return boolean
	 */
	public function isLoggedOut()
	{
		return $this->loggedOut;
	}

	/**
	 * This function will used the recommended way in PHP of hashing passwords.
	 * The length of the hashed token may change over time as PHP may release newer versions with better and stronger algorithms.
	 * Therefore it is recommended to use a width of 255 characters.
	 * http://php.net/manual/en/function.password-hash.php
	 *
	 * @param $token
	 * @return string
	 */
	public function getHashForToken($token)
	{
		return password_hash($token, PASSWORD_DEFAULT);
	}

	/**
	 * @param $token
	 * @param callable $validationCallback
	 * @return bool
	 * @throws PlinthException
	 */
	public function loginWithToken($token, $validationCallback = NULL)
	{
		return $this->login(NULL, $token, $validationCallback);
	}

	/**
	 * @param $login
	 * @param $token
	 * @param callable $validationCallback
	 * @return bool
	 * @throws PlinthException
	 */
	public function login($login, $token, $validationCallback = NULL)
	{
	    $this->user = $this->checkCreds($login, $token);
		if($this->user !== NULL) {
			if ($validationCallback === NULL || $validationCallback($this->user)) {
				session_regenerate_id(true); //Always generate new id on login
								
				if ($this->Main()->getSetting('usersession')) {
					$session = $this->encrypt_session();
					$this->user->setSession($session);
					$this->userrepository->updateUserSession($this->user->getID(), $session);
				}
					
				$_SESSION['plinth_user_id'] = $this->user->getID();
				$_SESSION['plinth_user_generated'] = time();
				
				return true;
			}
		}
		return false;
	}

	/**
	 * @param $login
	 * @param $token
	 * @return bool|null|User
	 * @throws PlinthException
	 */
	private function checkCreds($login, $token)
	{
	    if ($login === NULL) {
			$tokensalt = $this->Main()->config->get('keys:tokensalt');
			if ($tokensalt !== false) {
				$usertoken = crypt($token, $tokensalt);
				$userlogin = $this->userrepository->findUserWithToken($usertoken);

				if ($userlogin && $userlogin->canLogin()) {
					$userlogin->clearToken();
					return $userlogin;
				}
			} else {
				throw new PlinthException('Please define a token salt in your config if you want to use the token only authentication.');
			}
		} else {
	    	$userlogin = $this->userrepository->findUserWithLogin($login);
			if ($userlogin && $userlogin->canLogin()) {
				if (password_verify($token, $userlogin->getToken())) {
					if ($this->Main()->getSetting('userrehash') === true && password_needs_rehash($userlogin->getToken(), PASSWORD_DEFAULT)) {
						$this->userrepository->updateUserToken($userlogin->getID(), $this->getHashForToken($token));
					}

					$userlogin->clearToken();

					return $userlogin;
				}
			}
		}
		return NULL;
	}

	/**
	 * @return bool
	 * @throws PlinthException
	 */
	public function verifySession()
	{
		if (isset($_SESSION['plinth_user_id'])) {
			if ($this->user === NULL) {
				$this->user = $this->userrepository->find($_SESSION['plinth_user_id']);
			}

			if ($this->user !== NULL) {
				//If application uses usersessions
				if ($this->Main()->getSetting('usersession')) {
					$current_session = $this->user->getSession();
					
					//Check if User::getSession is implemented
					if ($current_session !== false) {
						$check_session = $this->hashSession();
						
						//Compare current session against the session generator
						if (strcmp($current_session, $check_session) === 0) {
								
							//Regenerate session id after a while
							if ($this->Main()->getSetting('sessionregenerate') !== false) {
								$now = time();
								if ($now > $_SESSION['plinth_user_generated'] + $this->Main()->getSetting('sessionregenerate')) {
									session_regenerate_id(); //Don't use true as it'll delete the old session
									$this->userrepository->updateUserSession($this->user->getID(), $this->hashSession());
									$_SESSION['plinth_user_generated'] = $now;
								}
							}
								
							return $this->validSession = true;
						} else {
							$this->logout();
						}
					}				
				} else {
					return $this->validSession = true;
				}			
			}
		}
		return false;
	}
	
	/**
	 * @return string
	 */
	private function hashSession()
	{
		return $this->getHashForToken(session_id() . "." . $_SERVER['REMOTE_ADDR'] . "." . $_SERVER['HTTP_USER_AGENT']);
	}
	
	/**
	 * @return boolean
	 */
	public function logout()
	{
		$this->user = NULL;
		$this->loggedOut = true;
		
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
		
		//Destory session
		session_unset();
		session_destroy();
		//Start new session 
		session_start();
		//Regenerate session id, else the session will continue with the old id
		session_regenerate_id(true); 
		
		return true;
	}
}