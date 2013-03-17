<?php

class ApiServicesMenuitemsGet extends JControllerBase
{
	/*
	 * Name of the primary resource.
	 */
	protected $primaryEntity = 'joomla:menuitems';

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
			'describedBy' => 'http://docs.joomla.org/Schemas/menuitems/v1',
			'resourceMap' => __DIR__ . '/resource.json',
		);

		// Get database object.
		$db = $this->app->getDatabase();

		// Create a database query object.
		$query = $db->getQuery(true)
			->select('m.*, mt.id AS menu_id')
			->from('#__menu AS m')
			->leftjoin('#__menu_types AS mt ON m.menutype = mt.menutype')
			;

		// Get a database query helper object.
		$apiQuery = new ApiDatabaseQuery($db);

		// Create response object.
		$service = new ApiApplicationHalJoomla($serviceOptions);
		$service->addLink(new ApiApplicationHalLink($this->primaryEntity, '/' . $this->primaryEntity));

		// Get single record from database.
		$data = $apiQuery->getItem($query, (int) $this->input->get('id'), 'm.id');

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
