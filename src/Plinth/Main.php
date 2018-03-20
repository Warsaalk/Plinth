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
use Plinth\Settings\Settings;
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

	const 	STATE_INIT = 0,
			STATE_HANDLING = 1,
			STATE_REQUEST = 2,
			STATE_DONE = 3;
	
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
	 * @var Bin
	 */
    private $_bin;
    
    /**
     * @var Connection[]
     */
    private $_connections;
    
    /**
     * @var KLogger
     */
    private $_logger;
    
    /**
     * @var Component|boolean
     */
    private $component;
    
    /**
     * @var integer
     */
    private $state;
    
    /**
     * @var Config
     */
    public $config;

	/**
	 * @var Config
	 */
    public $initialConfig;
    
    /**
     * @var Settings
     */
    public $settings;

	/**
	 * @var Settings
	 */
    public $initialSettings;
    
    public function __construct() { 
        
        $this->state = self::STATE_INIT;
    	
    	$this->loadComponent();
    	$this->loadConfig();
        $this->loadSettings();

        if (($timezone = $this->config->get('date:timezone')) !== false) {
        	date_default_timezone_set($timezone);
        }

		// Set Character encoding
		mb_internal_encoding($this->getSetting('characterencoding'));

        $this->registerLogger();
                
    	$this->loadDatabases();
                
        $this->initDictionaries($this->config->get('language:locales')?: array(), $this->getSetting('defaultlocale'));
        
        $this->registerClasses();
		$this->registerSessionPath();
    			
    }
    
    private function loadComponent()
	{
    	$component = false;
    	$currentPath = Request::getRequestPath(__BASE);
    	$defaultPath = false;
    	
    	if (file_exists(__APP_CONFIG_COMPONENTS)) {
    	
	    	$componentsData = json_decode(file_get_contents(__APP_CONFIG_COMPONENTS), true);
	    	
	    	if (!is_array($componentsData)) throw new PlinthException('Cannot parse components.json config');
	    	
	    	foreach ($componentsData as $label => $data) {
	    		$loadedComponent = Component::loadFromArray(__APP_CONFIG_PATH, $label, $data);
	    		if ($loadedComponent->getPath() === false) {
	    			if ($defaultPath === false) $defaultPath = true;
	    			else						throw new PlinthException('Their can only be one default path in components.json config');
	    		}
	    		if ($loadedComponent->matchesCurrentPath($currentPath)) {
	    			if ($component === false || $loadedComponent->getDepth() > $component->getDepth()) $component = $loadedComponent;
	    		}
	    	}
    		    	
    	}
    	    	
    	$this->component = $component;
    }

	private function loadConfig()
	{
		$this->initialConfig = new Config(__APP_CONFIG_PROD);
		$this->config = clone $this->initialConfig;

		if ($this->component !== false && $this->component->hasConfig()) {
			$componentconfig = new Config($this->component->getConfigPath());
			if ($this->component->getMergeDefaultConfig()) {
				$this->config->merge($componentconfig);
			} else {
				$this->config = $componentconfig;
			}
		}
	}
    
    private function loadSettings()
	{
    	// Set initial settings base on the initial config
		$userdefinedsettings = $this->initialConfig->get('settings')?: array();

		$this->initialSettings = new Settings();
		$this->initialSettings->loadSettings($userdefinedsettings);

    	// Set settings
        $userdefinedsettings = $this->config->get('settings')?: array();

		$this->settings = new Settings();
		$this->settings->loadSettings($userdefinedsettings);
    }
    
    /**
     * @param string $label
	 * @param boolean $useInitialSettings
     * @return boolean|string|integer
     */
    public function getSetting($label, $useInitialSettings = false)
	{
        return 	$useInitialSettings === true
				? $this->initialSettings->getSetting($label)
				: $this->settings->getSetting($label);
    }

	/**
	 * @param boolean $useInitialSettings
	 * @return Settings
	 */
    public function getSettings($useInitialSettings = false)
	{
		return 	$useInitialSettings === true
				? $this->initialSettings
				: $this->settings;
	}
    
    private function handleSessions()
	{
		if ($this->getRouter()->hasRoute()) {
			if (!$this->getRouter()->getRoute()->isPublic() || $this->getRouter()->getRoute()->allowSessions()) {
				if ($this->getSetting('userservice')) {
					try
					{
						if ($this->getSetting('userclass') === false) throw new PlinthException('Please define a user class and user repository');

						$this->getUserService()->setUserRepository($this->getEntityRepository()->getRepository($this->getSetting('userclass')));

						session_start();
					}
					catch (PlinthException $e)
					{
						throw $e;
					}
				} else {
					session_start();
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
    
    private function loadDatabases() {

		$databaseKeys = $this->config->get('databases:keys')?: array();

		if (!isset($databaseKeys['database'])) $databaseKeys[] = 'database'; // Default database config key

		foreach ($databaseKeys as $databaseKey) {
			$database = $this->config->get($databaseKey);
			if ($database !== false) $this->initConnection($database, $databaseKey);
			$this->config->destroy($databaseKey);
			unset($database);
		}
    	
    }
    
    private function registerClasses() {
  
    	$this->setValidator(new Validator($this)); //Connect form validator
    	$this->setEntityRepository(new EntityRepository($this));
    	$this->setRequest(new Request($this)); //Connect Request
    	$this->setResponse(new Response($this));
    	$this->setDict(new Dictionary($this)); //Connect dictionary
    	$this->setStore(new Store($this)); //Connect Store
		$this->setBin(new Bin($this)); // Connect Bin
    	$this->setUserService(new UserService($this));
    	$this->setRouter(new Router($this));
    	
    }

	/**
	 * Register different session paths for the different components 
	 */
	private function registerSessionPath() {

		$cookiePath = __BASE;

		if ($this->component !== false && !$this->component->usesRootCookiePath()) {
			$cookiePath .= $this->component->getPath();
		}

		session_set_cookie_params(0, $cookiePath);

	}
    
    private function executeHandlers() {
        
        $this->state = self::STATE_HANDLING;
    	
    	$this->getRequest()->initRequest($_GET);
    	    
    	$this->handleRouter();

    	$this->handleSessions();
    	$this->handleLogout();
    	$this->handleDictionary($_COOKIE, $this->getSetting('fallbacklocale')); //Handle Dictionary
    	
    }
    
    public function handleRouter()
	{
		// Load initial/default routes if there're no components or the component wants to merge them
    	if ($this->component === false || $this->component->getMergeDefaultRouting()) {
    		$this->getRouter()->loadRoutes(
    			__APP_CONFIG_ROUTING,
				!$this->getSetting('forcelogin', true),
				$this->getSetting('forcesession', true),
				$this->getSetting('templatebase', true),
				$this->getSetting('templatepath', true)
			);
    	}

		// If there's a component
		if ($this->component !== false) {
			$this->getRouter()->loadRoutes(
				$this->component->hasRouting() ? $this->component->getRoutingPath() : __APP_CONFIG_ROUTING,
				!$this->getSetting('forcelogin'),
				$this->getSetting('forcesession'),
				$this->getSetting('templatebase'),
				$this->getSetting('templatepath')
			);
    	}
    	    	
    	$this->getRouter()->handleRoute(__BASE);
    }
    
    /**
     * @param string $redirected (optional)
     */
    public function handleRequest($redirected=false) {
    	
    	if ($this->state < self::STATE_HANDLING) {
        	$this->executeHandlers();
        	$this->state = self::STATE_REQUEST;
    	}
    	
    	if ($this->getRouter()->hasRoute()) {
			if ($this->getRouter()->isRouteAllowed()) {

				$route = $this->getRouter()->getRoute();

				$this->getRequest()->loadRequest($route, $redirected);

				//On a login request first handle the request and afterwards the user
				if ($this->getRequest()->isLoginRequest()) {

					$this->getRequest()->handleRequest($route);
					$this->handleUser();
					$this->getRequest()->isRouteAuthorized($route);

				} else {

					$this->handleUser();
					$this->getRequest()->isRouteAuthorized($route);
					$this->getRequest()->handleRequest($route);

				}

			} else $this->getResponse()->hardExit(Response::CODE_405);
    	} else $this->getResponse()->hardExit(Response::CODE_404);
    	
    	if ($this->state < self::STATE_DONE) {
    		$this->state = self::STATE_DONE;
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
	 * @param $connectionData
	 * @param string $name
	 */
    public function initConnection($connectionData, $name = "database") {
    	
    	$this->_connections[$name] = new Connection(
    		$connectionData['type'],
			$connectionData['db'],
			$connectionData['host'],
			$connectionData['name'],
			$connectionData['pass'],
			$connectionData['charset'],
			$connectionData['port']
		);
    
    }

	/**
	 * @param string $name
	 */
    public function closeConnection($name = "database") {

    	if (isset($this->_connections[$name])) $this->_connections[$name]->close();
    
    }

	/**
	 * @param string $name
	 * @return null|Connection
	 */
    public function getConnection($name = "database")	{

		if (isset($this->_connections[$name])) return $this->_connections[$name];

		return null;
    
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
	 * @param Bin $bin
	 */
	public function setBin(Bin $bin)
	{
		$this->_bin = $bin;
	}

	/**
	 * @return Bin
	 */
	public function getBin()
	{
		return $this->_bin;
	}
    
    /**
     * @return KLogger
     */
    public function getLogger() {
    	
    	return $this->_logger;
    	
    }
    
    /**
     * @return Component|boolean
     */
    public function getComponent() {
    	
    	return $this->component;
    	
    }
    
    public function handleLogout() {
        
        if ($this->getRequest()->get('logout') !== null) {
            
            $this->getUserService()->logout();
            
            //Strip the logout parameter & redirect to the original destination page
            header('Location: ' . __BASE_URL . preg_replace('/(logout&|\?logout$|&logout$)/', '', Request::getRequestPath(__BASE, false)));
            exit;
            
        }
    
    }

	/**
	 * @param bool $fallback
	 * @return $this
	 * @throws PlinthException
	 */
    private function handleDictionaryService($fallback = false)
	{
		$dictionaryServiceClass = $this->getSetting("dictionaryservice");
		$dictionaryServiceMerge = $this->getSetting("dictionarymerge");

		if (class_exists($dictionaryServiceClass)) {
			/** @var Dictionary\DictionaryService $dictionaryService */
			$dictionaryService = new $dictionaryServiceClass($this);

			$this->getDict()->loadFromArray($dictionaryService->loadTranslations($this->getLang()), $dictionaryServiceMerge);
			if (Language::validate($fallback) === $fallback) {
				$this->getDict()->loadFromArray($dictionaryService->loadTranslations($fallback), $dictionaryServiceMerge, true);
			}
		} else {
			throw new PlinthException("Your dictionary service implementation, $dictionaryServiceClass, cannot be found.");
		}

		return $this;
	}

	/**
	 * @param array $cookie PHP $_COOKIE variable
	 * @param string|boolean $fallback (optional)
	 * @throws PlinthException
	 */
    public function handleDictionary($cookie, $fallback = false)
	{
    	if (count(Language::getLanguages()) > 0) {
    		$languageCookieIndex = 'plinth-language';
    		$languageCookieAble = false;
    		$languageCode = null;
    		
	    	$this->_lang = Language::getDefault();
		    		    	
	    	//Get browser language, Accept-Language overrules default language
			if ($this->getSetting('localeaccept') && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && count($this->config->get('language:locales')) > 0) {
				$regex = '/'.implode('|',$this->config->get('language:locales')).'/';
				$languageViaAccept = preg_match_all($regex, $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
				if ($languageViaAccept > 0) {
					$languageCode = $matches[0][0];
				}
			}

			//Get language for domain, Domain-Language overrules Accept-Language
			if ($this->getSetting('localedomain') && isset($_SERVER['HTTP_HOST']) && count($this->config->get('locale:domains')) > 0) {
				$domains = $this->config->get('locale:domains');
				$regex = '/('.implode('|', array_keys($domains)).')$/';
				$languageViaDomain = preg_match_all($regex, $_SERVER['HTTP_HOST'], $matches);
				if ($languageViaDomain > 0) {
					$languageCode = $domains[$matches[1][0]];
				}
			}

			//Get language for custom subdomain, Custom-Subdomain-Language overrules Domain-Language
			if ($this->getSetting('localesubdomain') && isset($_SERVER['HTTP_HOST']) && count($this->config->get('locale:subdomains')) > 0) {
				$subdomains = $this->config->get('locale:subdomains');
				$regex = '/(?:\.|^)('.implode('|', array_keys($subdomains)).')\./';
				$languageViaCustomSubDomain = preg_match_all($regex, $_SERVER['HTTP_HOST'], $matches);
				if ($languageViaCustomSubDomain > 0) {
					$languageCode = $subdomains[$matches[1][0]];
				}
			}

			//Get subdomain language, Subdomain-Language overrules Custom-Subdomain-Language
			if ($this->getSetting('localesubdomain') && isset($_SERVER['HTTP_HOST']) && count($this->config->get('language:locales')) > 0) {
				$regex = '/(?:\.|^)('.implode('|',$this->config->get('language:locales')).')\./';
				$languageViaSubdomain = preg_match_all($regex, $_SERVER['HTTP_HOST'], $matches);
				if ($languageViaSubdomain > 0) {
					$languageCode = $matches[1][0];
				}
			}

			//Get get language, Get-Language overrules Custom-Subdomain-Language
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

	    	//Set the language code and save it to the cookie if needed
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

	    	//If the language cookie is present get it and override the existing language code
	    	if ($this->getSetting('localecookie') && isset($cookie[$languageCookieIndex]) && Language::validate($cookie[$languageCookieIndex]) && $cookie[$languageCookieIndex] !== $this->_lang) {
	    		$this->_lang = $cookie[$languageCookieIndex];
	    	}

	    	//Load the translations into the dictionary
	    	$this->getDict()->loadLanguage($this->_lang, $this->getSetting('localetype'));

			//If a fallback language is enabled load its translations into the dictionary
	    	if ($fallback !== false) {
	    		if (Language::validate($fallback) === $fallback) {
	    			$this->getDict()->loadLanguage($fallback, $this->getSetting('localetype'), true);	    			
	    		} else {
	    			throw new PlinthException("Your fallback locale, $fallback, doesn't exist");	
	    		}	    		
			}

			//If a user defined dictionary service is defined load its translations into the dictionary
			$this->handleDictionaryService($fallback);
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
     * @param boolean $asArray (optional)
     * @return Info[]
     */
    public function getInfo($asArray = false) { 
    	
    	if ($asArray === true) {
    		$infos = array();
    		foreach ($this->_info as $info) {
    			$infos[] = $info->getArray();
    		}
    		return $infos;
    	}
    	
    	return $this->_info;
    
    }
    
    /**
     * @return boolean
     */
    public function hasInfo() {
    	
    	return count($this->_info) > 0;
    	
    }

	/**
	 * @param $lang
	 */
	public function setLang($lang)
	{
		if (Language::validate($lang)) {
			$this->_lang = $lang;
		}
	}
    
    /**
     * @return string
     */
    public function getLang()
	{
    	return $this->_lang;
    }
	
	/**
	 * @param string $label
	 * @throws Exception
	 * @return string
	 */
	public static function getToken($label, $expires = true) {
				
		if (ctype_alnum($label)) {
		
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
			
			throw new PlinthException('getToken, the label can only contain alphanumeric characters');
			
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