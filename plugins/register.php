<?php
/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function displayRegistration() {
	global $homeUrl;
	global $pages;
	$db = ssDbConnect();
	
	$originalUrl = $homeUrl;
	if (!empty($_GET["url"])) {
		$originalUrl = $_GET["url"];	
	}
	
	// If logged in, send to log in page to deal with this.
	if (isLoggedIn()) {
		header("Location: ".$pages["login"]->getAddress(TRUE)."/?url=$originalUrl");
	} else {
		$content = "<div class=\"box-title\">Create Account</div>";
		include_once(dirname(__FILE__)."/../third-party/recaptcha/recaptchalib.php");
		
		$userName = NULL;
		$userEmail = NULL;
		$userPass1 = NULL;
		$userPass2 = NULL;
		
		// Check if user has submitted information
		if (isset($_POST['user-name'])) {
			$userName = mysql_real_escape_string($_POST['user-name']);
			$userEmail = mysql_real_escape_string($_POST['email']);
			$userPass1 = $_POST["pass1"];
			$userPass2 = $_POST["pass2"];
			
			$errors = checkUserData(NULL, NULL, $userName, $userName, $userEmail, NULL, $userPass1, $userPass2, $db);
			
			if (empty($userName)) {
				$errors .= "<p class=\"ss-error\">Please submit a user name.</p>";
			}
			if (empty($userEmail)) {
				$errors .= "<p class=\"ss-error\">Please submit an email.</p>";
			}
			if (empty($userPass1) || empty($userPass2)) {
				$errors .= "<p class=\"ss-error\">Please submit your desired password and confirmation password.</p>";
			}
			if ((isset($_SESSION["validCaptcha"]) == FALSE || (isset($_SESSION["validCaptcha"]) && $_SESSION["validCaptcha"] != "true")) && (isset($_SESSION["regStep"]) == FALSE || (isset($_SESSION["regStep"]) && $_SESSION["regStep"] != "two"))) {
				global $recaptchaPrivateKey;
				$resp = recaptcha_check_answer ($recaptchaPrivateKey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
				if (!$resp->is_valid) {
					$errors .= "<p class=\"ss-error\">Submitted captcha code is invalid, please try again.</p>";
				} else {
					$_SESSION["validCaptcha"] = "true";
				}
			}
			
			if (!empty($errors)) {
				if (isset($_SESSION["regStep"]) && $_SESSION["regStep"] == "two") {
					$_SESSION["regStep"] = "one";
				}
				$content .= "$errors";
			} else {
				$hashedPass = hashPassword($userPass1);
				$userId = addUser($userName, $userEmail, $hashedPass, $db);
				editUserStatus ($userId, 3, $db);
				$userDisplayName = $userName;
				
				// Check if Twitter details have been imported
				if (isset($_SESSION["regStep"]) && $_SESSION["regStep"] == "two") {
					$socialNetworkId = $_SESSION["socialNetworkId"];
					$socialNetworkUserExtId = $_SESSION["userId"];
					$socialNetworkUserName = $_SESSION["userName"];
					$oauthToken = $_SESSION["oauthToken"];
					$oauthSecretToken = $_SESSION["oauthSecretToken"];
					$oauthRefreshToken = $_SESSION["oauthRefreshToken"];
					
					if ($socialNetworkId == 1) {
						addToTwitterList($socialNetworkUserExtId);
						$twitterUser = getTwitterUserDetails($socialNetworkUserExtId);
						$socialNetworkUserAvatar = $twitterUser->profile_image_url;
						editUserPreferences($userId, $twitterUser->url, $twitterUser->description, $twitterUser->location, 1, 1, $db);
						
					} elseif ($socialNetworkId == 3) {
						$googleUser = getGoogleUser($oauthToken);
						$socialNetworkUserExtId = $googleUser->id;
						$socialNetworkUserName = $googleUser->name;
						$socialNetworkUserAvatar = $googleUser->picture;
						$userEmail = $googleUser->email;
						$userUrl = NULL;
						$userBio = NULL;
						$userLocation = NULL;
						
						$googlePlusUser = getGooglePlusUser($socialNetworkUserExtId);
						if(isset($googlePlusUser->urls[0]->value))
							$userUrl = $googlePlusUser->urls[0]->value;
						if(isset($googlePlusUser->aboutMe))
							$userBio = $googlePlusUser->aboutMe;
						if(isset($googlePlusUser->currentLocation))
							$userLocation = $googlePlusUser->currentLocation;
							
						editUserPreferences($userId, $userUrl, $userBio, $userLocation, 1, 1, $db);
						
						if ($googlePlusUser->displayName) {
							$userDisplayName = $googlePlusUser->displayName;
						}
		
					}
					
					addSocialNetworkUser($socialNetworkId, $socialNetworkUserExtId, $socialNetworkUserName, $socialNetworkUserAvatar, $userId, NULL, $oauthToken, $oauthSecretToken, $oauthRefreshToken, $db);
					editDisplayName ($userId, $userDisplayName, $db);
				}
				
				$verificationCode = createSecretCode ($userId, 3, $db);
				sendVerificationEmail($verificationCode, $userEmail, $userName, $userDisplayName);

				header("Location: ".$pages["login"]->getAddress(TRUE)."/?step=confirm-verification");
			}
		}
		
		// Check if user is coming from Twitter
		if (isset($_SESSION["regStep"]) && $_SESSION["regStep"] == "one") {
			$userName = str_replace(" ", "_", $_SESSION["userName"]);
			if ($_SESSION["socialNetworkId"] == 3) {
				$oauthToken = $_SESSION["oauthToken"];
				$googleUser = json_decode(getPage("https://www.googleapis.com/oauth2/v1/userinfo?access_token=".$oauthToken));
				$userEmail = $googleUser->email;
			}
		
			$content .= "<p class=\"ss-successful\">You settings have been imported.</p>
			<p>To complete your registration, please fill this form:</p>";
		}
		
		global $recaptchaPublicKey;
		$currentUrl = getURL();
		$content .= "<div class=\"half-box\">
		<form name=\"register\" method=\"post\">
		<p>E-mail<br />
		<input type=\"text\" name=\"email\" value=\"$userEmail\" /></p>
		<p>User Name<br />
		<input type=\"text\" name=\"user-name\" size=\"30\" maxlength=\"20\" value=\"$userName\" /></p>
		<p>Password<br />
		<input type=\"password\" name=\"pass1\" /></p>
		<p>Re-type Password<br />
		<input type=\"password\". name=\"pass2\" /></p>";
		if ((empty($_SESSION["validCaptcha"]) || (isset($_SESSION["validCaptcha"]) && $_SESSION["validCaptcha"] != "true")) && (isset($_SESSION["regStep"]) == FALSE || (isset($_SESSION["regStep"]) && $_SESSION["regStep"] != "one"))) {
			$content .= "<p>".recaptcha_get_html($recaptchaPublicKey)."</p>";
		}
		$content .= "<p><input class=\"white-button\" style=\"width: 100%; padding: 6px 0px;\" type=\"submit\" value=\"Register\" /></p>
		</form>
		</div>";
		if (empty($_SESSION["regStep"]) || $_SESSION["regStep"] != "one") {
			$content .= "<div class=\"half-box\" style=\"float: right;\"> 
			<h4>Or...</h4>
			<p><a class=\"twitter-button\" href=\"".$pages["sync"]->getAddress(TRUE)."/?step=twitterAuth&amp;callback=".$pages["login"]->getAddress(TRUE)."&amp;url=".$originalUrl."\">Create account with Twitter</a></p>
			<p><a class=\"google-button\" href=\"".$pages["sync"]->getAddress(TRUE)."/?step=googleAuth&amp;callback=".$pages["login"]->getAddress(TRUE)."&amp;url=".$originalUrl."\">Create account with Google</a></p>
			<p><a class=\"white-button\" style=\"width: 100%; padding: 6px 0px;\" href=\"".$pages["login"]->getAddress(TRUE)."\">Log in</a></p>";
		}
		
		if (!empty($_SESSION["regStep"]) && $_SESSION["regStep"] == "one") {
			$_SESSION["regStep"] = "two";
		}
	}
	
	return $content;
}
