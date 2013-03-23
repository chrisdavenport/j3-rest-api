<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

class ApiServicesCategoriesGet extends ApiControllerItem
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
			'contentType' => 'application/vnd.joomla.item.v1; schema=categories.v1',
			'describedBy' => 'http://docs.joomla.org/Schemas/categories/v1',
			'primaryRel'  => 'joomla:categories',
			'resourceMap' => __DIR__ . '/resource.json',
			'self' 		  => '/joomla:categories/' . (int) $this->input->get('id'),
			'tableName'   => '#__categories',
		);

		$this->setOptions($serviceOptions);
	}

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

		// We need to add a link from the current category to the content items that
		// exist within the category.  However, we only know the name of the extension
		// and not the resource name used by the API.  We try to work out the correct
		// entry to make by doing a reverse-lookup on the router maps.
		if (isset($data->extension))
		{
			// Get the component name (without the com_ prefix).
			$extension = str_replace('com_', '', $data->extension);

			// Get the router maps.
			$maps = $this->app->getMaps();

			// Construct the regex pattern of the route we want to find.
			$pattern = '#component/' . $extension . '/(.*)List#';

			// Look for an appropriate route.
			foreach ($maps as $rel => $route)
			{
				if (substr($rel, 0, 17) == 'joomla:categories')
				{
					// Look for a match for our route.
					$matches = array();
					preg_match($pattern, $route, $matches);

					if (!empty($matches))
					{
						// Add a link to the resources associated with the category.
						$linkRel = 'joomla:' . strtolower($matches[1]);
						$linkHref = '/' . str_replace(':catid', $this->id, $rel);
						$service->addLink(new ApiApplicationHalLink($linkRel, $linkHref));
					}
				}
			}
		}

		// Load the data into the HAL object.
		$service->load($data);

		parent::execute();
	}

}
