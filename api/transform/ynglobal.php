<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

class ApiTransformYNGlobal
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

}