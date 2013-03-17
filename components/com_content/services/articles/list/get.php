<?php

class ComponentContentArticlesListGet extends JControllerBase
{
	/*
	 * Name of the primary resource.
	 */
	protected $primaryEntity = 'joomla:articles';

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
			'describedBy' => 'http://docs.joomla.org/Schemas/articles/v1',
			'embeddedMap' => __DIR__ . '/embedded.json',
			'resourceMap' => __DIR__ . '/../resource.json',
			'self' => '/' . $this->primaryEntity,
		);

		// Get database object.
		$db = $this->app->getDatabase();

		// Create a database query object.
		$query = $db->getQuery(true)
			->select('*')
			->from('#__content as a')
			;

		// Get a database query helper object.
		$apiQuery = new ApiDatabaseQuery($db);

		// Set pagination variables from input.
		$page = array(
			'offset'  => (int) $this->input->get('offset', 0),
			'page'    => (int) $this->input->get('page', 1),
			'perPage' => (int) $this->input->get('perPage', 10),
		);

		// Get page of data.
		$data = $apiQuery->setPagination($page)->getList($query);

		// Create response object.
		$service = new ApiApplicationHalJoomla($serviceOptions);
		$service->setPagination($apiQuery->getPagination());

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
