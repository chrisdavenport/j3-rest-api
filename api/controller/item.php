<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

abstract class ApiControllerItem extends ApiControllerBase
{
	/*
	 * Unique key value.
	 */
	protected $id = 0;

	/**
	 * Execute the request.
	 */
	public function execute()
	{
		// Get resource item id from input.
		$this->id = (int) $this->input->get('id');

		// Get resource item data.
		$data = $this->getData();

		// Get service object.
		$service = $this->getService();

		// Load the data into the HAL object.
		$service->load($data);

		parent::execute();
	}

	/**
	 * Get data for a single resource item.
	 *
	 * @return object Single resource item object.
	 */
	public function getData()
	{
		// Get the database query object.
		$query = $this->getQuery($this->tableName);

		// Get a database query helper object.
		$apiQuery = $this->getApiQuery();

		// Get single record from database.
		$data = $apiQuery->getItem($query, (int) $this->id);

		return $data;
	}

}