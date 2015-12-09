<?php 

namespace Plinth\User;

use Plinth\Database\Connection;
use Plinth\Connector;
use Plinth\Validation\Validator;
use Plinth\Common\Debug;

class UserService extends Connector {

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
	 * @return User
	 */
	public function getUser() {
		 
		return $this->user;
	
	}
	
	/**
	 * @return boolean
	 */
	public function hasUser() {
		 
		return $this->user !== NULL;
	
	}
	
	/**
	 * @param UserRepository $userrepository
	 */
	public function setUserRepository(UserRepository $userrepository) {
		
		$this->userrepository = $userrepository;
		
	}
	
	/**
	 * @return boolean
	 */
	public function isSessionValid() {
		 
		return $this->validSession;
		 
	}
	
	/**
	 * @param string $token
	 * @param function $validationCallback
	 * @return boolean
	 */
	public function loginWithToken($token, $validationCallback = NULL) {
		
		return $this->login(NULL, $token, $validationCallback);
		
	}
	
	/**
	 * @param string $login
	 * @param string $token
	 * @param function $validationCallback
	 * @return boolean
	 */
	public function login($login, $token, $validationCallback = NULL) {
		    
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
	 * @param string $login
	 * @param string $token
	 * @return User|boolean
	 */
	private function checkCreds($login, $token) {

	    $salt = $this->Main()->config->get('keys:tokensalt');
	    
	    if ($login === NULL) {
	    	
	    	$usertoken = crypt($token, $salt);
	    	$userlogin = $this->userrepository->findUserWithToken($usertoken);
	    	
	    	if ($userlogin && $userlogin->canLogin()) return $userlogin;
	    	
		} else {
	    	
	    	$userlogin = $this->userrepository->findUserWithLogin($login);

			if ($userlogin && $userlogin->canLogin()) {
			
				$usertoken = crypt($token, $salt);
			
				if (strcmp($usertoken, $userlogin->getToken()) === 0) {
	
					return $userlogin;
					
				}
				
			}
			
		}
		
		return NULL;
		
	}	
    
    /**
     * @return boolean
     */
	public function verifySession(){

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
						
						$check_session = $this->encrypt_session();
						
						//Compare current session against the session generator
						if (strcmp($current_session, $check_session) === 0) {
								
							//Regenerate session id after a while
							if ($this->Main()->getSetting('sessionregenerate') !== false) {
								$now = time();
								if ($now > $_SESSION['plinth_user_generated'] + $this->Main()->getSetting('sessionregenerate')) {
									session_regenerate_id(); //Don't use true as it'll delete the old session
									$this->userrepository->updateUserSession($this->user->getID(), $this->encrypt_session());
									$_SESSION['plinth_user_generated'] = $now;
								}
							}
								
							return $this->validSession = true;
						
						} else $this->logout();
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
	private function encrypt_session(){
	
		$mcryptkey   = self::Main()->config->get('keys:mcrypt');
		$ivsalt      = self::Main()->config->get('keys:ivsalt');
		
		$string      = session_id() . "." . $_SERVER['REMOTE_ADDR'] . "." . $_SERVER['HTTP_USER_AGENT'];
				
		$mcryptkey   = substr($mcryptkey, -24); //algorithm recuires key with length 24
		$crypttext   = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $mcryptkey, $string, MCRYPT_MODE_CBC, $ivsalt);
		
		unset($mcryptkey, $ivsalt);
		
		return base64_encode($crypttext);
			
	}
	
	/**
	 * @return boolean
	 */
	public function logout(){
		
		$this->user = NULL;
		
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