<?php
/**
 * @package     Joomla.Services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Object to represent a hypermedia link in HAL.
 */
class ApiApplicationHalLink
{
	/*
	 * Rel (relation) value.
	 */
	protected $rel = '';

	/*
	 * URL to link to.
	 */
	public $href = '';

	/**
	 * Constructor.
	 *
	 * @param  string  $rel  Rel (relation).
	 * @param  string  $href URL to link to.
	 */
	public function __construct($rel, $href = '')
	{
		$this->rel = $rel;
		$this->href = $href;
	}

	/**
	 * Returns rel value.
	 */
	public function getRel()
	{
		return $this->rel;
	}
}
