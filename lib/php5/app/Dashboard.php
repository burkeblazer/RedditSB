<?php

class Dashboard {

	/**
	 * Function: getChartDataByDate
	 *
	 * @extern true
	 */
	public static function getDailyChartDataByDate($start_date, $end_date) {
		// Get all days between start and end date inclusive
		$total_bets  = array();
		$total_units = array();
		$percent     = array();
		$plus_minus  = array();
		$labels      = array();
		$dates       = array();
	    $current     = strtotime($start_date);
	    $last        = strtotime($end_date);

	    while($current <= $last) {
	        $dates[] = date('Y-m-d', $current);
	        $current = strtotime('+1 day', $current);
	    }

	    foreach ($dates as $date) {
	    	$labels[]      = $date;
	    	$bet_slips     = BetSlip::getByDate($date, true);
	    	$total_bets[]  = round(BetSlip::getTotalBets (null, true, $bet_slips));
	    	$total_units[] = round(BetSlip::getTotalUnits(null, true, $bet_slips));
	    	$percent[]     = round(BetSlip::getPercent   (null, true, $bet_slips));
	    	$plus_minus[]  = round(BetSlip::getPlusMinus (null, true, $bet_slips));
	    }

	    $data_series = array(
			array('label' => 'Total Bets',  'data' => $total_bets),
			array('label' => 'Total Units', 'data' => $total_units),
			// array('label' => 'Percent',     'data' => $percent),
			array('label' => 'Plus Minus',  'data' => $plus_minus)
	    );

	    return Utility::successTrue(array('labels' => $labels, 'data_series' => $data_series), 'Successfully retrieved dashboard info.');
	}

	/**
	 * Function: getCommentsLikes
	 *
	 * @extern true
	 */
	public static function getCommentsLikes($bet_slip_id) {
		$likes    = Utility::pgQueryParams("SELECT bsl.*, u.name FROM bet_slip_like bsl JOIN \"user\" u ON u.user_id = bsl.user_id WHERE bsl.bet_slip_id = $1", array($bet_slip_id));
		$comments = Utility::pgQueryParams("SELECT bsc.*, u.name FROM bet_slip_comment bsc JOIN \"user\" u ON u.user_id = bsc.user_id WHERE bsc.bet_slip_id = $1 ORDER BY modified ASC", array($bet_slip_id));

		return Utility::successTrue(array('likes' => $likes, 'comments' => $comments), 'Successfully retrieved likes and comments');
	}

	/**
	 * Function: addComment
	 *
	 * @extern true
	 */
	public static function postComment($bet_slip_id, $comment) {
		Utility::pgQueryParams("INSERT INTO bet_slip_comment (bet_slip_id, user_id, comment) VALUES ($1, $2, $3)", array($bet_slip_id, User::$current['user_id'], $comment));

		return Utility::successTrue(null, 'Successfully added comment');
	}

	/**
	 * Function: removeComment
	 *
	 * @extern true
	 */
	public static function removeComment($bet_slip_comment_id) {
		Utility::pgQueryParams("DELETE FROM bet_slip_comment WHERE bet_slip_comment_id = $1 AND user_id = $2", array($bet_slip_comment_id, User::$current['user_id']));

		return Utility::successTrue(null, 'Successfully removed comment.');
	}

	/**
	 * Function: addLike
	 *
	 * @extern true
	 */
	public static function addLike($bet_slip_id) {
		// Remove it if it exists
		Dashboard::removeLike($bet_slip_id);

		Utility::pgQueryParams("INSERT INTO bet_slip_like (bet_slip_id, user_id) VALUES ($1, $2)", array($bet_slip_id, User::$current['user_id']));

		return Utility::successTrue(null, 'Successfully added like');
	}

	/**
	 * Function: removeLike
	 *
	 * @extern true
	 */
	public static function removeLike($bet_slip_id) {
		Utility::pgQueryParams("DELETE FROM bet_slip_like WHERE bet_slip_id = $1 AND user_id = $2", array($bet_slip_id, User::$current['user_id']));

		return Utility::successTrue(null, 'Successfully removed like.');
	}

	/**
	 * Function: getNewsfeed
	 *
	 * @extern true
	 */
	public static function getNewsfeed() {
		// Get bet slips happening today
		//$bet_slips = Utility::pgQueryParams("SELECT bs.*,u.name FROM bet_slip bs JOIN \"user\" u ON u.user_id = bs.user_id WHERE bs.modified::date = '".date('Y-m-d')."'::date ORDER BY modified desc");
		$bet_slips = Utility::pgQueryParams("SELECT bs.*,u.name FROM bet_slip bs JOIN \"user\" u ON u.user_id = bs.user_id WHERE bs.modified::date = '2017-03-17'::date ORDER BY modified desc");

		// Get all bets for each bet slip
		foreach ($bet_slips as &$bet_slip) {
			$bet_slip['bets']     = Heresy::select('*', 'bet',              array('bet_slip_id' => $bet_slip['bet_slip_id']));
			$bet_slip['likes']    = Utility::pgQueryParams("SELECT bsl.*, u.name FROM bet_slip_like bsl JOIN \"user\" u ON u.user_id = bsl.user_id WHERE bsl.bet_slip_id = $1", array($bet_slip['bet_slip_id']));
			$bet_slip['comments'] = Utility::pgQueryParams("SELECT bsc.*, u.name FROM bet_slip_comment bsc JOIN \"user\" u ON u.user_id = bsc.user_id WHERE bsc.bet_slip_id = $1 ORDER BY modified ASC", array($bet_slip['bet_slip_id']));
		}

		return Utility::successTrue($bet_slips, 'Successfully retrieved newsfeed');
	}
}