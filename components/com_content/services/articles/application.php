<?php

/**
 * Class to represent a Joomla HAL object.
 *
 * This is a standard HAL object with some additional properties.
 */
class ComponentContentArticlesApplication extends ApiApplicationHalJoomla
{
	/**
	 * Method to transform a value to a float string.
	 *
	 * @param  string   $definition  Field definition.
	 * @param  mixed    $data        Source data.
	 *
	 * @return string Transformed value.
	 */
	protected function transformFloat($definition, $data)
	{
		switch ($definition)
		{
			case '':
				$return = 'global';
				break;

			case 'left':
			case 'right':
			case 'none':
				$return = $definition;
				break;

			default:
				$return = 'undefined';
				break;
		}

		return $return;
	}

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

	/**
	 * Method to transform a value to standard target string.
	 *
	 * @param  string   $definition  Field definition.
	 * @param  mixed    $data        Source data.
	 *
	 * @return string Transformed value.
	 */
	protected function transformTarget($definition, $data)
	{
		switch ($definition)
		{
			case '':
				$return = 'global';
				break;
			case 0:
				$return = 'parent';
				break;
			case 1:
				$return = 'new';
				break;
			case 2:
				$return = 'popup';
				break;
			case 3:
				$return = 'modal';
				break;
			default:
				$return = 'undefined';
				break;
		}

		return $return;
	}

}
