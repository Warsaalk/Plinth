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
use Plinth\User\UserService;
use Plinth\Response\Response;
use Plinth\Entity\EntityRepository;
use Plinth\Logging\KLogger;
use Plinth\Routing\Route;
use Plinth\Exception\PlinthException;

/**
* This is the main class which handles and provides all the data
*
*/
class Main
{
	const 	STATE_INIT = 0,
			STATE_HANDLING = 1,
			STATE_REQUEST = 2,
			STATE_DONE = 3;

	const	DEFAULT_VALIDATOR_LABEL = 'default';

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
     * @var Validator[]
     */
    private $_validators;
    
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

	/**
	 * @throws PlinthException
	 */
    public function __construct()
	{
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

	/**
	 * Load all components and activate the correct one
	 *
	 * @throws PlinthException
	 */
    private function loadComponent()
	{
		/** @var Component $component */
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
	    			else throw new PlinthException('Their can only be one default path in components.json config');
	    		}
	    		if ($loadedComponent->matchesCurrentPath($currentPath)) {
	    			if ($component === false || $loadedComponent->getDepth() > $component->getDepth()) $component = $loadedComponent;
	    		}
	    	}
    	}
    	    	
    	$this->component = $component;
    }

	/**
	 * Load the config(s)
	 *
	 * @throws PlinthException
	 */
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

	/**
	 * Load all application settings from the config file
	 */
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

	/**
	 * Handle session_start when needed
	 *
	 * @throws PlinthException
	 */
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

	/**
	 * Register the logger service
	 *
	 * @throws PlinthException
	 */
    private function registerLogger()
	{
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

	/**
	 * Load the different database connections
	 *
	 * @throws PlinthException
	 */
    private function loadDatabases()
	{
		$databaseKeys = $this->config->get('databases:keys')?: array();

		if (!isset($databaseKeys['database'])) $databaseKeys[] = 'database'; // Default database config key

		foreach ($databaseKeys as $databaseKey) {
			$database = $this->config->get($databaseKey);
			if ($database !== false) $this->initConnection($database, $databaseKey);
			$this->config->destroy($databaseKey);
			unset($database);
		}
    }

	/**
	 * Register the necessary connector classes
	 */
    private function registerClasses()
	{
    	$this->addValidator(self::DEFAULT_VALIDATOR_LABEL, new Validator($this)); //Connect form validator
    	$this->setEntityRepository(new EntityRepository($this));
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
	private function registerSessionPath()
	{
		$cookiePath = __BASE;

		if ($this->component !== false && !$this->component->usesRootCookiePath()) {
			$cookiePath .= $this->component->getPath();
		}

		session_set_cookie_params(0, $cookiePath);
	}

	/**
	 * Handle the necessary services
	 *
	 * @throws PlinthException
	 */
	private function executeHandlers()
	{
        $this->state = self::STATE_HANDLING;
    	    
    	$this->handleRouter();
    	$this->handleSessions();
    	$this->handleLogout();
    	$this->handleDictionary($_COOKIE, $this->getSetting('fallbacklocale')); //Handle Dictionary
    }

	/**
	 * Load and handle the application routing
	 *
	 * @throws PlinthException
	 */
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
	 * Handle the current request
	 * @throws PlinthException
	 */
    public function handleRequest()
	{
    	if ($this->state < self::STATE_HANDLING) {
        	$this->executeHandlers();
        	$this->state = self::STATE_REQUEST;
    	}
    	
    	if ($this->getRouter()->hasRoute()) {
			if ($this->getRouter()->isRouteAllowed()) {

				// Create a new request
				$this->setRequest(new Request($this, $this->getRouter()->getRoute()));
				$this->getRequest()->handleController();

				//On a login request first handle the request and afterwards the user
				if ($this->getRequest()->isLoginRequest()) {
					$this->getRequest()->handleRequest();
					$this->handleUser();
					$this->getRequest()->isRouteAuthorized();
				} else {
					$this->handleUser();
					$this->getRequest()->isRouteAuthorized();
					$this->getRequest()->handleRequest();
				}
			} else $this->getResponse()->hardExit(Response::CODE_405);
    	} else $this->getResponse()->hardExit(Response::CODE_404);
    	
    	if ($this->state < self::STATE_DONE) {
    		$this->state = self::STATE_DONE;
    	}
    }

	/**
	 * Handle user logout
	 */
	public function handleLogout()
	{
		if (Request::get('logout') !== null) {
			$this->getUserService()->logout();

			//Strip the logout parameter & redirect to the original destination page
			header('Location: ' . __BASE_URL . preg_replace('/(logout&|\?logout$|&logout$)/', '', Request::getRequestPath(__BASE, false)));
			exit;
		}
	}

	/**
	 * Handle the user defined dictionary service if present
	 *
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
	 * Handle the application dictionary/translations
	 *
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
				$languageViaGet = Request::get($this->getSetting('localeget'));
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

	/**
	 * Verify the user session
	 *
	 * @throws PlinthException
	 */
	public function handleUser()
	{
		if ($this->getSetting('userservice')) {
			$this->getUserService()->verifySession();
		}
	}

	/**
	 * @param string $class
	 * @return mixed
	 * @throws PlinthException
	 * @deprecated
	 */
	public function handleModule($class)
	{
		$instance = new $class();
		if (method_exists($instance, 'execute')) {
			return $instance->execute($this);
		} else {
			throw new PlinthException("The method execute is not implemented on your $class class.");
		}
	}

	/**
	 * @param string[] $locales
	 * @param string|bool $default
	 */
    private function initDictionaries($locales, $default = false)
	{
    	Language::init($locales, $default);
    }

	/**
	 * @param array $connectionData
	 * @param string $name
	 * @return $this
	 * @throws PlinthException
	 */
    public function initConnection($connectionData, $name = "database")
	{
    	$this->_connections[$name] = Connection::initializeFromArray($connectionData);

    	return $this;
    }

	/**
	 * @param string $name
	 * @return $this
	 */
    public function closeConnection($name = "database")
	{
    	if (isset($this->_connections[$name])) $this->_connections[$name]->close();

    	return $this;
    }

	/**
	 * @param string $name
	 * @return null|Connection
	 */
    public function getConnection($name = "database")
	{
		if (isset($this->_connections[$name])) return $this->_connections[$name];

		return null;
    }
    
    /**
     * @param EntityRepository $er
	 * @return $this
     */
    public function setEntityRepository(EntityRepository $er)
	{
    	$this->_entityrepository = $er;

    	return $this;
    }
    
    /**
     * @return EntityRepository
     */
    public function getEntityRepository()
	{
    	return $this->_entityrepository;
    }
    
    /**
     * @param Router $rt
	 * @return $this
     */
    public function setRouter(Router $rt)
	{
    	$this->_router = $rt;

    	return $this;
    }
    
    /**
     * @return Router
     */
    public function getRouter()
	{
    	return $this->_router;
    }

	/**
	 * @param $label
	 * @param Validator $va
	 * @return $this
	 */
    public function addValidator($label, Validator $va = null)
	{
    	$this->_validators[$label] = $va?: new Validator($this);

    	return $this;
    }

	/**
	 * @param $label
	 * @return bool
	 */
    public function hasValidator($label = self::DEFAULT_VALIDATOR_LABEL)
	{
		return isset($this->_validators[$label]);
	}

	/**
	 * @param string $label
	 * @return null|Validator
	 */
    public function getValidator($label = self::DEFAULT_VALIDATOR_LABEL)
	{
    	return isset($this->_validators[$label]) ? $this->_validators[$label] : null;
    }
    
    /**
     * @param Request $rm
	 * @return $this
     */
    public function setRequest(Request $rm)
	{
    	$this->_request = $rm;		

    	return $this;
    }
    
    /**
     * @return Request
	 * @throws PlinthException
     */
    public function getRequest()
	{
		if ($this->_request === null) throw new PlinthException("The request has not been initiated yet, this happens in 'handleRequest()'.");

    	return $this->_request;
    }
    
    /**
     * @param Response $rm
	 * @return $this
     */
    public function setResponse(Response $rm)
	{
    	$this->_response = $rm;		

    	return $this;
    }
    
    /**
     * @return Response
     */
    public function getResponse()
	{
    	return $this->_response;
    }
    
    /**
     * @param UserService $us
	 * @return $this
     */
    public function setUserService(UserService $us)
	{
    	$this->_userservice = $us;		

    	return $this;
    }
    
    /**
     * @return UserService
     */
    public function getUserService()
	{
    	return $this->_userservice;
    }
    
    /**
     * @param Dictionary $dc
	 * @return $this
     */
    public function setDict(Dictionary $dc)
	{
    	$this->_dictionary = $dc;

    	return $this;
    }
    
    /**
     * @return Dictionary
     */
    public function getDict()
	{
    	return $this->_dictionary;
    }
    
    /**
     * @param Store $st
	 * @return $this
     */
    public function setStore(Store $st)
	{
    	$this->_store = $st;			

    	return $this;
    }
    
    /**
     * @return Store
     */
    public function getStore()
	{
    	return $this->_store;
    }

	/**
	 * @param Bin $bin
	 * @return $this
	 */
	public function setBin(Bin $bin)
	{
		$this->_bin = $bin;

		return $this;
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
    public function getLogger()
	{
    	return $this->_logger;
    }
    
    /**
     * @return Component|boolean
     */
    public function getComponent()
	{
    	return $this->component;
    }
    
    /**
     * @param Info $info
	 * @return $this
     */
    public function addInfo($info)
	{
    	array_push($this->_info, $info);

    	return $this;
    }
    
    /**
     * @param boolean $asArray (optional)
     * @return Info[]
     */
    public function getInfo($asArray = false)
	{
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
    public function hasInfo()
	{
    	return count($this->_info) > 0;
    }

	/**
	 * @param $lang
	 * @return $this
	 */
	public function setLang($lang)
	{
		if (Language::validate($lang)) {
			$this->_lang = $lang;
		}
		return $this;
	}
    
    /**
     * @return string
     */
    public function getLang()
	{
    	return $this->_lang;
    }

	/**
	 * @param $label
	 * @param bool $expires
	 * @return string
	 * @throws PlinthException
	 */
	public static function getToken($label, $expires = true)
	{
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
	public function validateToken($formtoken)
	{
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