<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

/**
 * Resource map object.
 *
 * @package     Joomla.Services
 * @since       3.1
 */
class ApiApplicationResourcemap
{
	/*
	 * Resource map.
	 */
	protected $map = array();

	/*
	 * Base path.
	 *
	 * Custom transform classes should be placed in
	 * a /transform directory at this location.
	 */
	protected $basePath = '';

	/**
	 * Constructor.
	 *
	 * @param  array  $options  Array of configuration options.
	 */
	public function __construct(array $options = array())
	{
		// Set the base path (used to locate custom transform functions).
		$this->basePath = isset($options['basePath']) ? $options['basePath'] : '';
	}

	/**
	 * Load resource map from JSON.
	 *
	 * @param  string JSON-encoded resource map.
	 *
	 * @return object This method may be chained.
	 */
	public function fromJson($json)
	{
		$this->map = json_decode($json, true);

		return $this;
	}

	/**
	 * Method to return the untransformed value associated with a field name.
	 *
	 * @param  string  $fieldName Field name.
	 * @param  mixed   $default   Optional default value.
	 *
	 * @return mixed Value associated with field name.
	 */
	public function getField($fieldName, $default = '')
	{
		return isset($this->map[$fieldName]) ? $this->map[$fieldName] : $default;
	}

	/**
	 * Method to extract a value from the source data.
	 *
	 * @param  string $sourceDefinition Source data field name.
	 * @param  array  $sourceData       Source data.
	 *
	 * @return mixed Requested data field.
	 */
	private function getSourceValue($sourceDefinition, $sourceData)
	{
		// Source definition fields must be in the form type:definition.
		// Locate first occurrence of a colon.
		$pos = strpos($sourceDefinition, ':');

		// Separate type and definition.
		$sourceFieldType = substr($sourceDefinition, 0, $pos);
		$definition = substr($sourceDefinition, $pos+1);

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

		// Transform the value depending on its type.
		$return = $this->transformField($sourceFieldType, $definition, $sourceData);

		return $return;
	}

	/**
	 * Get the name of the transform class for a given field type.
	 *
	 * First looks for the transform class in the /transform directory
	 * in the same directory as the resource.json file.  Then looks
	 * for it in the /api/transform directory.
	 *
	 * @param  string  $fieldType   Field type.
	 *
	 * @return string  Transform class name.
	 */
	private function getTransformClass($fieldType)
	{
		// Cache for the class names.
		static $classNames = array();

		// Cache for component class prefix.
		static $componentPrefix = '';

		// If we already know the class name, just return it.
		if (isset($classNames[$fieldType]))
		{
			return $classNames[$fieldType];
		}

		// Compute the component class prefix if needed.
		// This will be used for component-level overrides.
		if ($componentPrefix == '')
		{
			// Get the path to the resource.json file.
			$path = str_replace(JPATH_BASE, '', realpath($this->basePath));

			// Explode it and make some adjustments.
			$parts = explode('/', $path);

			foreach ($parts as $k => $part)
			{
				$parts[$k] = ucfirst(str_replace('com_', '', $part));
				if ($part == 'components')
				{
					$parts[$k] = 'Component';
				}
				if ($part == 'services')
				{
					unset($parts[$k]);
				}
			}

			$componentPrefix = implode('', $parts);
		}

		// Construct the name of the class to do the transform (default is ApiTransformString).
		$className = $componentPrefix . 'Transform' . ucfirst($fieldType);
		if (!class_exists($className))
		{
			$className = 'ApiTransform' . ucfirst($fieldType);
		}

		// Cache it for later.
		$classNames[$fieldType] = $className;

		return $className;
	}

	/**
	 * Method to get a value from the source data.
	 *
	 * @param  string  $fieldName  Name of the field.
	 * @param  string  $data       Data.
	 *
	 * @return mixed Field value (or null if not found).
	 */
	private function getValue($fieldName, $data)
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
	 * Method to determine if a particular map is available.
	 *
	 * @param  string  $fieldName Field name.
	 *
	 * @return boolean
	 */
	public function isAvailable($fieldName)
	{
		return isset($this->map[$fieldName]);
	}

	/**
	 * Transform a data object to its external representation.
	 *
	 * @param  object  $data     Data object.
	 * @param  array   $include  List of fields to include (if empty then include all fields).
	 *
	 * @return object  External representation of the data object.
	 */
	public function toExternal($data, array $include = array())
	{
		// If there is no map then return the data unmodified.
		if (empty($this->map))
		{
			return $data;
		}

		// Initialise the object store.
		$targetData = array();

		// Run through each mapped field.
		foreach ($this->map as $halField => $sourceDefinition)
		{
			// Check that the field is to be included.
			if (!empty($include) && !in_array($halField, $include))
			{
				continue;
			}

			// Left-hand side (HAL field) must be in the form objectName/name.
			// Note that objectName is optional; default is "main".
			list($halFieldPath, $halFieldName) = explode('/', $halField);

			// If we have a non-empty objectName then make sure we have an object with that name.
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
				$targetData[$targetContext]->$halFieldObject->$halFieldProperty = $this->getSourceValue($sourceDefinition, $data);
			}
			else
			{
				// Extract source data into simple field.
				$targetData[$targetContext]->$halFieldName = $this->getSourceValue($sourceDefinition, $data);
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
	 * Transform a data object to its internal representation.
	 *
	 * @param  object  $data  Data object.
	 *
	 * @return mixed Internal representation of the data object.
	 */
	public function toInternal($data)
	{
		// If there is no map then return the data unmodified.
		if (empty($this->map))
		{
			return $data;
		}

		// @TODO
	}

	/**
	 * Transform a source field data value.
	 *
	 * Calls the static toExternal method of a transform class.
	 *
	 * @param  string  $fieldType   Field type.
	 * @param  string  $definition  Field definition.
	 * @param  string  $data        Data to be transformed.
	 *
	 * @return mixed Transformed data.
	 */
	private function transformField($fieldType, $definition, $data)
	{
		// Get the transform class name.
		$className = $this->getTransformClass($fieldType);

		// Execute the transform.
		if ($className instanceof ApiTransform)
		{
			return $className::toExternal($definition, $data);
		}
		else
		{
			return $definition;
		}
	}
}
