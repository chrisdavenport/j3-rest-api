<?php

/**
 * Class to represent a HAL standard object.
 *
 */
class ApiApplicationHal
{
	/*
	 * Array of properties of the HAL object.
	 */
	protected $properties = array();

	/**
	 * Add link object.
	 * Link objects with the same rel name are contained in an array.
	 *
	 * @param  HalLink $link  Link object.
	 *
	 * @return Hal This method may be chained.
	 */
	public function addLink(ApiApplicationHalLink $link)
	{
		// If there is no _links property, create it.
		if (!isset($this->properties['_links']))
		{
			$this->properties['_links'] = new stdClass;
		}

		// Get the link relation name.
		$rel = $link->getRel();

		// Add the link to the array of links for the given link relation name.
		if (isset($this->properties['_links']->$rel))
		{
			$this->properties['_links']->$rel = array_merge($this->properties['_links']->$rel, array($link));
		}
		else
		{
			$this->properties['_links']->$rel = array($link);
		}

		return $this;
	}

	/**
	 * Embed objects.
	 *
	 * @param  string  $name  Name (rel) of embedded objects.
	 * @param  array   $data  Array of objects to be embedded.
	 */
	public function embed($name, $data)
	{
		// If there is no _embedded property, create it.
		if (!isset($this->properties['_embedded']))
		{
			$this->properties['_embedded'] = new stdClass;
		}

		$this->properties['_embedded']->$name = $data;

		return $this;
	}

	/**
	 * Method to get the value of a property.
	 *
	 * @param  string  $name     Name of the property.
	 * @param  mixed   $default  Value returned if property does not exist.
	 *
	 * @return mixed Value of the property.
	 */
	public function get($name, $default = null)
	{
		return isset($this->properties[$name]) ? $this->properties[$name] : $default;
	}

	/**
	 * Method to return an object suitable for serialisation.
	 *
	 * @return stdClass A HAL object suitable for serialisation.
	 */
	public function getHal()
	{
		$hal = new stdClass;

		// Links with the same link relation name are stored in an array, but if
		// the array contains only one item, then we drop the array.
		if (isset($this->properties['_links']) && !empty($this->properties['_links']))
		{
			// Get the links object.
			$links = $this->properties['_links'];

			// Convert single entry arrays to direct links.
			foreach ($links as $rel => $link)
			{
				if (is_array($link))
				{
					if (count($link) == 1)
					{
						$links->$rel = $link[0];
					}
					else
					{
						$links->$rel = $this->links[$rel];
					}
				}
			}

			// Replace the links object.
			$this->properties['_links'] = $links;
		}

		// Copy all the properties into the HAL object.
		if (!empty($this->properties))
		{
			foreach ($this->properties as $key => $value)
			{
				$hal->$key = $value;
			}
		}

		return $hal;
	}

	/**
	 * Method to load an object or an array into this HAL object.
	 *
	 * @param  object  $object  Object whose properties are to be loaded.
	 *
	 * @return object This object for chaining.
	 */
	public function load($object)
	{
		foreach ($object as $name => $value)
		{
			// For _links and _embedded, we merge rather than replace.
			if ($name == '_links' || $name == '_embedded')
			{
				$this->properties[$name] = (object) array_merge((array) $this->properties[$name], (array) $value);
			}
			else
			{
				$this->properties[$name] = $value;
			}
		}

		return $this;
	}

	/**
	 * Method to set the value of a property.
	 *
	 * @param  string  $name  Name of the property.
	 * @param  mixed   $value Value of the property.
	 *
	 * @return object  This method may be chained.
	 */
	public function set($name, $value)
	{
		$this->properties[$name] = $value;

		return $this;
	}

}
