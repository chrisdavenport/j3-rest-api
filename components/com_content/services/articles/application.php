<?php

/**
 * Class to represent a Joomla HAL object.
 *
 * This is a standard HAL object with some additional properties.
 */
class ComponentContentArticlesApplication extends ApiApplicationHalJoomla
{
	/**
	 * Method to transform a value to standard state string.
	 *
	 * @param  string   $definition  Field definition.
	 * @param  mixed    $data        Source data.
	 *
	 * @return string Transformed value.
	 */
	protected function transformPosition($definition, $data)
	{
		switch ($definition)
		{
			case '':
				$return = 'global';
				break;
			case 0:
				$return = 'above';
				break;
			case 1:
				$return = 'below';
				break;
			case 2:
				$return = 'split';
				break;
			default:
				$return = 'undefined';
				break;
		}

		return $return;
	}

}
