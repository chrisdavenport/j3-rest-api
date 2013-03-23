<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

class ApiTransformTarget
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