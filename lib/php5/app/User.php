<?php

class User {
	public static $current = null;

	/**
	 * Function: getUsers
	 *
	 * @extern true
	 */
	public static function getUsers() {
		$return_data = array();
		$users       = Heresy::select(array('user_id', 'name'), 'user');
		foreach ($users as $user) {
			$return_user                = array();
			$return_user['name']        = $user['name'];
			$return_user['total_bets']  = BetSlip::getTotalBets($user['user_id'],  true);
			$return_user['total_units'] = BetSlip::getTotalUnits($user['user_id'], true);
			$return_user['percent']     = BetSlip::getPercent($user['user_id'],    true);
			$return_user['plus_minus']  = BetSlip::getPlusMinus($user['user_id'],  true);

			$return_data[]              = $return_user;
		}

		return Utility::successTrue($return_data, 'Successfully retrieved users.');
	}

	/**
	 * Function: validate
	 *
	 */
	public static function validate($user_id) {
		$user = Heresy::selectOne('*', 'user', array('user_id' => $user_id));

		// Check if the account is disabled
		if ($user['user_id'] && $user['status'] == 'f') {
			return false;
		}
		
		User::$current = $user;
		return true;
	}

	/**
	*
	**/
	public static function authorizeUser($user_data, $auth_key, $access_token) {
		$name = $user_data['result']['name'];

		// See if we already have this user in the db
		list($user_id) = Heresy::selectOne('user_id', 'user', array('name' => $name), true);
		if ($user_id) {
			Heresy::update('user', array('auth_key' => $auth_key, 'access_token' => $access_token), array('user_id' => $user_id));
		}
		else {
			Heresy::insertInto('user', array('name' => $name, 'auth_key' => $auth_key, 'status' => true, 'access_token' => $access_token));
		}

		echo "<script>window.close();</script>";
	}

	/**
	 * Function: logOut
	 *
	 *
	 * @extern true
	 */
	public static function logOut() {
		if (!User::$current) {return Utility::successFalse(null, 'There was an error logging out.');}

		Heresy::update('user', array('auth_key' => '', 'access_token' => '', 'session_id' => '', 'modified' => date('c')), array('user_id' => User::$current['user_id']));

		return Utility::successTrue(null, 'Succesffully logged user out.');
	}
	
	/**
	 * Function: logIn
	 *
	 * Parameters:
	 * $auth_key
	 *
	 * Returns:
	 * standard array
	 *
	 * @extern true
	 */
	public static function logIn($auth_key) {
		$user = Heresy::selectOne('*', 'user', array('auth_key' => $auth_key));

		// Check if the account is disabled
		if ($user['user_id'] && $user['status'] == 'f') {
			return Utility::successFalse(null, 'This account is currently inactive. Please contact support to activate your account.');
		}

		// Make sure the user actually exists
		if (!$user['user_id']) {
			return Utility::successFalse(null, 'Incorrect email address or password - please try again.');
		}

		User::$current = $user;

		// Start new session
		session_name('REDDITSB');
		session_start();
		$_SESSION['user_id']   = User::$current['user_id'];
		$_SESSION['user_data'] = User::$current;

		if (session_id() != '') {
			Heresy::update('user', array('session_id' => session_id(), 'modified' => date('c')), array('user_id' => User::$current['user_id']));
		}

		return Utility::successTrue(User::$current);
	}
}