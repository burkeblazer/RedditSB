<?php

class BetSlip {
	/**
	 * Function: getByDate
	 *
	 * Parameters:
	 * $date
	 *
	 * Returns:
	 * standard array
	 *
	 * @extern true
	 */
	public static function getByDate($date, $suppress_message = false, $user_id = null) {
		if (!$user_id) {$user_id = User::$current['user_id'];}

		// Get all bet slips for date
		$bet_slips = Utility::pgQueryParams("
			SELECT
				 bs.*
			
			FROM
				 bet_slip bs

			WHERE
				 bs.user_id = $1 AND bs.slip_date = $2

			ORDER BY bs.modified DESC;
		", array($user_id, $date));

		// Get all bets for each bet slip
		foreach ($bet_slips as &$bet_slip) {
			$bet_slip['bets'] = Heresy::select('*', 'bet', array('bet_slip_id' => $bet_slip['bet_slip_id']));
		}

		if ($suppress_message) {return $bet_slips;}

		return Utility::successTrue($bet_slips, 'Successfully retrieved bet slips for user by date');
	}

	/**
	 * Function: getAll
	 *
	 * Parameters:
	 * $date
	 *
	 * Returns:
	 * standard array
	 *
	 * @extern true
	 */
	public static function getAll($public_only = false, $suppress_message =  false, $user_id = null) {
		if (!$user_id) {$user_id = User::$current['user_id'];}

		// Get all bet slips for date
		$bet_slips = Utility::pgQueryParams("
			SELECT
				 bs.*
			
			FROM
				 bet_slip bs

			WHERE
				 bs.user_id = $1

			ORDER BY bs.modified DESC;
		", array($user_id));

		// Get all bets for each bet slip
		foreach ($bet_slips as &$bet_slip) {
			$bet_slip['bets'] = Heresy::select('*', 'bet', array('bet_slip_id' => $bet_slip['bet_slip_id']));
		}

		if ($suppress_message) {return $bet_slips;}

		return Utility::successTrue($bet_slips, 'Successfully retrieved bet slips for user');
	}

	/**
	 * Function: delete
	 *
	 * Parameters:
	 * $bets
	 *
	 * Returns:
	 * standard array
	 *
	 * @extern true
	 */
	public static function delete($bet_slip_id) {
		// Clear out any existing bets
		Utility::pgQueryParams("DELETE FROM bet WHERE bet_slip_id = $1",      array($bet_slip_id));
		Utility::pgQueryParams("DELETE FROM bet_slip WHERE bet_slip_id = $1", array($bet_slip_id));

		return Utility::successTrue(null, 'Successfully deleted bet slip.');
	}

	/**
	 * Function: saveBets
	 *
	 * Parameters:
	 * $bets
	 *
	 * Returns:
	 * standard array
	 *
	 */
	public static function saveBets($bets, $bet_slip_id) {
		// Clear out any existing bets
		Utility::pgQueryParams("DELETE FROM bet WHERE bet_slip_id = $1", array($bet_slip_id));

		// Add in the new bets
		foreach ($bets as $bet) {
			$bet['bet_slip_id'] = $bet_slip_id;
			$bet['matches']     = json_encode($bet['matches']);
			Heresy::insertInto('bet', $bet);
		}
	}

	/**
	 * Function: saveBetSlip
	 *
	 * Parameters:
	 * $data
	 *
	 * Returns:
	 * standard array
	 *
	 * @extern true
	 */
	public static function saveBetSlip($data, $date, $bet_slip_id = null) {
		// If one previously exists, delete it
		if ($bet_slip_id) {BetSlip::delete($bet_slip_id);}

		// Insert the bet slip with the new information
		$update            = ($bet_slip_id);
		$bets              = $data['bets'];
		unset($data['bets']);
		$data['slip_date'] = $date;
		$data['public']    = ($data['public']) ? 1 : 0;
		$data['user_id']   = User::$current['user_id'];
		list($bet_slip_id) = Heresy::insertInto('bet_slip', $data, 'bet_slip_id', true);

		// Do the Bets
		BetSlip::saveBets($bets, $bet_slip_id);

		return Utility::successTrue($bet_slip_id, 'Successfully '.(($update) ? ' updated ' : ' created '). 'bet slip.');
	}

	/**
	 * Function: getTotalBets
	 *
	 * @extern true
	 */
	public static function getTotalBets($user_id = null, $suppress_message = false, $bet_slips = array()) {
		if (!$user_id) {$user_id = User::$current['user_id'];}
		$total_bets              = 0;
		if (!$bet_slips) {
			$bet_slips           = BetSlip::getAll(true, true, $user_id);
		}
		foreach ($bet_slips as $public_bet) {
			$total_bets += count($public_bet['bets']);
		}

		if ($suppress_message) {return $total_bets;}

		return Utility::successTrue($total_bets, 'Successfully retrieved total bets.');
	}

	/**
	 * Function: getTotalUnits
	 *
	 * @extern true
	 */
	public static function getTotalUnits($user_id = null, $suppress_message = false, $bet_slips = array()) {
		if (!$user_id) {$user_id = User::$current['user_id'];}
		$total_units             = 0;
		if (!$bet_slips) {
			$bet_slips           = BetSlip::getAll(true, true, $user_id);
		}
		foreach ($bet_slips as $public_bet) {
			foreach ($public_bet['bets'] as $bet) {
				$total_units += $bet['units_bet'];
			}
		}

		if ($suppress_message) {return $total_units;}

		return Utility::successTrue($total_units, 'Successfully retrieved total units.');
	}

	/**
	 * Function: getPercent
	 *
	 * @extern true
	 */
	public static function getPercent($user_id = null, $suppress_message = false, $bet_slips = array()) {
		if (!$user_id) {$user_id = User::$current['user_id'];}
		$total_bets              = 0;
		$total_wins              = 0;
		$percent                 = 0;
		if (!$bet_slips) {
			$bet_slips           = BetSlip::getAll(true, true, $user_id);
		}
		foreach ($bet_slips as $public_bet) {
			foreach ($public_bet['bets'] as $bet) {
				if ($bet['outcome'] == 'TBD') {continue;}
				if ($bet['outcome'] == 'WIN') {$total_bets++;$total_wins++;}
				else {$total_bets++;}
			}
		}

		if ($total_bets == 0) {if ($suppress_message) {return $percent;}return Utility::successTrue($percent, 'Successfully retrieved percent.');}

		$percent = ($total_wins/$total_bets)*100;

		if ($suppress_message) {return $percent;}

		return Utility::successTrue($percent, 'Successfully retrieved percent.');
	}

	/**
	 * Function: getPlusMinus
	 *
	 * @extern true
	 */
	public static function getPlusMinus($user_id = null, $suppress_message = false, $bet_slips = array()) {
		if (!$user_id) {$user_id = User::$current['user_id'];}
		$total_units             = 0;
		if (!$bet_slips) {
			$bet_slips           = BetSlip::getAll(true, true, $user_id);
		}

		foreach ($bet_slips as $public_bet) {
			foreach ($public_bet['bets'] as $bet) {
				if ($bet['outcome'] == 'TBD') {continue;}
				if ($bet['outcome'] == 'WIN') {$total_units += $bet['units_to_win'];}
				else {$total_units -= $bet['units_to_win'];}
			}
		}

		if ($suppress_message) {return $total_units;}

		return Utility::successTrue($total_units, 'Successfully retrieved plus minus.');
	}
}