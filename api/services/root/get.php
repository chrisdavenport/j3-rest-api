<?php

class ApiServicesRootGet extends JControllerBase
{
	public function execute()
	{
		// Create response object.
		$service = new ApiApplicationHalJoomla;

		// Add basic hypermedia links.
		$service->addLink(new ApiApplicationHalLink('base', rtrim(JUri::base(), '/')));
		$service->addLink(new ApiApplicationHalLink('self', '/'));

		// Set basic metadata.
		$contentType = 'application/vnd.joomla.service.v1';
		$service->setMetadata('contentType', $contentType);
		$service->setMetadata('describedBy', 'http://docs.joomla.org/Schemas/service/v1');

		// Look for the top-level resources and add them as links.
		foreach ($this->app->getMaps() as $route => $map)
		{
			if (strpos($route, '/') === false)
			{
				$service->addLink(new ApiApplicationHalLink($route, '/' . $route));
			}
		}

		// Response may be cached.
		$this->app->allowCache(true);

		// Push results into the document.
		$this->app->getDocument()
//			->setMimeEncoding('application/vnd.joomla.service.v1')		// Comment this line out to debug
			->setBuffer($service->getHal())
			;
	}
}
