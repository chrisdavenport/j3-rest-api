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
			'resourceId'  => $this->input->get('id'),
			'resourceMap' => __DIR__ . '/resource.json',
		);

		// Create response object.
		$service = new ApiApplicationHalJoomla($serviceOptions);

		// Construct database query.
		$db = $this->app->getDatabase();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__categories as c')
			->where('id = ' . (int) $service->getResourceId())
			;

		// Retrieve single record from database.
		$data = $db->setQuery($query)->loadObject();

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
