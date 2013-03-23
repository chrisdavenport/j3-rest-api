<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

/**
 * A class to help build database queries for services.
 *
 * @package     Joomla.Services
 * @since       3.2
 */
class ApiDatabaseQuery
{
	/*
	 * Pagination information.
	 */
	protected $pagination = array();

	/**
	 * Database object.
	 */
	protected $db = null;

	/**
	 * Constructor.
	 *
	 * @param  object  $db  Database object.
	 */
	public function __construct(JDatabase $db)
	{
		$this->db = $db;

		// Initialise pagination array.
		$this->pagination = array(
			'offset' => 0,
			'page' => 1,
			'perPage' => 10,
		);
	}

	/**
	 * Get single data record.
	 *
	 * Given a base query this method will return the single
	 * data record with the given value of a unique key.
	 *
	 * @param  JDatabaseQuery  $query  A database query object.
	 * @param  integer         $id     Unique key value.
	 * @param  string          $pk     Key name.
	 *
	 * @return object Single resource item object.
	 */
	public function getItem(JDatabaseQuery $query, $id, $pk = 'id')
	{
		// Apply key to query.
		$itemQuery = clone($query);
		$itemQuery->where($this->db->qn($pk) . ' = ' . (int) $id);

		// Retrieve the data.
		$data = $this->db
			->setQuery($itemQuery)
			->loadObject();

		return $data;
	}

	/**
	 * Get page of data.
	 *
	 * Given a base query this method will apply current pagination
	 * variables to return a page of data records.
	 *
	 * @param  JDatabaseQuery  $query  A database query object.
	 *
	 * @return Array of data objects returned by the query.
	 */
	public function getList(JDatabaseQuery $query)
	{
		// Apply sanity check to perPage.
		$this->pagination['perPage'] = min(max($this->pagination['perPage'], 1), 100);

		// Determine total items and total pages.
		$countQuery = clone($query);
		$countQuery->clear('select')->select('count(*)');
		$this->pagination['totalItems'] = (int) $this->db->setQuery($countQuery)->loadResult();
		$this->pagination['totalPages'] = (int) floor(($this->pagination['totalItems']-1)/$this->pagination['perPage']) + 1;

		// Apply sanity check to page number.
		$this->pagination['page'] = min(max($this->pagination['page'], 1), $this->pagination['totalPages']);

		// Calculate base for paginated query.
		$base = ($this->pagination['page'] - 1) * $this->pagination['perPage'] + $this->pagination['offset'];

		// Retrieve the data.
		$data = $this->db
			->setQuery($query, $base, $this->pagination['perPage'])
			->loadObjectList();

		return $data;
	}

	/**
	 * Return array of pagination variables.
	 *
	 * @return array Array of pagination variables.
	 */
	public function getPagination()
	{
		return $this->pagination;
	}

	/**
	 * Set pagination variables.
	 *
	 * @param  array  $pagination  Array of pagination variables.
	 *
	 * @return object  This object may be chained.
	 */
	public function setPagination($pagination = array())
	{
		$this->pagination = array_merge($this->pagination, $pagination);

		return $this;
	}

}
