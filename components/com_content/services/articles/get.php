<?php

class ComponentContentArticlesGet extends JControllerBase
{
	protected $primaryEntity = 'joomla:articles';

	public function execute()
	{
		// Create response object.
		$service = new ComponentContentArticlesApplication;

		// Add basic hypermedia links.
		$service->addLink(new ApiApplicationHalLink('base', rtrim(JUri::base(), '/')));

		// Set basic metadata.
		$contentType = 'application/vnd.joomla.list.v1; schema=articles.v1';
		$service->setMetadata('contentType', $contentType);
		$service->setMetadata('describedBy', 'http://docs.joomla.org/Schemas/articles/v1');

		// Get article id from input.
		$article_id = $this->input->get('article_id');

		// Construct database query.
		$db = $this->app->getDatabase();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__content as a')
			->where('id = ' . (int) $article_id)
			;

		// Retrieve single record from database.
		$data = $db->setQuery($query)->loadObject();

		// Load the field map.
		$dataMap = json_decode(file_get_contents(__DIR__ . '/articles.json'), true);

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
