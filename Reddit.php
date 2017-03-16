<?php
include_once("config/config.php");
include_once("lib/php5/Gateway.php");
include_once("lib/php5/api/oauth/Client.php");
include_once("lib/php5/api/oauth/IGrantType.php");
include_once("lib/php5/api/oauth/AuthorizationCode.php");

if ($_GET['error']) {print "There was an error authorizing your Reddit account: ".$_GET['error'].'. You will be unable to use this tool without associating your Reddit account.';exit;}

$accessTokenUrl = 'https://ssl.reddit.com/api/v1/access_token';
$userAgent      = 'ChangeMeClient/0.1 by YourUsername';
$redirectUrl    = REDDIT_REDIRECT_URL;
$clientId       = REDDIT_CLIENT_ID;
$clientSecret   = REDDIT_SECRET_KEY;

$client         = new OAuth2\Client($clientId, $clientSecret, OAuth2\Client::AUTH_TYPE_AUTHORIZATION_BASIC);
$client->setCurlOption(CURLOPT_USERAGENT, $userAgent);

$params         = array("code" => $_GET["code"], "redirect_uri" => $redirectUrl);
$response       = $client->getAccessToken($accessTokenUrl, "authorization_code", $params);

$accessTokenResult = $response["result"];
$client->setAccessToken($accessTokenResult["access_token"]);
$client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);

$response = $client->fetch("https://oauth.reddit.com/api/v1/me.json");

User::authorizeUser($response, $_GET['state'], $accessTokenResult["access_token"]);
?>