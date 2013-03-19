<?php
/**
 * @package     Joomla.Services
 * @subpackage  Document
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * ApiDocumentHal class, provides an easy interface to parse and display HAL+JSON output
 *
 * @package     Joomla.Services
 * @subpackage  Document
 * @see         http://stateless.co/hal_specification.html
 * @since       3.1
 */
class ApiDocumentHalJson extends JDocument
{
	/**
	 * Document name
	 *
	 * @var    string
	 * @since  3.1
	 */
	protected $_name = 'joomla';

	/**
	 * Render hrefs as absolute or relative?
	 */
	protected $absoluteHrefs = false;

	/**
	 * Class constructor
	 *
	 * @param   array  $options  Associative array of options
	 *
	 * @since  3.1
	 */
	public function __construct($options = array())
	{
		parent::__construct($options);

		// Set default mime type.
		$this->_mime = 'application/json';

		// Set document type.
		$this->_type = 'hal+json';

		// Set absolute/relative hrefs.
		$this->absoluteHrefs = isset($options['absoluteHrefs']) ? $options['absoluteHrefs'] : false;
	}

	/**
	 * Render the document.
	 *
	 * @param   boolean  $cache   If true, cache the output
	 * @param   array    $params  Associative array of attributes
	 *
	 * @return  The rendered data
	 *
	 * @since  3.1
	 */
	public function render($cache = false, $params = array())
	{
		JResponse::allowCache($cache);
		JResponse::setHeader('Content-disposition', 'attachment; filename="' . $this->getName() . '.json"', true);

		// Unfortunately, the exact syntax of the Content-Type header
		// is not defined, so we have to try to be a bit clever here.
		$contentType = $this->_mime;
		if (stripos($contentType, 'json') === false)
		{
			$contentType .= '+' . $this->_type;
		}
		$this->_mime = $contentType;

		parent::render();

		// Get the HAL object from the buffer.
		$hal = $this->getBuffer();

		// If required, change relative links to absolute.
		if ($this->absoluteHrefs && $hal instanceof ApiApplicationHal)
		{
			// Adjust hrefs in the _links object.
			$this->relToAbs($hal->_links);

			// Adjust hrefs in the _embedded object (if there is one).
			if (isset($hal->_embedded))
			{
				foreach ($hal->_embedded as $rel => $resources)
				{
					foreach ($resources as $id => $resource)
					{
						if (isset($resource->_links))
						{
							$this->relToAbs($resource->_links);
						}
					}
				}
			}
		}

		// Return it as a JSON string.
		return json_encode($hal);
	}

	/**
	 * Returns the document name
	 *
	 * @return  string
	 *
	 * @since  3.1
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Method to convert relative to absolute links.
	 *
	 * @param  object $links  Links object (eg. _links).
	 */
	protected function relToAbs($links)
	{
		// Adjust hrefs in the _links object.
		foreach ($links as $rel => $link)
		{
			if (substr($link->href, 0, 1) == '/')
			{
				$links->$rel->href = rtrim(JUri::base(), '/') . $link->href;
			}
		}
	}

	/**
	 * Sets the document name
	 *
	 * @param   string  $name  Document name
	 *
	 * @return  JDocumentJSON instance of $this to allow chaining
	 *
	 * @since   3.1
	 */
	public function setName($name = 'joomla')
	{
		$this->_name = $name;

		return $this;
	}
}
