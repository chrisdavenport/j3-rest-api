<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

class ApiServicesMenuitemsGet extends ApiControllerItem
{
	/**
	 * Constructor.
	 *
	 * @param   JInput            $input  The input object.
	 * @param   JApplicationBase  $app    The application object.
	 */
	public function __construct(JInput $input = null, JApplicationBase $app = null)
	{
		parent::__construct($input, $app);

		// Use the default database.
		$this->setDatabase();

		// Set the controller options.
		$serviceOptions = array(
			'contentType' => 'application/vnd.joomla.item.v1; schema=menuitems.v1',
			'describedBy' => 'http://docs.joomla.org/Schemas/menuitems/v1',
			'primaryRel'  => 'joomla:menuitems',
			'resourceMap' => __DIR__ . '/resource.json',
			'self' 		  => '/joomla:menuitems/' . (int) $this->input->get('id'),
			'tableName'   => '#__menu',
		);

		$this->setOptions($serviceOptions);
	}

	/**
	 * Get a single record from database.
	 *
	 * @return array  Array of data records.
	 */
	public function getData()
	{
		// Get the database query object.
		$query = $this->getQuery($this->tableName);

		// Get a database query helper object.
		$apiQuery = $this->getApiQuery();

		// Get single record from database.
		$data = $apiQuery->getItem($query, (int) $this->input->get('id'), 'm.id');

		return $data;
	}

	/**
	 * Get database query.
	 *
	 * @param  string  $table  Primary table name.
	 *
	 * @return JDatabaseDriver object.
	 */
	public function getQuery($table)
	{
		// Create a database query object.
		$query = $this->db->getQuery(true)
			->select('m.*, mt.id AS menu_id')
			->from('#__menu AS m')
			->leftjoin('#__menu_types AS mt ON m.menutype = mt.menutype')
			;

		return $query;
	}

}
