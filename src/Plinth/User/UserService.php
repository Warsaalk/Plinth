<?php 

namespace Plinth\User;

use App\Entity\User\User;
use Plinth\Database\Query\Where_Query;
use Plinth\Database\Query\Select_Query;
use PDO;
use Plinth\Database\Connection;
use Plinth\Connector;
use Plinth\Validation\Validator;

//TODO:: Implement
class UserService extends Connector {

	/**
	 * @var User
	 */
	private $_user = NULL;
	
	/**
	 * @var boolean
	 */
	private $_validSession = false;
	
	/**
	 * @return User
	 */
	public function getUser() {
		 
		return $this->_user;
	
	}
	
	/**
	 * @return boolean
	 */
	public function hasUser() {
		 
		return $this->_user !== NULL;
	
	}
	
	/**
	 * @return boolean
	 */
	public function isSessionValid() {
		 
		return $this->_validSession;
		 
	}
	
	/**
	 * @param string $name
	 * @return array
	 */
	public function getLogin($login) {
		    
		//TODO:: Make this general and defineable
	    $data 	= array(array(':login', $login, PDO::PARAM_STR));
	
	    $query	= (new Select_Query($this->Main()->config->get('mysql:table:user')))
	    	->select('id')
	    	->select('password')
	    	->select('authlevel')
	    	->where('name', Where_Query::OPERATOR_EQUAL, ':login', Where_Query::WHERE_OR)
	    	->where('email', Where_Query::OPERATOR_EQUAL, ':login', Where_Query::WHERE_OR);
	    	    
	    return $this->Main()->getConnection()->exec($query->get(), $data, Connection::FETCH);
	    
	}
	
	/**
	 * @param string $name
	 * @param string $pass
	 * @return boolean
	 */
	public function login($name, $pass) {
		    
	    $id = $this->checkCreds($name, $pass);
		
		if($id !== false) {
		
			session_regenerate_id(true); //Always generate new id on login
			
			$new_session = $this->encrypt_session();
			
			//$this->Main()->getEntityManager()->updateSession($id, $new_session);
			
			$_SESSION['user_id'] = $id;
			$_SESSION['generated'] = time();
			
			return true;
			
		}
		
		return false;
		
	}

	/**
	 * @param string $name
	 * @param string $pass
	 * @return integer|boolean
	 */
	private function checkCreds($name, $pass) {

	    $passsalt = $this->Main()->config->get('keys:passsalt');
	    
		$user = $this->getLogin($name);
		
		if ($user && $user['authlevel'] != 0) { //Authlevel 0 is banned/disabled
		
			$password = crypt($pass, $passsalt);
			if (strcmp($password, $user['password']) === 0) {
			
				return $user['id'];
				
			}
			
		}
		
		return false;
	}
	
	public static function randomPassword( $length = 30, $prefix = '' ) {
	
		$dict 	= "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/.";
		$str 	= $prefix;

		while (strlen($str) < $length-1) {
			$str .= $dict{rand(0, strlen($dict)-1)};
		}

		return $str;
		
    }
		
	
    
    /**
     * @return boolean
     */
	public function verifySession(){

		if (isset($_SESSION['user_id'])) {

			$repo = $this->Main()->getEntityRepository()->getRepository(User::class);
			
			$this->_user = $repo->find($_SESSION['user_id']);
				
			if ($this->_user) {
				
				return $this->_validSession = true; /*
				$current_session 	= $this->_user->getSession();
				$check_session 		= $this->encrypt_session();

				if (strcmp($current_session, $check_session) === 0) { 
					
					//Regenerate session id after a while
					if (time() > $_SESSION['generated'] + $this->Main()->getSetting('sessionregenerate')) {
						session_regenerate_id(); //Don't use true as it'll delete the old session
						$new_session = $this->encrypt_session();
						$this->Main()->getEntityManager()->updateSession($this->_user->getId(), $new_session);
						$_SESSION['generated'] = time();
					}
					
					return $this->_validSession = true;
						
				} else $this->logout();*/
			
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
		
		$this->_user = NULL;
		
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