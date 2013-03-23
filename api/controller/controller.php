<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

interface ApiController extends JController
{
	/**
	 * Get API query object.
	 *
	 * Returns the API query helper object, creating a new
	 * one if it doesn't already exist.
	 *
	 * @return ApiDatabaseQuery object;
	 */
	public function getApiQuery();

	/**
	 * Get resource data.
	 *
	 * @return object Resource data.
	 */
	public function getData();

	/**
	 * Get database query.
	 *
	 * Returns a new base query for the table name given.
	 *
	 * @param  string  $table  Primary table name.
	 *
	 * @return JDatabaseDriver object.
	 */
	public function getQuery($table);

	/**
	 * Get service object.
	 *
	 * @return ApiDatabaseQuery object;
	 */
	public function getService();

}