<?php

require_once("app/User.php");
require_once("app/Sport.php");
require_once("app/BetSlip.php");
require_once("app/Dashboard.php");
require_once("Utility/Heresy.php");
require_once("Utility/Utility.php");

class DaoInit
{
	public static $instance = null;

	public function __construct()
	{
	}
}

DaoInit::$instance = new DaoInit();
