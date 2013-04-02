<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

class ApiTransformDatetime extends ApiTransformBase
{
	/**
	 * Method to transform an internal representation to an external one.
	 *
	 * @param  string   $definition  Field definition.
	 * @param  mixed    $data        Source data.
	 *
	 * @return string Transformed value.
	 */
	public static function toExternal($definition, $data)
	{
		// @TODO Convert MySQL data string to ISO 8601.
		return (string) $definition;
	}

}