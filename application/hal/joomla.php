<?php

/**
 * Class to represent a Joomla HAL object.
 *
 * This is a standard HAL object with some additional properties.
 */
class ApiApplicationHalJoomla extends ApiApplicationHal
{
	/*
	 * Metadata.
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

	/**
	 * Constructor.
	 */
	public function __construct()
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
	 * Import data into HAL object.
	 *
	 * @param  string $name  Name (rel) of data to be imported.
	 * @param  array  $data  Array of data items.
	 * @param  array  $map   Array of maps.
	 *
	 * @return object This object may be chained.
	 */
	public function embed($name, $data, $map = array())
	{
		// If there is no map then use the standard embed method.
		if (empty($map))
		{
			return parent::embed($name, $data);
		}

		// Transform the source data array.
		$resources = array();
		foreach ($data as $key => $datum)
		{
			$resources[$key] = $this->transform($datum, $map);
		}

		// Embed data into HAL object.
		parent::embed($name, $resources);

		// Set pagination properties.
		$this->setMetadata('totalItems', count($resources));
		$this->setMetadata('totalPages', floor(count($resources)/$this->perPage) + 1);

		// Add pagination URI template (per RFC6570).
		$pagesLink = new ApiApplicationHalLink('pages', '/' . $name . '{?fields,offset,page,perPage,sort}');
		$pagesLink->templated = true;
		$this->addLink($pagesLink);

		return $this;
	}

	/**
	 * Method to extract a value from the source data.
	 *
	 * @param  string $sourceDefinition Source data field name.
	 * @param  array  $sourceData       Source data.
	 *
	 * @return mixed Requested data field.
	 */
	protected function getSourceValue($sourceDefinition, $sourceData)
	{
		// Source definition fields must be in the form type:definition.
		// Locate first occurrence of a colon.
		$pos = strpos($sourceDefinition, ':');

		// Separate type and definition.
		$sourceFieldType = substr($sourceDefinition, 0, $pos);
		$definition = substr($sourceDefinition, $pos+1);

		// Construct the name of the method to do the transform (default is toString).
		$methodName = 'transform' . $sourceFieldType;
		$methodName = method_exists($this, $methodName) ? $methodName : 'transformString';

		// Look for source field names.  These are surrounded by curly brackets.
		preg_match_all('/\{(.*)\}/U', $definition, $matches);

		// If the definition contains field names, substitute their values.
		if (!empty($matches[0]))
		{
			foreach ($matches[1] as $key => $fieldName)
			{
				$matches[1][$key] = $this->getValue($fieldName, $sourceData);
			}

			// Replace {fieldName} with value.
			$definition = str_replace($matches[0], $matches[1], $definition);
		}

		// Transform the value depending on its type (default is string).
		$return = $this->$methodName($definition, $sourceData);

		return $return;
	}

	/**
	 * Method to get a value from the source data.
	 *
	 * @param  string  $fieldName  Name of the field.
	 * @param  string  $data       Data.
	 *
	 * @return mixed Field value (or null if not found).
	 */
	protected function getValue($fieldName, $data)
	{
		// Static array of unpacked json fields.
		static $unpacked = array();

		$return = null;

		// Look for an optional field separator in name.
		// The dot separator indicates that the prefix is a json-encoded
		// field, each element of which can be addressed by the suffix.
		if (strpos($fieldName, '.') !== false)
		{
			// Extract the field names.
			list($context, $fieldName) = explode('.', $fieldName);

			// Make sure we have unpacked the json field.
			if (!isset($unpacked[$context]))
			{
				$unpacked[$context] = json_decode($data->$context);
			}

			if (isset($unpacked[$context]->$fieldName))
			{
				$return = $unpacked[$context]->$fieldName;
			}
		}
		else
		{
			// If the field does not exist, return null.
			if (isset($data->$fieldName))
			{
				$return = $data->$fieldName;
			}
		}

		return $return;
	}

	/**
	 * Method to load an object into this HAL object.
	 *
	 * @param  object  $object  Object whose properties are to be loaded.
	 * @param  array   $map     Array of maps.
	 *
	 * @return object This object for chaining.
	 */
	public function load($object, $map = array())
	{
		// If there is no map then use the standard load method.
		if (empty($map))
		{
			return parent::load($object);
		}

		parent::load($this->transform($object, $map));

		return $this;
	}

	/**
	 * Method to transform data using a map.
	 *
	 * @param  object  $sourceData  Source data object.
	 * @param  array   $map         Array of maps.
	 *
	 * @return object Transformed data.
	 */
	public function transform($sourceData, $map = array())
	{
		// If there is no map then return the source data unmodified.
		if (empty($map))
		{
			return $sourceData;
		}

		// Initialise the object store.
		$targetData = array();

		// Run through each mapped field.
		foreach ($map as $halField => $sourceDefinition)
		{
			// Left-hand side (HAL field) must be in the form objectName/name.
			// Note that objectName is optional; default is "main".
			list($halFieldPath, $halFieldName) = explode('/', $halField);

			// If we have a non-empty objectName then make sure we have an object.
			$targetContext = $halFieldPath == '' ? 'main' : $halFieldPath;
			if (!isset($targetData[$targetContext]))
			{
				$targetData[$targetContext] = new stdClass;
			}

			// Look for an optional field separator in name.
			// The dot separator indicates that the prefix is an object
			// and the suffix is a property of that object.
			if (strpos($halFieldName, '.') !== false)
			{
				// Extract the field names.
				list($halFieldObject, $halFieldProperty) = explode('.', $halFieldName);

				// If the object doesn't already exist, create it.
				if (!isset($targetData[$targetContext]->$halFieldObject))
				{
					$targetData[$targetContext]->$halFieldObject = new stdClass;
				}

				// Extract source data into object property.
				$targetData[$targetContext]->$halFieldObject->$halFieldProperty = $this->getSourceValue($sourceDefinition, $sourceData);
			}
			else
			{
				// Extract source data into simple field.
				$targetData[$targetContext]->$halFieldName = $this->getSourceValue($sourceDefinition, $sourceData);
			}
		}

		// Remove any redundant _links.
		if (isset($targetData['_links']))
		{
			foreach ($targetData['_links'] as $k => $link)
			{
				if (!isset($link->href) || $link->href == '')
				{
					unset($targetData['_links']->$k);
				}
			}
		}

		// Move subsidiary objects under main object so it has the right structure when serialised.
		foreach ($targetData as $objName => $object)
		{
			if ($objName != 'main')
			{
				$targetData['main']->$objName = $targetData[$objName];
				unset( $targetData[$objName]);
			}
		}

		return $targetData['main'];
	}

	/**
	 * Method to transform a value to a boolean.
	 *
	 * @param  string   $definition  Field definition.
	 * @param  mixed    $data        Source data.
	 *
	 * @return string Transformed value.
	 */
	protected function transformBoolean($definition, $data)
	{
		if ($definition == 'true')
		{
			return true;
		}

		if ($definition == 'false')
		{
			return false;
		}

		return (boolean) $definition;
	}

	/**
	 * Method to transform a value to a date-time.
	 *
	 * @param  string   $definition  Field definition.
	 * @param  mixed    $data        Source data.
	 *
	 * @return string Transformed value.
	 */
	protected function transformDateTime($definition, $data)
	{
		// @TODO Convert MySQL data string to ISO 8601.
		return (string) $definition;
	}

	/**
	 * Method to transform a value to an integer.
	 *
	 * @param  string   $definition  Field definition.
	 * @param  mixed    $data        Source data.
	 *
	 * @return string Transformed value.
	 */
	protected function transformInt($definition, $data)
	{
		return (int) $definition;
	}

	/**
	 * Method to transform a value to standard state string.
	 *
	 * @param  string   $definition  Field definition.
	 * @param  mixed    $data        Source data.
	 *
	 * @return string Transformed value.
	 */
	protected function transformState($definition, $data)
	{
		switch ($definition)
		{
			case 0:
				$return = 'unpublished';
				break;
			case 1:
				$return = 'published';
				break;
			default:
				$return = 'undefined';
				break;
		}

		return $return;
	}

	/**
	 * Method to transform a value to a string.
	 *
	 * @param  string   $definition  Field definition.
	 * @param  mixed    $data        Source data.
	 *
	 * @return string Transformed value.
	 */
	protected function transformString($definition, $data)
	{
		return (string) $definition;
	}

	/**
	 * Method to transform a value to yes/no/global.
	 *
	 * @param  string   $definition  Field definition.
	 * @param  mixed    $data        Source data.
	 *
	 * @return string Transformed value.
	 */
	protected function transformYNGlobal($definition, $data)
	{
		if ($definition == '')
		{
			return 'global';
		}

		if ($definition == 0)
		{
			return 'no';
		}

		if ($definition == 1)
		{
			return 'yes';
		}

		return 'undefined';
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
	 * @param  integer  $page     Page number (starting from 1).
	 * @param  integer  $perPage  Number of items per page (default 10).
	 * @param  integer  $offset   Offset from 0.
	 *
	 * @return object  This object may be chained.
	 */
	public function setPagination($page = 1, $perPage = 10, $offset = 0)
	{
		$this->meta->page = $page;
		$this->meta->perPage = $perPage;
		$this->meta->offset = $offset;

		return $this;
	}

}
