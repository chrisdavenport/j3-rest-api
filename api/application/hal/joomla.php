<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Class to represent a Joomla HAL object.
 *
 * This is a HAL object with some Joomla-specific additional properties.
 */
class ApiApplicationHalJoomla extends ApiApplicationHal
{
	/*
	 * Metadata object.
	 */
	protected $meta = null;

	/*
	 * Page number.
	 */
	protected $page = 1;

	/*
	 * Number of items per page.
	 */
	protected $perPage = 10;

	/*
	 * Page base offset.
	 */
	protected $offset = 0;

	/*
	 * Resource map.
	 */
	protected $resourceMap = null;

	/*
	 * Include map object for embedded resources.
	 */
	protected $includeMap = null;

	/**
	 * Constructor.
	 *
	 * @param  array  $options  Array of configuration options.
	 */
	public function __construct($options = array())
	{
		// Create a metadata object.
		$this->meta = new stdClass;
		$this->meta->apiVersion = '1.0';
		$this->set('_meta', $this->meta);

		// Add standard Joomla namespace as curie.
		$joomlaCurie = new ApiApplicationHalLink('curies', 'http://docs.joomla.org/Link_relations/{rel}');
		$joomlaCurie->name = 'joomla';
		$joomlaCurie->templated = true;
		$this->addLink($joomlaCurie);

		// Add basic hypermedia links.
		$this->addLink(new ApiApplicationHalLink('base', rtrim(JUri::base(), '/')));
		if (isset($options['self']))
		{
			$this->addLink(new ApiApplicationHalLink('self', $options['self']));
		}

		// Set the content type.
		if (isset($options['contentType']))
		{
			$this->setMetadata('contentType', $options['contentType']);
		}

		// Set link to (human-readable) schema documentation.
		if (isset($options['describedBy']))
		{
			$this->setMetadata('describedBy', $options['describedBy']);
		}

		// Load the resource field map (if there is one).
		$resourceMapFile = isset($options['resourceMap']) ? $options['resourceMap'] : '';
		if ($resourceMapFile != '' && file_exists($resourceMapFile))
		{
			$basePath = dirname($options['resourceMap']);
			$this->resourceMap = new ApiApplicationResourcemap(array('basePath' => $basePath));
			$this->resourceMap->fromJson(file_get_contents($resourceMapFile));
		}

		// Load the embedded field map (if there is one).
		$embeddedMapFile = isset($options['embeddedMap']) ? $options['embeddedMap'] : '';
		if ($embeddedMapFile != '' && file_exists($embeddedMapFile))
		{
			// Load the embedded fields list.
			$this->includeMap = new ApiApplicationIncludemap();
			$this->includeMap->fromJson(file_get_contents($embeddedMapFile));
		}
	}

	/**
	 * Import data into HAL object.
	 *
	 * @param  string $name  Name (rel) of data to be imported.
	 * @param  array  $data  Array of data items.
	 *
	 * @return object This object may be chained.
	 */
	public function embed($name, $data)
	{
		// If there is no map then use the standard embed method.
		if (!($this->includeMap instanceof ApiApplicationIncludemap))
		{
			return parent::embed($name, $data);
		}

		// Get list of fields to be included.
		$include = $this->includeMap->toArray();

		// Transform the source data array.
		$resources = array();
		foreach ($data as $key => $datum)
		{
			$resources[$key] = $this->resourceMap->toExternal($datum, $include);
		}

		// Embed data into HAL object.
		parent::embed($name, $resources);

		// Add pagination URI template (per RFC6570).
		$pagesLink = new ApiApplicationHalLink('pages', '/' . $name . '{?fields,offset,page,perPage,sort}');
		$pagesLink->templated = true;
		$this->addLink($pagesLink);

		return $this;
	}

	/**
	 * Method to return an object suitable for serialisation.
	 *
	 * @return stdClass A Joomla HAL object suitable for serialisation.
	 */
	public function getHal()
	{
		$this->set('_meta', $this->meta);

		$hal = parent::getHal();

		return $hal;
	}

	/**
	 * Method to return a metadata field.
	 *
	 * @param  string  $field   Field name.
	 * @param  string  $default Optional default value.
	 *
	 * @return mixed Value of field.
	 */
	public function getMetadata($field, $default = '')
	{
		if (!isset($this->meta->$field))
		{
			return $default;
		}

		return $this->meta->$field;
	}

	/**
	 * Method to return the resource map object.
	 *
	 * @return ApiApplicationResourcemap Resource map object.
	 */
	public function getResourceMap()
	{
		return $this->resourceMap;
	}

	/**
	 * Method to load an object into this HAL object.
	 *
	 * @param  object  $object  Object whose properties are to be loaded.
	 *
	 * @return object This method may be chained.
	 */
	public function load($object)
	{
		// If there is no map then use the standard load method.
		if (empty($this->resourceMap))
		{
			return parent::load($object);
		}

		parent::load($this->resourceMap->toExternal($object));

		return $this;
	}

	/**
	 * Method to add or modify a metadata field.
	 *
	 * @param  string  $field  Field name.
	 * @param  mixed   $value  Value to be assigned to the field.
	 *
	 * @return object  This method may be chained.
	 */
	public function setMetadata($field, $value)
	{
		$this->meta->$field = $value;

		return $this;
	}

	/**
	 * Set pagination variables.
	 *
	 * @param  array  $page  Array of pagination variables.
	 *
	 * @return object  This object may be chained.
	 */
	public function setPagination($page = array())
	{
		if (isset($page['page']))
		{
			$this->meta->page = $page['page'];
		}

		if (isset($page['perPage']))
		{
			$this->meta->perPage = $page['perPage'];
		}

		if (isset($page['offset']))
		{
			$this->meta->offset = $page['offset'];
		}

		if (isset($page['totalItems']))
		{
			$this->meta->totalItems = $page['totalItems'];
		}

		if (isset($page['totalPages']))
		{
			$this->meta->totalPages = $page['totalPages'];
		}

		return $this;
	}

}
