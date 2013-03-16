<?php

class ApiServicesCategoriesListGet extends JControllerBase
{
	/*
	 * Name of the primary resource.
	 */
	protected $primaryEntity = 'joomla:categories';

	/*
	 * Content-Type header.
	 */
	protected $contentType = 'application/vnd.joomla.list.v1';

	/**
	 * Execute the request.
	 */
	public function execute()
	{
		// Application options.
		$serviceOptions = array(
			'contentType' => $this->contentType,
			'describedBy' => 'http://docs.joomla.org/Schemas/categories/v1',
			'embeddedMap' => __DIR__ . '/embedded.json',
			'resourceMap' => __DIR__ . '/../resource.json',
		);

		// Create response object.
		$service = new ApiApplicationHalJoomla($serviceOptions);

		// Add basic hypermedia links.
		$service->addLink(new ApiApplicationHalLink('self', '/' . $this->primaryEntity));

		// Set pagination.
		$offset = 0;
		$page = 1;
		$perPage = 10;
		$base = ($page - 1) * $perPage + $offset;
		$service->setPagination($page, $perPage, $offset);

		// Query the database.
		$db = $this->app->getDatabase();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__categories as c')
			;
		$data = $db->setQuery($query, $base, $perPage)->loadObjectList();

		// Import the data into the HAL object.
		$service->embed($this->primaryEntity, $data);

		// Response may be cached.
		$this->app->allowCache(true);

		// Push results into the document.
		$this->app->getDocument()
//			->setMimeEncoding($this->contentType)		// Comment this line out to debug
			->setBuffer($service->getHal())
			;
	}
}
