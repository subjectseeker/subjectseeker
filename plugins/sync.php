<?php
/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function syncPage() {
	global $homeUrl, $sitename, $pages;
	$db = ssDbConnect();
	
	$originalUrl = $homeUrl;
	if (isset($_REQUEST["url"])) {
		$originalUrl = $_REQUEST["url"];
	}
	
	if (isset($_REQUEST["step"]) && $_REQUEST["step"] == "twitterAuth") {
		$callbackUrl = $_REQUEST["callback"]."/?url=".$originalUrl;
		$twitterAuthUrl = getTwitterAuthURL($callbackUrl, TRUE);
		header("Location: $twitterAuthUrl");
		
	} elseif (isset($_REQUEST["step"]) && $_REQUEST["step"] == "googleAuth") {
		$callbackUrl = $_REQUEST["callback"];
		$googleAuthUrl = "https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=490557068973.apps.googleusercontent.com&redirect_uri=$callbackUrl&scope=https://www.googleapis.com/auth/userinfo.email+https://www.googleapis.com/auth/userinfo.profile&access_type=offline";
		header("Location: $googleAuthUrl");
	}
	
	$content = "<div class=\"box-title\">Sync</div>";
	
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
	} else {
		$content .= "<p>You must have a $sitename account to use this feature.</p>
		<p><a class=\"white-button\" href=\"".$pages["login"]->getAddress(TRUE)."/?url=$originalUrl\">Log in</a> <a class=\"white-button\" href=\"".$pages["register"]->getAddress(TRUE)."\">Register</a> <a class=\"white-button\" title=\"Go back to the original page\" href=\"$originalUrl\">Take me back to $sitename</a></p>";
		
		return $content;
	}
	
	if (isset($_REQUEST["oauth_token"])) {
		if ($_SESSION["oauth_token"] !== $_REQUEST["oauth_token"]) {
			$content .= "<p>Session Expired!</p>
			<p><a class=\"white-button\" href=\"".$pages["sync"]->getAddress(TRUE)."/?url=$originalUrl\">Retry synchronization</a> <a class=\"white-button\" href=\"$originalUrl\">Take me back to $sitename</a></p>";
		} else {
			// Get Twitter Tokens
			$accessToken = getTwitterAuthTokens($_SESSION["oauth_token"], $_SESSION["oauth_token_secret"], $_REQUEST["oauth_verifier"]);
			
			if (isset($accessToken["user_id"])) {
				$twitterUserDetails = getTwitterUserDetails($accessToken["user_id"], NULL, $accessToken["oauth_token"], $accessToken["oauth_token_secret"]);

				// Add new user to member list.
				addToTwitterList($accessToken["user_id"]);

				// Add Twitter account to database
				addSocialNetworkUser(1, $accessToken["user_id"], $accessToken["screen_name"], $twitterUserDetails->profile_image_url, $authUserId, NULL, $accessToken["oauth_token"], $accessToken["oauth_token_secret"], NULL, $db);

				$content .= "<p>Your Twitter account has been successfully synced with our system.</p>
				<p><a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a></p>";
			} else {
				$content .= "<p class=\"ss-error\">There was a problem trying to sync you account.</p>
				<a class=\"white-button\" href=\"".$pages["sync"]->getAddress(TRUE)."\">Retry</a> <p><a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a></p>";
			}

		}
	} elseif (isset($_REQUEST["code"])) {
		$oauthRefreshToken = NULL;
		if (isset($googleTokens->refresh_token)) {
			$oauthRefreshToken = $googleTokens->refresh_tokens;
		}
		
		$code = $_REQUEST["code"];
		$googleTokens = getGoogleTokens($code, $pages["sync"]->getAddress(TRUE));
		if (isset($googleTokens->access_token)) {
			$oauthToken = $googleTokens->access_token;
			
			$googleUser = getGoogleUser($oauthToken);
			$socialNetworkUserExtId = $googleUser->id;
			$socialNetworkUserName = $googleUser->name;
			$socialNetworkUserAvatar = $googleUser->picture;
			
			addSocialNetworkUser(3, $socialNetworkUserExtId, $socialNetworkUserName, $socialNetworkUserAvatar, $authUserId, NULL, $oauthToken, NULL, $oauthRefreshToken, $db);
			
			$content .= "<p>Your account has been synced.</p>
			<p><a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a></p>";
		} else {
			$content .= "<p>Invalid token.</p>
			<p><a class=\"white-button\" href=\"".$pages["sync"]->getAddress(TRUE)."\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a></p>";
		}
	
	} elseif (isset($_REQUEST["remove"]) && isset($_REQUEST["network"])) {
		$socialNetworkName = $_REQUEST["network"];
		if ($socialNetworkName == "twitter")
			$socialNetworkId = 1;
		elseif ($socialNetworkName == "google")
			$socialNetworkId = 3;
		
		$socialNetworkUser = getSocialNetworkUser($socialNetworkId, $authUserId, "userId", $db);
		if ($socialNetworkUser) {
			$content .= "<p>Your $socialNetworkName account has been successfully removed from our system.</p>
			<p><a class=\"white-button\" title=\"Go back to the original page\" href=\"$originalUrl\">Take me back to $sitename</a></p>";
			unlinkSocialNetworkUser($socialNetworkId, $authUserId, $db);
		} else {
			$content .= "<p>You don't have any $socialNetworkName accounts associated with $sitename</p>
			<p><a class=\"white-button\" href=\"".$pages["sync"]->getAddress(TRUE)."\" title=\"Go to synchronization page\">Sync my Twitter account</a>	<a class=\"white-button\" title=\"Cancel social network account removal\" href=\"$originalUrl\">Take me back to $sitename</a></p>";
		}
	} else {
		$content .= "<p><a class=\"twitter-button\" href=\"".$pages["sync"]->getAddress(TRUE)."/?step=twitterAuth&amp;callback=".$pages["sync"]->getAddress(TRUE)."&amp;url=".$originalUrl."\">Sync Twitter</a></p>
		<p><a class=\"google-button\" href=\"".$pages["sync"]->getAddress(TRUE)."/?step=googleAuth&amp;callback=".$pages["sync"]->getAddress(TRUE)."&amp;url=".$originalUrl."\">Sync Google</a></p>";
	}
	
	return $content;
}