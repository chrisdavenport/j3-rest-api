<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

class ApiTransformFloat
{
	/**
	 * Method to transform a value to a string.
	 *
	 * @param  string   $definition  Field definition.
	 * @param  mixed    $data        Source data.
	 *
	 * @return string Transformed value.
	 */
	public static function execute($definition, $data)
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

}