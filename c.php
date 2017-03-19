<?php
header('X-Host: ' . php_uname('n'));

$start_time = time();

include_once("config/config.php");
include_once("lib/php5/Gateway.php");

session_start();
session_name("REDDITSB");
$user_id = $_SESSION['user_id'];

error_reporting(E_ALL);
ini_set('display_errors', 0);
// register_shutdown_function(array("Gateway", "fatalErrorHandler"));

$overrides = array();

if (!(User::validate($user_id) || in_array($_REQUEST['mode'], $overrides)))
{
	$result = array(
		'success' => false,
		'logout'  => true,
		'error'   => 'Session expired'
	);
}
else if ($_REQUEST['mode'] == 'User::logOut')
{
	foreach ($_REQUEST as $key => $value)
	{
		$matches = array();

		if (strpos($value, '%7B%22') !== false)
		{
			$value          = urldecode($value);
			$_REQUEST[$key] = $value;
		}

		if (preg_match('/^json_(.+)$/', $key, $matches))
		{
			$new_key = $matches[1];

			$json = stripcslashes($_REQUEST[$key]);

			if (is_null(json_decode($json, true))) {$json = $_REQUEST[$key];}

			$_REQUEST[$key] = $json;

			$_REQUEST[$new_key] = json_decode($json, true);
		}
		else if ($value === 'true' || $value === 'false')
		{
			$_REQUEST[$key] = ($value === 'true' ? 1 : 0);
		}
	}

	$result = Gateway::executeMethod($user_id, $_REQUEST["mode"], $_REQUEST, $_REQUEST["instance_id"], $_REQUEST['enqueue']);
}
else
{
	session_write_close();

	foreach ($_REQUEST as $key => $value)
	{
		$matches = array();

		if (strpos($value, '%7B%22') !== false)
		{
			$value          = urldecode($value);
			$_REQUEST[$key] = $value;
		}

		if (preg_match('/^json_(.+)$/', $key, $matches))
		{
			$new_key = $matches[1];

			$json = stripcslashes($_REQUEST[$key]);

			if (is_null(json_decode($json, true))) {$json = $_REQUEST[$key];}

			$_REQUEST[$key] = $json;

			$_REQUEST[$new_key] = json_decode($json, true);
		}
		else if ($value === 'true' || $value === 'false')
		{
			$_REQUEST[$key] = ($value === 'true' ? 1 : 0);
		}
	}

	if (isset($_REQUEST['form_name']))
	{
		$form_name = $_REQUEST['form_name'];

		if (isset($_REQUEST[$form_name]))
		{
			$_REQUEST['record'] = $_REQUEST[$form_name];
		}
	}

	try
	{
		if (!isset($_REQUEST["instance_id"]))
		{
			$_REQUEST["instance_id"] = null;
		}

		if (!isset($_REQUEST["enqueue"]))
		{
			$_REQUEST["enqueue"] = null;
		}

		$result = Gateway::executeMethod($user_id, $_REQUEST["mode"], $_REQUEST, $_REQUEST["instance_id"], $_REQUEST['enqueue']);
	}
	catch (Exception $ex)
	{
		// Send back JSON object to be displayed to the user
		$result = array(
			'success' => false,
			'error' => $ex->getMessage()
		);
	}
}

$json = json_encode($result);
$json = str_replace('"status":"t"', '"status":true',  $json);
$json = str_replace('"status":"f"', '"status":false', $json);
print $json;
