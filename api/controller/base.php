<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

abstract class ApiControllerBase extends JControllerBase implements ApiController
{
	/**
	 * Database object.
	 */
	protected $db = null;

	/**
	 * Primary table name.
	 */
	protected $tableName = '';

	/**
	 * API query object.
	 */
	protected $apiQuery = null;

	/**
	 * Service object.
	 */
	protected $service = null;

	/**
	 * Service options array.
	 */
	protected $serviceOptions = array();

	/*
	 * Content-Type header.
	 */
	protected $contentType = 'application/hal+json';

	/**
	 * Primary relation.
	 */
	protected $primaryRel = '';

	/**
	 * Execute the request.
	 *
	 * May be overridden in child classes.
	 *
	 */
	public function execute()
	{
		// Get service object.
		$service = $this->getService();

		// Push results into the document.
		$this->app->getDocument()
			->setMimeEncoding($this->contentType)		// Comment this line out to debug
			->setBuffer($service->getHal())
			;
	}

	/**
	 * Get API query object.
	 *
	 * Returns the API query helper object, creating a new
	 * one if it doesn't already exist.
	 * May be overridden in child classes.
	 *
	 * @return ApiDatabaseQuery object;
	 */
	public function getApiQuery()
	{
		if (is_null($this->apiQuery))
		{
			// Get a database query helper object.
			$this->apiQuery = new ApiDatabaseQuery($this->db);
		}

		return $this->apiQuery;
	}

	/**
	 * Get resource data.
	 *
	 * May be overridden in child classes.
	 *
	 * @return object Resource data.
	 */
	public function getData()
	{
	}

	/**
	 * Get database query.
	 *
	 * Returns a new base query for the table name given.
	 * May be overridden in child classes.
	 *
	 * @param  string  $table  Primary table name.
	 *
	 * @return JDatabaseDriver object.
	 */
	public function getQuery($table)
	{
		// Create a database query object.
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->qn($table) . ' as p')
			;

		return $query;
	}

	/**
	 * Get service object.
	 *
	 * May be overridden in child classes.
	 *
	 * @return ApiDatabaseQuery object;
	 */
	public function getService()
	{
		if (is_null($this->service))
		{
			$this->service = new ApiApplicationHalJoomla($this->serviceOptions);
		}

		return $this->service;
	}

	/**
	 * Set the database driver to use.
	 *
	 * @param  JDatabaseDriver  $db  Database driver.
	 *
	 * @return object  This method may be chained.
	 */
	public function setDatabase(JDatabaseDriver $db = null)
	{
		$this->db = isset($db) ? $db : $this->app->getDatabase();

		return $this;
	}

	/**
	 * Set controller options.
	 *
	 * @param array $options Array of controller options.
	 *
	 * @return object  This method may be chained.
	 */
	public function setOptions($options = array())
	{
		// Setup dependencies.
		$this->serviceOptions = (array) $options;

		// Set primary table name.
		if (isset($options['tableName']))
		{
			$this->tableName = $options['tableName'];
		}

		// Set the content type.
		if (isset($options['contentType']))
		{
			$this->contentType = $options['contentType'];
		}

		// Set the primary relation.
		if (isset($options['primaryRel']))
		{
			$this->primaryRel = $options['primaryRel'];
		}

		return $this;

	}

}