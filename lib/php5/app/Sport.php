<?php

class Sport {
	/**
	 * Function: getAll
	 *
	 * Parameters:
	 * N/A
	 *
	 * Returns:
	 * standard array
	 *
	 * @extern true
	 */
	public static function getAll() {
		$sports = Heresy::select('*', 'sport');

		return Utility::successTrue($sports, 'Successfully retrieved sports');
	}
}