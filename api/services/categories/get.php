<?php

class ApiServicesCategoriesGet extends JControllerBase
{
	/*
	 * Name of the primary resource.
	 */
	protected $primaryEntity = 'joomla:categories';

	/*
	 * Content-Type header.
	 */
	protected $contentType = 'application/vnd.joomla.item.v1';

	/**
	 * Execute the request.
	 */
	public function execute()
	{
		// Application options.
		$serviceOptions = array(
			'contentType' => $this->contentType,
			'describedBy' => 'http://docs.joomla.org/Schemas/categories/v1',
			'resourceMap' => __DIR__ . '/resource.json',
		);

		// Get database object.
		$db = $this->app->getDatabase();

		// Create a database query object.
		$query = $db->getQuery(true)
			->select('*')
			->from('#__categories as c')
			;

		// Get a database query helper object.
		$apiQuery = new ApiDatabaseQuery($db);

		// Create response object.
		$service = new ApiApplicationHalJoomla($serviceOptions);
		$service->addLink(new ApiApplicationHalLink($this->primaryEntity, '/' . $this->primaryEntity));

		// Get single record from database.
		$id = (int) $this->input->get('id');
		$data = $apiQuery->getItem($query, $id);

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
						$linkHref = '/' . str_replace(':catid', $id, $rel);
						$service->addLink(new ApiApplicationHalLink($linkRel, $linkHref));
					}
				}
			}
		}

		// Load the data into the HAL object.
		$service->load($data);

		// Response may be cached.
		$this->app->allowCache(true);

		// Push results into the document.
		$this->app->getDocument()
//			->setMimeEncoding($this->contentType)		// Comment this line out to debug
			->setBuffer($service->getHal())
			;
	}
}
