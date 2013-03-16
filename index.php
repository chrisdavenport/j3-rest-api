<?php
/**
 * @package     Joomla.Api
 * @subpackage  API
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/*
 * If you get 404's when requesting pages in the API then you probably
 * need to add the following lines to your .htaccess file.
 *
 * # If the requested path and file is not /index.php and the request
 * # has not already been internally rewritten to the index.php script
 * RewriteCond %{REQUEST_URI} !^/index\.php
 * # and the request is for something within the api folder
 * RewriteCond %{REQUEST_URI} /api/ [NC]
 * # and the requested path and file doesn't directly match a physical file
 * RewriteCond %{REQUEST_FILENAME} !-f
 * # and the requested path and file doesn't directly match a physical folder
 * RewriteCond %{REQUEST_FILENAME} !-d
 * # internally rewrite the request to the API index.php script
 * RewriteRule .* api/index.php [L]
 * #
 */
error_reporting(-1);
ini_set('display_errors', 1);

// Define the application home directory.
$JAPIHOME = getenv('JAPI_HOME') ? getenv('JAPI_HOME') : dirname(__DIR__);

// Look for the Joomla Platform.
$JPLATFORMHOME = getenv('JPLATFORM_HOME') ? getenv('JPLATFORM_HOME') : dirname(__DIR__) . '/libraries';

// Fire up the Platform importer.
if (file_exists($JPLATFORMHOME . '/import.php'))
{
	require $JPLATFORMHOME . '/import.php';
}

// Ensure that required path constants are defined.
if (!defined('JPATH_BASE'))
{
	define('JPATH_BASE', realpath(dirname(__DIR__)));
}
if (!defined('JPATH_SITE'))
{
	define('JPATH_SITE', $JAPIHOME);
}
if (!defined('JPATH_CACHE'))
{
	define('JPATH_CACHE', '/tmp/cache');
}
if (!defined('JPATH_CONFIGURATION'))
{
	define('JPATH_CONFIGURATION', $JAPIHOME . '/etc');
}
if (!defined('JPATH_API'))
{
	define('JPATH_API', $JAPIHOME . '/api');
}

try
{
	// Fire up the API importer.
	if (file_exists(JPATH_API . '/import.php'))
	{
		require JPATH_API . '/import.php';
	}

	// Instantiate the application.
	$application = JApplicationWeb::getInstance('ApiApplicationWeb');

	// Store the application.
	JFactory::$application = $application;

	// Execute the application.
	$application->loadSession()
		->loadConfiguration($application->fetchApiConfigurationData())
		->loadDatabase()
		->fetchStandardMaps()
		->loadRouter()
		->execute();
}
catch (Exception $e)
{
	// Set the server response code.
	header('Status: 500', true, 500);

	// An exception has been caught, echo the message and exit.
	echo json_encode(array('message' => $e->getMessage(), 'code' => $e->getCode(), 'type' => get_class($e)));
	exit();
}
