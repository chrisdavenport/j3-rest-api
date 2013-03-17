<?php
/**
 * @package     Joomla.Services
 * @subpackage  Database
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * A class to help build database queries for services.
 *
 * @package     Joomla.Services
 * @subpackage  Database
 * @since       3.2
 */
class ApiDatabaseQuery
{
	/*
	 * Page information.
	 */
	protected $page = array();

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
		$this->page = array(
			'offset' => 0,
			'page' => 1,
			'perPage' => 10,
		);
	}

	/**
	 * Get single data record.
	 *
	 * @param  JDatabaseQuery  $query  A database query object.
	 * @param  integer         $id     Record primary key value.
	 * @param  string          $pk     Primary key.
	 *
	 * @return Array of data objects returned by the query.
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
	 * @param  JDatabaseQuery  $query  A database query object.
	 *
	 * @return Array of data objects returned by the query.
	 */
	public function getList(JDatabaseQuery $query)
	{
		// Apply sanity check to perPage.
		$this->page['perPage'] = min(max($this->page['perPage'], 1), 100);

		// Determine total items and total pages.
		$countQuery = clone($query);
		$countQuery->clear('select')->select('count(*)');
		$this->page['totalItems'] = (int) $this->db->setQuery($countQuery)->loadResult();
		$this->page['totalPages'] = (int) floor(($this->page['totalItems']-1)/$this->page['perPage']) + 1;

		// Apply sanity check to page number.
		$this->page['page'] = min(max($this->page['page'], 1), $this->page['totalPages']);

		// Calculate base for paginated query.
		$base = ($this->page['page'] - 1) * $this->page['perPage'] + $this->page['offset'];

		// Retrieve the data.
		$data = $this->db
			->setQuery($query, $base, $this->page['perPage'])
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
		return $this->page;
	}

	/**
	 * Set pagination variables.
	 *
	 * @param  array  $page  Array of pagination variables.
	 *
	 * @return object  This object may be chained.
	 */
	public function setPagination($page = array())
	{
		$this->page = array_merge($this->page, $page);

		return $this;
	}

}
