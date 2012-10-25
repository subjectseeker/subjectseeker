<?php
/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function claimSite() {
	global $pages;
	// Connect to DB.
	$db	= ssDbConnect();
	
	preg_match('/(?<=\/claim\/)(\d+)/', $_SERVER["REQUEST_URI"], $matchResult);
	$blogId = $matchResult[1];

	if (empty($blogId)) {
		print "<p class=\"ss-error\">No blog specified to claim.</p>";
		return;
	}

	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$authUserEmail = getUserEmail($authUserId, $db);
		
		$step = NULL;
		if (!empty($_REQUEST["step"])) {
			$step = $_REQUEST["step"];
		}

		// If there is already a verified claim, move ahead to linking things
		if (empty($step) && retrieveVerifiedClaimToken($blogId, $authUserId, $db)) {
			displayUserAuthorLinkForm($blogId, $authUserId, $authUserName, $db);
		} else if (empty($step)) {
			doClaimBlog($blogId, $authUserName, $db);
		} else if ($step === "verify") {
			doVerifyClaim($blogId, $authUserName, $db);
		} else if ($step === "userAuthorLinkForm") {
			doLinkUserAndAuthor($authUserId, $authUserName, $db);
		} else {
			print "ERROR: Unknown step $step.";
		}
	} else {
		$originalUrl = getURL();
		print "<p class=\"ss-warning\">You must <a href=\"".$pages["login"]->getAddress(TRUE)."/?url=$originalUrl\">log in</a> to claim your sites.</p>";
	}
}

?>
