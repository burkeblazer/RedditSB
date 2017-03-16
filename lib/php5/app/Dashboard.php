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
}