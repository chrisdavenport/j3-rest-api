<?php

class ComponentContentArticlesListGet extends JControllerBase
{
	protected $primaryEntity = 'joomla:articles';

	public function execute()
	{
		// Create response object.
		$service = new ComponentContentArticlesApplication;

		// Add basic hypermedia links.
		$service->addLink(new ApiApplicationHalLink('base', rtrim(JUri::base(), '/')));
//		$service->addLink(new ApiApplicationHalLink('self', '/' . $this->primaryEntity));

		// Set basic metadata.
		$contentType = 'application/vnd.joomla.list.v1; schema=articles.v1';
		$service->setMetadata('contentType', $contentType);
		$service->setMetadata('describedBy', 'http://docs.joomla.org/Schemas/articles/v1');

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
			->from('#__content as a')
			;
		$data = $db->setQuery($query, $base, $perPage)->loadObjectList();

		// Load the field map.
		$dataMap = json_decode(file_get_contents(__DIR__ . '/../articles.json'), true);

		// Look for a file containing a list of the fields we want to embed.
		if (file_exists(__DIR__ . '/embedded.json'))
		{
			// Build a new field map.
			$keepMap = array();

			// Load the embedded fields list.
			$embeddedList = json_decode(file_get_contents(__DIR__ . '/embedded.json'), true);

			// The "embedded" array will contain a list of fields names to be retained.
			if (isset($embeddedList['embedded']))
			{
				foreach ($embeddedList['embedded'] as $fieldName)
				{
					if (isset($dataMap[$fieldName]))
					{
						$keepMap[$fieldName] = $dataMap[$fieldName];
					}
				}
			}

			// Swap the field map for our shortened version.
			$dataMap = $keepMap;
		}

		// Import the data into the HAL object.
		$service->embed($this->primaryEntity, $data, $dataMap);

		// Response may be cached.
		$this->app->allowCache(true);

		// Push results into the document.
		$this->app->getDocument()
//			->setMimeEncoding($contentType)		// Comment this line out to debug
			->setBuffer($service->getHal())
			;
	}
}
