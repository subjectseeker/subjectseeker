<?php

include_once (dirname(__FILE__)."/../globals.php");

// If debug is enabled, show all errors, otherwise, hide them all.
global $debugSite;
if ($debugSite == "true") {
	error_reporting("E_ALL");
	ini_set("display_errors", 1);
}
else {
	error_reporting("E_NONE");
	ini_set("display_errors", 0);
}

// Get basic files for all other scripts to use
global $basedir;
include_once (dirname(__FILE__)."/util.php");
include_once (dirname(__FILE__)."/config.php");

session_start();

global $homeUrl;
global $pages;

// Check if site is in a subfolder
$prefix = parse_url($homeUrl, PHP_URL_PATH);
// Get current page info.
foreach ($pages as $page) {
	$pageAddress = $page->address;
	$currentAddress = parse_url(getURL (),PHP_URL_PATH);
	if (preg_match("~^($prefix)?$pageAddress/?$~", $currentAddress)) {
		$currentPage = $page;
		break;
	}
}

// If page was not found, return 404 page.
if (empty($currentPage)) {
	$currentPage = $pages["404"];
}

// Check if user has a log in cookie
if (!isLoggedIn() && isset($_COOKIE["ss-login"])) {
	$db = ssDbConnect();
	$cookieArray = explode("/", $_COOKIE["ss-login"]);
	$userId = $cookieArray[0];
	$cookieCode = $cookieArray[1];
	$validCookie = validateCookie($userId, $cookieCode, $db);
	$authUser = new auth();
	
	if ($validCookie == TRUE && $authUser->validateUser($userId, $db) == TRUE) {
		createCookie($userId, $db);
	}
}
?>
