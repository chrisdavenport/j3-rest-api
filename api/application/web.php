<?php
/**
 * @package    Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Joomla API Web Application.
 *
 * @package  Joomla.Services
 * @since    3.2
 */
class ApiApplicationWeb extends JApplicationWeb
{
	/**
	 * @var    JDatabaseDriver  A database object for the application to use.
	 * @since  3.2
	 */
	protected $db;

	/**
	 * @var    JApplicationWebRouter  A router object for the application to use.
	 * @since  3.2
	 */
	protected $router;

	/**
	 * @var    JCache  The application cache object.
	 * @since  3.2
	 */
	protected $cache;

	/**
	 * @var    array  Service routes.
	 * @since  3.2
	 */
	protected $maps = array();

	/**
	 * The start time for measuring the execution time.
	 *
	 * @var    float
	 * @since  3.2
	 */
	private $_startTime;

	/**
	 * Overrides the parent constructor to set the execution start time.
	 *
	 * @param   mixed  $input   An optional argument to provide dependency injection for the application's
	 *                          input object.  If the argument is a JInput object that object will become
	 *                          the application's input object, otherwise a default input object is created.
	 * @param   mixed  $config  An optional argument to provide dependency injection for the application's
	 *                          config object.  If the argument is a JRegistry object that object will become
	 *                          the application's config object, otherwise a default config object is created.
	 * @param   mixed  $client  An optional argument to provide dependency injection for the application's
	 *                          client object.  If the argument is a JApplicationWebClient object that object will become
	 *                          the application's client object, otherwise a default client object is created.
	 *
	 * @since   3.2
	 */
	public function __construct(JInput $input = null, JRegistry $config = null, JApplicationWebClient $client = null)
	{
		$this->_startTime = microtime(true);

		parent::__construct($input, $config, $client);

		// Load the Joomla CMS configuration object.
		$this->loadConfiguration($this->fetchConfigurationData());

		// By default, assume response may be cached.
		$this->allowCache(true);
	}

	/**
	 * Permits retrieval of the database connection for this application.
	 *
	 * @return  JDatabaseDriver  The database driver.
	 *
	 * @since   3.2
	 */
	public function getDatabase()
	{
		return $this->db;
	}

	/**
	 * Allows the application to load a custom or default database driver.
	 *
	 * @param   JDatabaseDriver  $driver  An optional database driver object. If omitted, the application driver is created.
	 *
	 * @return  object This method may be chained.
	 *
	 * @since   3.2
	 */
	public function loadDatabase(JDatabaseDriver $driver = null)
	{
		if ($driver === null)
		{
			$this->db = JDatabaseDriver::getInstance(
				array(
					'driver' => $this->get('dbtype'),
					'host' => $this->get('host'),
					'user' => $this->get('user'),
					'password' => $this->get('password'),
					'database' => $this->get('db'),
					'prefix' => $this->get('dbprefix'),
//					'schema' => $this->get('db_schema'),
//					'port' => $this->get('db_port')
				)
			);

			// Select the database.
			$this->db->select($this->get('db'));
		}
		// Use the given database driver object.
		else
		{
			$this->db = $driver;
		}

		// Set the database to our static cache.
		JFactory::$database = $this->db;

		return $this;
	}

	/**
	 * Method to load services route maps.
	 *
	 * @param   array  $maps  A list of route maps to add to the router as $pattern => $controller.
	 *
	 * @return  object This method may be chained.
	 *
	 * @since   3.2
	 */
	protected function loadMaps($maps = array())
	{
		// Make sure we have an array.
		$maps = (array) $maps;

		// If route indicates a traditional Joomla component then register special prefix.
		foreach ($maps as $key => $route)
		{
			$parts = explode('/', $route);
			if ($parts[0] == 'component')
			{
				$path = JPATH_SITE . '/components/com_' . $parts[1] . '/services';
				JLoader::registerPrefix('Component' . ucfirst($parts[1]), $path);
			}
		}

		$this->maps = array_merge($this->maps, $maps);

		return $this;
	}

	/**
	 * Allows the application to load a custom or default router.
	 *
	 * @param   JApplicationWebRouter  $router  An optional router object. If omitted, the standard router is created.
	 *
	 * @return  object This method may be chained.
	 *
	 * @since   3.2
	 */
	public function loadRouter(JApplicationWebRouter $router = null)
	{
		$this->router = ($router === null) ? new ApiApplicationRouter($this, $this->input) : $router;

		return $this;
	}

	/**
	 * Execute the application.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	protected function doExecute()
	{
		$documentOptions = array(
			'absoluteHrefs' => $this->get('absoluteHrefs', false),
		);

		try
		{
			// Set the controller prefix, add maps, and execute the appropriate controller.
			$this->input = new JInputJson;
			$this->document = new ApiDocumentHalJson($documentOptions);
			$this->router->setControllerPrefix('ApiServices')
				->setDefaultController('Root')
				->addMaps($this->maps)
				->execute($this->get('uri.route'));
		}
		catch (Exception $e)
		{
			$this->setHeader('status', '400', true);
			$message = $e->getMessage();
			$body = array('message' => $message, 'code' => $e->getCode(), 'type' => get_class($e));

			$this->setBody(json_encode($body));
		}
	}

	/**
	 * Method to get the application configuration data to be loaded.
	 *
	 * @param   string  $file   The path and filename of the configuration file. If not provided, configuration.php
	 *                          in JPATH_BASE will be used.
	 * @param   string  $class  The class name to instantiate.
	 *
	 * @return  object An object to be loaded into the application configuration.
	 *
	 * @since   3.2
	 */
	public function fetchApiConfigurationData($file = '', $class = 'JConfig')
	{
		// Instantiate variables.
		$config = array();

		// Ensure that required path constants are defined.
		if (!defined('JPATH_CONFIGURATION'))
		{
			$path = getenv('JAPI_CONFIG');
			if ($path)
			{
				define('JPATH_CONFIGURATION', realpath($path));
			}
			else
			{
				define('JPATH_CONFIGURATION', realpath(dirname(JPATH_BASE) . '/config'));
			}
		}

		// Set the configuration file path for the application.
		if (file_exists(JPATH_CONFIGURATION . '/config.json'))
		{
			$file = JPATH_CONFIGURATION . '/config.json';
		}
		else
		{
			$file = JPATH_CONFIGURATION . '/config.dist.json';
		}

		if (!is_readable($file))
		{
			throw new RuntimeException('Configuration file does not exist or is unreadable.');
		}

		// Load the configuration file into an object.
		$config = json_decode(file_get_contents($file));

		return $config;
	}

	/**
	 * Method to load services route maps from all subdirectories
	 * within a given directory (non-recursive).
	 *
	 * @param  string  $basePath  Path to base directory.
	 *
	 * @return  object This method may be chained.
	 *
	 * @since   3.2
	 */
	protected function fetchMaps($basePath = JPATH_SITE)
	{
		// Get a directory iterator for the base path.
		$iterator = new DirectoryIterator($basePath);

		// Iterate over the files, looking for just the directories.
		foreach ($iterator as $file)
		{
			$fileName = $file->getFilename();

			// Only want directories.
			if ($file->isDir())
			{
				// Look for services file.
				$servicesFilename = $basePath . '/' . $fileName . '/services.json';
				if (file_exists($servicesFilename))
				{
					$this->loadMaps(json_decode(file_get_contents($servicesFilename), true));
				}
			}
		}

		return $this;
	}

	/**
	 * Method to load services route maps from standard locations.
	 *
	 * @return  object This method may be chained.
	 *
	 * @since   3.2
	 */
	public function fetchStandardMaps()
	{
		// Look for maps in front-end components.
		$this->fetchMaps(JPATH_SITE . '/components');

		// Look for maps in back-end components.
		$this->fetchMaps(JPATH_ADMINISTRATOR . '/components');

		// Merge the main services file.
		$this->loadMaps(json_decode(file_get_contents(JPATH_CONFIGURATION . '/services.json'), true));

		return $this;
	}

	/**
	 * Method to get services route maps.
	 *
	 * @return  array  A list of route maps to add to the router as $pattern => $controller.
	 *
	 * @since   3.2
	 */
	public function getMaps()
	{
		return $this->maps;
	}

	/**
	 * Method to send the application response to the client.  All headers will be sent prior to the main
	 * application output data.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	protected function respond()
	{
		$runtime = microtime(true) - $this->_startTime;

		// Set the Server and X-Powered-By Header.
		$this->setHeader('Server', '', true);
		$this->setHeader('X-Powered-By', 'JoomlaWebAPI/1.0', true);
		$this->setHeader('X-Runtime', $runtime, true);

		// Copy document encoding and charset into application.
		$this->mimeType = $this->getDocument()->getMimeEncoding();
		$this->charSet  = $this->getDocument()->getCharset();

		parent::respond();
	}
}
