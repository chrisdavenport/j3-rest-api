<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

class ApiServicesMenuitemsListGet extends ApiControllerList
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
			'contentType' => 'application/vnd.joomla.list.v1',
			'describedBy' => 'http://docs.joomla.org/Schemas/menuitems/v1',
			'embeddedMap' => __DIR__ . '/embedded.json',
			'primaryRel'  => 'joomla:menuitems',
			'resourceMap' => realpath(__DIR__ . '/../resource.json'),
			'self' => '/joomla:menuitems',
			'tableName'   => '#__menu',
		);

		$this->setOptions($serviceOptions);
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
