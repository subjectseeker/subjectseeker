<?php
/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function syncTwitter() {
	global $pages;
	global $sitename;
	global $twitterListId;
	global $twitterListToken;
	global $twitterListTokenSecret;
	
	global $homeUrl;
	$originalUrl = $homeUrl;
	if (!empty($_REQUEST["url"])) {
		$originalUrl = $_REQUEST["url"];
	}
	
	//If logged in, allow synchronization
	$db = ssDbConnect();
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userTwitter = getUserSocialAccount(1, $authUserId, $db);
		
		// If coming from Twitter with the new token, display this.
		if (!empty($_REQUEST['oauth_token'])) {
			if (!empty($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
				$content = "<div class=\"box-title\">Sync</div>
				<p>Session Expired!</p>
				<p><a class=\"white-button\" href=\"".$pages["twitter"]->getAddress(TRUE)."\" title=\"Go to synchronization page\">Retry synchronization</a> <a class=\"white-button\" title=\"Go back to the original page\" href=\"$originalUrl\">Take me back to $sitename</a></p>";
			}
			else {
				// Get Twitter Tokens
				$twitterConnection = getTwitterAuthTokens ($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
				$twitterCredentials = $twitterConnection->getAccessToken();
				
				// Add new user to member list.
				addToTwitterList($twitterCredentials['user_id']);
				// Add Twitter account to database
				addUserSocialAccount (1, $twitterCredentials['screen_name'], $twitterCredentials['oauth_token'], $twitterCredentials['oauth_token_secret'], $authUserId, $db);
				
				$content = "<div class=\"box-title\">Sync</div>
				<p>Your Twitter account has been successfully synced with our system.</p>
				<p><a class=\"white-button\" title=\"Go back to the original page\" href=\"$originalUrl\">Take me back to $sitename</a></p>";
			}
		}
		// Confirm account removal
		elseif (!empty($_REQUEST["remove"]) && $_REQUEST["remove"] == "true") {
			if (!empty($userTwitter)) {
				$content = "<div class=\"box-title\">Sync</div>
				<p>Are you sure you want to remove your Twitter account from our system?</p>
				<p><a class=\"white-button\" title=\"Confirm social network account removal\" href=\"".$pages["twitter"]->getAddress(TRUE)."/?remove=confirmed&amp;url=$originalUrl\">Yes</a> <a class=\"white-button\" title=\"Cancel social network account removal\" href=\"$originalUrl\">No, go back to $sitename</a></p>";
			}
			else {
				$content = "<div class=\"box-title\">Sync</div>
				<p>You don't have any Twitter accounts associated with $sitename</p>
				<p><a class=\"white-button\" href=\"".$pages["twitter"]->getAddress(TRUE)."\" title=\"Go to synchronization page\">Sync my Twitter account</a>  <a class=\"white-button\" title=\"Cancel social network account removal\" href=\"$originalUrl\">Take me back to $sitename</a></p>";
			}
		} elseif (!empty($_REQUEST["remove"]) && $_REQUEST["remove"] == "confirmed") {
			if (!empty($userTwitter)) {
				$content = "<div class=\"box-title\">Sync</div>
				<p>Your Twitter account has been successfully removed from our system.</p>
				<p><a class=\"white-button\" title=\"Go back to the original page\" href=\"$originalUrl\">Take me back to $sitename</a></p>";
				removeUserSocialAccount(1, $authUserId, $db);
			}
		} else {
			if ($userTwitter == TRUE) {
				$content = "<div class=\"box-title\">Sync</div>
				<p>Your Twitter account is already synced with our system, do you want to desync it?.</p>
				<p><a class=\"white-button\" title=\"Confirm social network account removal\" href=\"".$pages["twitter"]->getAddress(TRUE)."/?remove=confirmed&amp;url=$originalUrl\">Yes</a> <a class=\"white-button\" title=\"Go back to the original page\" href=\"$originalUrl\">No, go back to $sitename</a></p>";
			}
			else {
				$twitterUrl = getTwitterAuthURL ($pages["twitter"]->getAddress(TRUE)."/?=".$originalUrl, FALSE);
				
				$content = "<div class=\"box-title\">Sync</div>
				<p>You will now be directed to Twitter, where you can authorize $sitename to view your tweets and update your status, you can remove this authorization at any time. If you enable the Twitter synchronization, you can share your $sitename notes on Twitter and be added to the $sitename member list.</p>
				<p><a class=\"white-button\" title=\"Go back to the Twitter authorization page\" href=\"$twitterUrl\">Continue to the next step</a> <a class=\"white-button\" title=\"Go back to the original page\" href=\"$originalUrl\">Take me back to $sitename</a></p>";
			}
		}
	} elseif (isset($_REQUEST["step"]) && $_REQUEST["step"] == "authUrl") {
		$twitterUrl = getTwitterAuthURL ($pages["login"]->getAddress(TRUE)."/?=".$originalUrl, TRUE);
		header("Location: $twitterUrl");
		
	}
	else {
		$content = "<div class=\"box-title\">Sync</div>
		<p>You must have a $sitename account to use this feature.</p>
		<p><a class=\"white-button\" title=\"Log in page\" href=\"".$pages["login"]->getAddress(TRUE)."\">Log in</a> <a class=\"white-button\" title=\"Go to register page\" href=\"".$pages["register"]->getAddress(TRUE)."\">Register</a> <a class=\"white-button\" title=\"Go back to the original page\" href=\"$originalUrl\">Take me back to $sitename</a></p>";
	}
	
	return $content;
}