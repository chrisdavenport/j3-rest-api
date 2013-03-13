<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

/**
 * RESTful Web application router class for the Joomla CMS.
 *
 * @package     Joomla.Services
 * @since       3.1
 */
class ApiApplicationRouter extends JApplicationWebRouterRest
{
	/**
	 * Parse the given route and return the name of a controller mapped to the given route.
	 *
	 * @param   string  $route  The route string for which to find and execute a controller.
	 *
	 * @return  string  The controller name for the given route excluding prefix.
	 *
	 * @since   3.1
	 * @throws  InvalidArgumentException
	 */
	protected function parseRoute($route)
	{
		$controller = parent::parseRoute($route);

		// If the controller name includes a component route prefix then handle it.
		// Form is 'controller/' component-name-without-com_ controllername
		// eg. 'component/content/ArticlesList' will become ComponentContentArticlesList
		$parts = explode('/', $controller);
		if ($parts[0] == 'component')
		{
			$this->controllerPrefix = 'Component' . ucfirst($parts[1]);
			$controller = $parts[2];
		}

		return $controller;
	}
}
