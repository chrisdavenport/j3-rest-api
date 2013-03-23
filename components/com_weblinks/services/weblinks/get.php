<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

class ComponentWeblinksWeblinksGet extends ApiControllerItem
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
			'contentType' => 'application/vnd.joomla.item.v1; schema=weblinks.v1',
			'describedBy' => 'http://docs.joomla.org/Schemas/weblinks/v1',
			'primaryRel'  => 'joomla:weblinks',
			'resourceMap' => __DIR__ . '/resource.json',
			'self' 		  => '/joomla:weblinks/' . (int) $this->input->get('id'),
			'tableName'   => '#__weblinks',
		);

		$this->setOptions($serviceOptions);
	}

}
