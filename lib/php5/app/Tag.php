<?php

class Tag {
	/**
	 * Function: getAll
	 *
	 * @extern true
	 */
	public static function getAll() {
		$return_data = array();
		$system_tags = Utility::extractValuesByKey('name', Heresy::select(array('name'), 'user_tag', array('user_id' => null)));
		$user_tags   = Utility::extractValuesByKey('name', Heresy::select(array('name'), 'user_tag', array('user_id' => User::$current['user_id'])));
		$return_data = array_merge($system_tags, $user_tags);

		return Utility::successTrue($return_data, 'Successfully retrieved tags.');
	}
}