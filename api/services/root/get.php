<?php

class ApiServicesRootGet extends JControllerBase
{
	/*
	 * Content-Type header.
	 */
	protected $contentType = 'application/vnd.joomla.service.v1';

	/**
	 * Execute the request.
	 */
	public function execute()
	{
		// Application options.
		$serviceOptions = array(
			'contentType' => $this->contentType,
			'self' => '/',
		);

		// Create response object.
		$service = new ApiApplicationHalJoomla($serviceOptions);

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
//			->setMimeEncoding($this->contentType)		// Comment this line out to debug
			->setBuffer($service->getHal())
			;
	}
}
