<?php
/**
* General file information
*
* @package root\php_model
*/ 

namespace Plinth;

use Plinth\Database\Connection;
use Plinth\Common\Info;
use Plinth\Common\Language;
use Plinth\Request\Request;
use Plinth\Routing\Router;
use Plinth\Validation\Validator;
use Plinth\Store;
use Plinth\Dictionary;
use Plinth\User\UserService;
use Plinth\Common\Debug;
use Plinth\Response\Response;
use Plinth\Entity\EntityRepository;
use Plinth\Logging\KLogger;
use Plinth\Routing\Route;
use Plinth\Exception\PlinthException;

/**
* This is the main class which handles and provides all the data
*
*/
class Main {

	/**
	 * @var string
	 */
    private $_lang;
    
    /**
     * @var Info[]
     */
    private $_info = array();
    
    /**
     * @var UserService
     */
    private	$_userservice;
    
    /**
     * @var Router
     */
    private $_router;

    /**
     * @var EntityRepository
     */
    private $_entityrepository;
    
    /**
     * @var Validator
     */
    private $_validator;
    
    /**
     * @var Request
     */
    private $_request;
    
    /**
     * @var Response
     */
    private $_response;
    
    /**
     * @var Dictionary
     */
    private $_dictionary;
    
    /**
     * @var Store
     */
    private $_store;
    
    /**
     * @var Connection
     */
    private $_connection;
    
    /**
     * @var KLogger
     */
    private $_logger;
    
    /**
     * @var Config
     */
    public $config;
    
    private $_defaultSettings = array(
        'forcelogin' => false,
        'userservice' => false,
    	'usersession' => false,
    	'userclass' => false,
    	'loginpage' => 'page_login',
    	'defaultlocale' => false,
    	'fallbacklocale' => false,
    	'localetype' => 'php',
    	'localeget' => false,
    	'localeaccept' => false,
    	'localecookie' => false,
    	'tokenexpire' => 300,
    	'sessionregenerate' => 300
    );
    
    /**
     * @var array
     */
    public $settings;
    
    /**
     * @var boolean
     */
    private $_devEnvironment;
    
    /**
     * @param boolean $inProduction
     */
    public function __construct($devEnvironment=false) { 
    	
    	$this->loadConfig();
        $this->loadSettings();
        
        if (($timezone = $this->config->get('date:timezone')) !== false) {
        	date_default_timezone_set($timezone);
        }
        
    	$this->_devEnvironment = $devEnvironment;
        
        $this->registerLogger();
                
    	$this->loadDatabase();
                
        $this->initDictionaries($this->config->get('language:locales')?: array(), $this->getSetting('defaultlocale'));
        
        $this->registerClasses();
        $this->executeHandlers();
    			
    }
    
    private function loadConfig() {
    	
    	$configFile = $this->_devEnvironment ? __APP_CONFIG_DEV : __APP_CONFIG_PROD;
    	 
    	$this->config = new Config($configFile);
    	
    }
    
    private function loadSettings() {
        
        $settings = $this->config->get('settings')?: array();
        
        $this->settings = array_merge($this->_defaultSettings, $settings);
                
    }
    
    /**
     * @param string $label
     * @return boolean|string|integer
     */
    public function getSetting($label) {
        
        return isset($this->settings[$label]) ? $this->settings[$label] : false;
        
    }
    
    private function handleSessions() {
    	
		if ($this->getSetting('userservice')) {
			//TODO:: Add option to allow sessions on public pages
			if (!$this->getRouter()->getRoute()->isPublic()) {
			
				try {
				
					if ($this->getSetting('userclass') === false) throw new PlinthException('Please define a user class and user repository');
						
					$this->getUserService()->setUserRepository($this->getEntityRepository()->getRepository($this->getSetting('userclass')));
					
			    	session_set_cookie_params(0, __BASE);
			    	session_start();
		    	
				} catch (PlinthException $e) {
					
					throw $e;
					
				}
	    	
			}
    	
		}
    	
    }
    
    private function registerLogger() {
    	
        $path = $this->config->get('logger:path')?: __LOGGING_PATH;
                
        if (!file_exists($path)) throw new PlinthException('Logging directory does not exist');
        
        $this->_logger = KLogger::instance($path, KLogger::INFO);
        
        $mail = $this->config->get('logger:mail');
        if (is_array($mail)) {
            foreach ($mail as $name => $address) {
                $this->_logger->addMailing($address, $name);
            }
        }
        
        register_shutdown_function(array(KLogger::class, 'handleShutdown'), $this->_logger);
        
    }
    
    private function loadDatabase() {
    	
    	$db = $this->config->get('database');
    	if ($db !== false) $this->initConnection($db);
    	$this->config->destroy('database');
    	unset($db);
    	
    }
    
    private function registerClasses() {
  
    	$this->setValidator(new Validator($this)); //Connect form validator
    	$this->setEntityRepository(new EntityRepository($this));
    	$this->setRequest(new Request($this)); //Connect Request
    	$this->setResponse(new Response($this));
    	$this->setDict(new Dictionary($this)); //Connect dictionary
    	$this->setStore(new Store($this)); //Connect Store
    	$this->setUserService(new UserService($this));
    	$this->setRouter(new Router($this));
    	
    }
    
    private function executeHandlers() {
    	
    	$this->getRequest()->initRequest($_GET);
    	
    	$baseRoute = __BASE . ($this->_devEnvironment ? 'dev/' : '');
    	
    	$this->getRouter()->loadRoutes(__APP_CONFIG_ROUTING, !$this->getSetting('forcelogin'));
    	$this->getRouter()->handleRoute($baseRoute);

    	$this->handleSessions();
    	$this->handleLogout();
    	$this->handleDictionary($_COOKIE, $this->getSetting('fallbacklocale')); //Handle Dictionary
    	
    }
    
    /**
     * @param string $redirected (optional)
     */
    public function handleRequest($redirected=false) {
    	
    	if ($this->getRouter()->hasRoute()) {
    		 
    	    $route = $this->getRouter()->getRoute();
    	    
    		$this->getRequest()->loadRequest($route, $redirected);
    		 
    		//On a login request first handle the request and afterwards the user
    		if ($this->getRequest()->isLoginRequest()) {
    	
    			$this->getRequest()->handleRequest($route);
    			$this->handleUser();
    			$this->getRequest()->isRouteAuthorized($route);
    			 
    		} else {
    	
    			$this->handleUser();
    			$this->getRequest()->handleRequest($route);
    			 
    		}
    		 
    	}
    	
    }
    
    /**
     * @param string[] $dicts
     * @param string $default
     */
    private function initDictionaries($locales, $default=false) { 
    	
    	Language::init($locales, $default);             
    
    }
    
    /**
     * @param array $c
     */
    public function initConnection($c) { 
    	
    	$this->_connection = new Connection($c['type'], $c['db'], $c['host'], $c['name'], $c['pass'], $c['charset'], $c['port']); 
    
    }
    
    public function closeConnection() { 
    	
    	$this->_connection->close();
    
    }
    
    /**
     * @return Connection
     */
    public function getConnection()	{ 
    	
    	return $this->_connection;									
    
    }
    
    /**
     * @param EntityRepository $em
     */
    public function setEntityRepository($er) {
    	 
    	$this->_entityrepository = $er;
    
    }
    
    /**
     * @return EntityRepository
     */
    public function getEntityRepository() {
    	 
    	return $this->_entityrepository;
    
    }
    
    /**
     * @param Router $rt
     */
    public function setRouter($rt) {
    	
    	$this->_router = $rt;	
    	
    }
    
    /**
     * @return Router
     */
    public function getRouter() {
    	
    	return $this->_router;
    	
    }
    
    /**
     * @param Validator $va
     */
    public function setValidator($va) { 
    	
    	$this->_validator = $va;		
    
    }
    
    /**
     * @return Validator
     */
    public function getValidator() { 
    	
    	return $this->_validator;										
    
    }
    
    /**
     * @param Request $rm
     */
    public function setRequest($rm) { 
    	
    	$this->_request = $rm;		
    
    }
    
    /**
     * @return Request
     */
    public function getRequest() { 
    	
    	return $this->_request;									
    
    }
    
    /**
     * @param Response $rm
     */
    public function setResponse($rm) { 
    	
    	$this->_response = $rm;		
    
    }
    
    /**
     * @return Response
     */
    public function getResponse() { 
    	
    	return $this->_response;									
    
    }
    
    /**
     * @param UserService $us
     */
    public function setUserService($us) { 
    	
    	$this->_userservice = $us;		
    
    }
    
    /**
     * @return UserService
     */
    public function getUserService() { 
    	
    	return $this->_userservice;
    
    }
    
    /**
     * @param Dictionary $dc
     */
    public function setDict($dc) { 
    	
    	$this->_dictionary = $dc;		
    
    }
    
    /**
     * @return Dictionary
     */
    public function getDict() { 
    	
    	return $this->_dictionary;									
    
    }
    
    /**
     * @param Store $st
     */
    public function setStore($st) { 
    	
    	$this->_store = $st;			
    
    }
    
    /**
     * @return Store
     */
    public function getStore() { 
    	
    	return $this->_store;											
    
    }
    
    /**
     * @return KLogger
     */
    public function getLogger() {
    	
    	return $this->_logger;
    	
    }
    
    public function handleLogout() {
        
        if ($this->getRequest()->get('logout') !== null) {
            
            $this->getUserService()->logout();
            
        }
    
    }
    
    /**
     * @param array $cookie PHP $_COOKIE variable
     * @param string|boolean $fallback (optional)
     */
    public function handleDictionary($cookie, $fallback = false) {
    	
    	if (count(Language::getLanguages()) > 0) {
    	
    		$languageCookieIndex = 'plinth-language';
    		$languageCookieAble = false;
    		
	    	$this->_lang = Language::getDefault();
		    		    	
	    	//Get browser language, Accept-Language overrules default language
			if ($this->getSetting('localeaccept') && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && count($this->config->get('language:locales')) > 0) {
				$regex = '/'.implode('|',$this->config->get('language:locales')).'/';
				$languageViaAccept = preg_match_all($regex, $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
				if ($languageViaAccept > 0) {
					$languageCode = $matches[0][0];
				}
			}
			
			//Get get language, Get-Language overrules Accept-Language
	    	if ($this->getSetting('localeget') !== false) {
	    		$languageViaGet = $this->getRequest()->get($this->getSetting('localeget'));
	    		if ($languageViaGet !== null) {
	    			$languageCode = $languageViaGet;
	    			$languageCookieAble = true;
	    		}
	    	}
		    		    	
	    	//Get route language, Route-Language overrules Get-Language
	    	if ($this->getRouter()->hasRoute()) {
		    	$languageViaRequest = $this->getRouter()->getRoute()->get(Route::DATA_LANG);
		    	if ($languageViaRequest !== false) {
		    		$languageCode = $languageViaRequest;
		    		$languageCookieAble = true;
		    	}
	    	}
	    		    	
	    	if ($languageCode !== null) {
    	    	$this->_lang = Language::validate($languageCode);
    	    	if ($this->getSetting('localecookie') && $languageCookieAble) {
    	    		//Save language cookie if it doesn't exist or if it's different from the previously saved one
    	    		if (!isset($cookie[$languageCookieIndex]) || (isset($cookie[$languageCookieIndex]) && $cookie[$languageCookieIndex] !== $this->_lang)) {
    	    			$cookie[$languageCookieIndex] = $this->_lang;
    	    			$this->getResponse()->saveCookie($languageCookieIndex, $this->_lang);
    	    		}
    	    	}
	    	}
	    		    	
	    	if ($this->getSetting('localecookie') && isset($cookie[$languageCookieIndex]) && Language::validate($cookie[$languageCookieIndex]) && $cookie[$languageCookieIndex] !== $this->_lang) {
	    		$this->_lang = $cookie[$languageCookieIndex];
	    	}
	    	    	
	    	$this->getDict()->loadLanguage($this->_lang, $this->getSetting('localetype'));
	    	
	    	if ($fallback !== false) {
	    		if (Language::validate($fallback) === $fallback) {
	    			$this->getDict()->loadLanguage($fallback, $this->getSetting('localetype'), true);	    			
	    		} else {
	    			throw new PlinthException("Your fallback locale, $fallback, doesn't exist");	
	    		}	    		
	    	}
    	
    	}
    		
    }
    
    public function handleUser() {

        if ($this->getSetting('userservice')) {
            $this->getUserService()->verifySession();
        }
            
    }
    
    /**
     * @param string $class
     */
    public function handleModule ($class) {
    
    	$instance = new $class();
    	return $instance->execute($this);
    
    }
    
    /**
     * @param Info $info
     */
    public function addInfo($info) { 
    	
    	array_push($this->_info, $info); 
    
    }
    
    /**
     * @return Info[]
     */
    public function getInfo() { 
    	
    	return $this->_info;       
    
    }
    
    /**
     * @return boolean
     */
    public function hasInfo() {
    	
    	return count($this->_info) > 0;
    	
    }
    
    /**
     * @return string
     */
    public function getLang() { 
    	
    	return $this->_lang;       
    
    }
	
	/**
	 * @param string $label
	 * @throws Exception
	 * @return string
	 */
	public static function getToken($label, $expires = true) {
				
		if (ctype_alpha($label)) {
		
			if (session_status() !== PHP_SESSION_ACTIVE) session_start();
			
			if (isset($_SESSION)) {
			
				$token = md5(uniqid(rand(), TRUE));
				
				$_SESSION['tokens'][$label]['token'] = $token;
				$_SESSION['tokens'][$label]['token_time'] = $expires ? time() : false;
				
				return $label . '_' . $token;
		
			} else {
				
				throw new PlinthException('getToken, this function can only be used when you are using PHP sessions');
				
			}
			
		} else {
			
			throw new PlinthException('getToken, the label can only contain alphabetic characters');
			
		}
		
	}
	
	/**
	 * @param string $formtoken
	 * @return boolean
	 */
	public function validateToken($formtoken) {
				
		$tokeninfo = explode('_', $formtoken);
		
		if (count($tokeninfo) === 2) {
			
			$label = $tokeninfo[0];
			$token = $tokeninfo[1];
			
			if (session_status() !== PHP_SESSION_ACTIVE) session_start();
			
			if (isset($_SESSION['tokens'][$label])) {
				
				if ($_SESSION['tokens'][$label]['token_time'] !== false) {
					if (time() > $_SESSION['tokens'][$label]['token_time'] + $this->getSetting('tokenexpire')) return false;
				}
				
				if ($token === $_SESSION['tokens'][$label]['token']) return true;
				
			}
			
		}
		
		return false;
		
	}
		
}