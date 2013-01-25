<?php

include_once (dirname(__FILE__)."/../config/globals.php");
global $homeUrl;
global $pages;
global $debugSite;

// If debug is enabled, show all errors, otherwise, hide them all.
if ($debugSite == "true") {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
} else {
	error_reporting(E_NONE);
	ini_set("display_errors", 0);
}

// Get basic files for all other scripts to use
include_once (dirname(__FILE__)."/util.php");
include_once (dirname(__FILE__)."/config.php");

session_start();

$currentPage = $pages["404"];
// Check if site is in a subfolder
$prefix = parse_url($homeUrl, PHP_URL_PATH);
// Get current page info.
foreach ($pages as $page) {
	$pageAddress = $page->address;
	$currentAddress = parse_url(getURL(),PHP_URL_PATH);
	if (preg_match("~^($prefix)?$pageAddress/?$~", $currentAddress)) {
		$currentPage = $page;
		break;
	}
}

// Check if user has a log in cookie
if (!isLoggedIn() && isset($_COOKIE["ss-login"])) {
	$db = ssDbConnect();
	$cookieArray = explode("/", $_COOKIE["ss-login"]);
	$userId = $cookieArray[0];
	$cookieCode = $cookieArray[1];
	$validCookie = validateCookie($userId, $cookieCode, $db);
	
	$userName = getUserName($userId, $db);
	$authUser = new auth();
	$userId = $authUser->validateUser($userName);
	if ($validCookie == TRUE && !$authUser->errors) {
		$authUser->loginUser($userId, TRUE);
	}
}
?>
