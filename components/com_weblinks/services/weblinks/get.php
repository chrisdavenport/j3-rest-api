<?php

class ComponentWeblinksWeblinksGet extends JControllerBase
{
	protected $primaryEntity = 'joomla:weblinks';

	public function execute()
	{
		// Create response object.
		$service = new ApiApplicationHalJoomla;

		// Add basic hypermedia links.
		$service->addLink(new ApiApplicationHalLink('base', rtrim(JUri::base(), '/')));

		// Set basic metadata.
		$contentType = 'application/vnd.joomla.list.v1; schema=weblinks.v1';
		$service->setMetadata('contentType', $contentType);
		$service->setMetadata('describedBy', 'http://docs.joomla.org/Schemas/weblinks/v1');

		// Get article id from input.
		$article_id = $this->input->get('weblink_id');

		// Construct database query.
		$db = $this->app->getDatabase();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__weblinks as w')
			->where('id = ' . (int) $article_id)
			;

		// Retrieve single record from database.
		$data = $db->setQuery($query)->loadObject();

		// Load the field map.
		$dataMap = json_decode(file_get_contents(__DIR__ . '/resource.json'), true);

		// Load the data into the HAL object.
		$service->load($data, $dataMap);

		// Response may be cached.
		$this->app->allowCache(true);

		// Push results into the document.
		$this->app->getDocument()
//			->setMimeEncoding($contentType)		// Comment this line out to debug
			->setBuffer($service->getHal())
			;
	}
}
