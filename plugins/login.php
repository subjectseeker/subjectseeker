<?php
/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function displayLogin() {
	global $sitename, $homeUrl, $pages;
	include_once (dirname(__FILE__)."/../third-party/hasher/phpass.php");
	
	$db = ssDbConnect();
	$content = "";
	$step = NULL;
	if (isset($_REQUEST["step"])) {
		$step = $_REQUEST["step"];
	}
	// Get original URL for redirection
	$originalUrl = $homeUrl;
	if (isset($_REQUEST["url"])) {
		$originalUrl = $_REQUEST["url"];	
	}
	
	// If user is logged in, log out or ask.
	if (isLoggedIn()) {
		$authUser = new auth();
		if (isset($_REQUEST["logout"]) && $_REQUEST["logout"] == "true") {
			$authUser->logout();
			header("Location: ".$originalUrl);
		} else {
			$authUserName = $authUser->userName;
			$content .=	"<div class=\"box-title\">Log In</div>
			<p>You are logged in as $authUserName</p>
			<p><a class=\"white-button\" href=\"".$pages["login"]->getAddress(TRUE)."/?logout=true\">Log Out</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a></p>";
		}
	} 
	
	/* Twitter log in */
	elseif (isset($_REQUEST["oauth_token"])) {
		// Check if user is coming from Twitter
		if ($_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
			$content .= "<div class=\"box-title\">Log In</div>
			<p>Twitter session expired!</p>
			<p><a class=\"white-button\" href=\"".$pages["twitter"]->getAddress(TRUE)."/?step=authUrl&amp;url=".$originalUrl."\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a></p>";
			
		} elseif ($_SESSION['oauth_token'] == $_REQUEST['oauth_token']) {
			// Get User ID from Auth Tokens for log in
			$twitterConnection = getTwitterAuthTokens ($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
			$twitterCredentials = $twitterConnection->getAccessToken();
			$socialNetworkUser = getSocialNetworkUser (1, $twitterCredentials["user_id"], "socialNetworkUserExtId", $db);
			$userId = $socialNetworkUser["userId"];
			$userName = getUserName($userId, $db);
			
			// Log in and create cookie if successful
			$authUser = new auth();
			if ($userId && $authUser->validateUser($userName)) {
				$authUser->loginUser($userId, TRUE);
				header("Location: ".$originalUrl);
			}
			// If user is unverified, send to verification
			elseif ($userId == TRUE && $authUser->errors[0]["id"] == 2) {
				header("Location: ".$pages["login"]->getAddress(TRUE)."/?step=send-verification");
			}
			// If user doesn't exist, redirect to registration with settings now imported
			else {
				$_SESSION["regStep"] = "one";
				$_SESSION["socialNetworkId"] = "1";
				$_SESSION["userId"] = $twitterCredentials['user_id'];
				$_SESSION["userName"] = $twitterCredentials["screen_name"];
				$_SESSION["oauthToken"] = $twitterCredentials['oauth_token'];
				$_SESSION["oauthSecretToken"] = $twitterCredentials['oauth_token_secret'];
				$_SESSION["oauthRefreshToken"] = NULL;
				header("Location: ".$pages["register"]->getAddress(TRUE)."/?url=".$originalUrl);
			}
		}
	}
		
	/* Google log in */
	elseif (isset($_REQUEST["code"])) {
		$code = $_REQUEST["code"];
		$googleTokens = getGoogleTokens($code,$pages["login"]->getAddress(TRUE));
		$oauthToken = $googleTokens->access_token;
		$googleUser = getGoogleUser($oauthToken);
		$socialNetworkUserExtId = $googleUser->id;
		
		$socialNetworkUser = getSocialNetworkUser (3, $socialNetworkUserExtId, "socialNetworkUserExtId", $db);
		$userId = $socialNetworkUser["userId"];
		$userName = getUserName($userId, $db);
		
		$authUser = new auth();
		if ($userId && $authUser->validateUser($userName)) {
			$authUser->loginUser($userId, TRUE);
			header("Location: ".$originalUrl);
		} elseif ($userId == TRUE && $authUser->errors[0]["id"] == 3) {
			header("Location: ".$pages["login"]->getAddress(TRUE)."/?step=send-verification");
		} else {
			$refreshToken = NULL;
			if (isset($googleTokens->refresh_token)) {
				$refreshToken = $googleTokens->refresh_tokens;
			}
			
			$_SESSION["regStep"] = "one";
			$_SESSION["socialNetworkId"] = "3";
			$_SESSION["userId"] = $socialNetworkUserExtId;
			$_SESSION["userName"] = $googleUser->name;
			$_SESSION["oauthToken"] = $oauthToken;
			$_SESSION["oauthSecretToken"] = NULL;
			$_SESSION["oauthRefreshToken"] = $refreshToken;
			header("Location: ".$pages["register"]->getAddress(TRUE)."/?url=".$originalUrl);
		}
	}
	
	/* Recover Account */
	elseif ($step == "lost-password") {
		$content .= "<div class=\"box-title\">Recover Account</div>
		<form action=\"".$pages["login"]->getAddress(TRUE)."/?step=recover-account\" name=\"login\" method=\"post\">
		<p>Please enter your user name or email to recover your account<br />
		<input type=\"text\" name=\"recovery-data\" size=\"30\" /></p>
		<p><input class=\"white-button\" type=\"submit\" value=\"Send recovery email\" /></p>
		</form>";
	} elseif ($step == "recover-account") {
		$content .= "<div class=\"box-title\">Recover Account</div>";
		if (!empty($_POST["recovery-data"])) {
			$userRecoveryData = $_POST["recovery-data"];
			
			// Determine if user submitted an email or a user name
			if ((filter_var($userRecoveryData, FILTER_VALIDATE_EMAIL) && $userId = emailToUserId($userRecoveryData, $db)) || ((preg_match("/^[A-Za-z][A-Za-z0-9_]*$/", $userRecoveryData) && strlen($userRecoveryData) < 31) && $userId = getUserId($userRecoveryData, $db))) {
				$userName = getUserName($userId, $db);
				$userEmail = getUserEmail($userId, $db);
				$userDisplayName = getDisplayName($userId, $db);
				$userStatusId = getUserStatus($userId, $db);
				$recoveryCode = createSecretCode ($userId, 2, $db);
				sendRecoveryEmail($recoveryCode, $userEmail, $userName, $userDisplayName);
			} else {
				$content .= "<p class=\"ss-error\">The submitted email or user was not found in our database.</p>
				<a class=\"white-button\" href=\"".$pages["login"]->getAddress(TRUE)."/?step=lost-password\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
				return $content;
			}
		}
		$content .= "<p class=\"ss-successful\">An email has been sent to your address to recover your account.</p>
		<form action=\"".$pages["login"]->getAddress(TRUE)."/?step=verify-recovery\" name=\"login\" method=\"post\">
		<p class=\"margin-bottom-small\">Please enter your recovery code below or follow the link sent with the email.</p>
		<p><input type=\"text\" name=\"recovery-code\" /></p>
		<p><input class=\"white-button\" type=\"submit\" value=\"Submit\" /></p>
		</form>";
		
	} elseif ($step == "verify-recovery") {
		$content .= "<div class=\"box-title\">Recover Account</div>";
		
		if (!empty($_REQUEST["recovery-code"])) {
			$recoveryCode = $_REQUEST["recovery-code"];
			$recoveryStatus = secretCodeToUserId($recoveryCode, 2, $db);
			
			// Check if code has expired
			if (!empty($recoveryStatus["expired"]) && $recoveryStatus["expired"] == TRUE) {
				$content .= "<p class=\"ss-error\">Your recovery code has expired.</p>";
				$userId = $recoveryStatus["userId"];
				removeSecretCode($userId, 2, $db);
			}
			// Check if it's time to reset the password
			elseif ($recoveryStatus["userId"] && $_POST["new-pass"]) {
				$userId = $recoveryStatus["userId"];
				$newUserPass1 = NULL;
				$newUserPass2 = NULL;
				if (!empty($_POST["new-pass"])) {
					$newUserPass1 = $_POST["new-pass"];
				}
				if (!empty($_POST["new-pass2"])) {
					$newUserPass2 = $_POST["new-pass2"];
				}
				
				$errors = checkUserData(NULL, NULL, NULL, NULL, NULL, NULL, $newUserPass1, $newUserPass2, $db);
				if (empty($newUserPass1) || empty($newUserPass2)) {
					$errors .= "<p class=\"ss-error\">Missing value, please submit your new password and confirmation password.</p>";
				}
				
				if (!empty($errors)) {
					$content .= "$errors";
				} elseif ($newUserPass1 == $newUserPass2) {
					$userId = $recoveryStatus["userId"];
					editUserPass($userId, $newUserPass1, $db);
					removeSecretCode($userId, 2, $db);
					
					$content .= "<p class=\"ss-successful\">Password has been changed.</p>
					<a class=\"white-button\" href=\"".$pages["login"]->getAddress(TRUE)."\">Log In</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
				}
			}
			// Check if recovery code is valid.
			elseif (!empty($recoveryStatus["userId"])) {
				$content .= "<p class=\"ss-successful\">Code verified, you can reset your password below.</p>
				<form method=\"post\">
				<input type=\"hidden\" name=\"recovery-code\" value=\"$recoveryCode\" />
				<p>New Password<br />
				<input name=\"new-pass\" type=\"password\" /></p>
				<p>Re-type Password<br />
				<input name=\"new-pass2\" type=\"password\" /></p>
				<br />
				<p class=\"margin-bottom-small\"><input class=\"white-button\" type=\"submit\" value=\"Change Password\" /></p>
				</form>";
			} else {
				$content .= "<p class=\"ss-error\">Recovery code not found.</p>
				<p><a class=\"white-button\" href=\"".$pages["login"]->getAddress(TRUE)."/?step=recover-account\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a></p>";
			}
		} else {
			$content .= "<p class=\"ss-error\">You must submit a recovery code.</p>
			<p><a class=\"white-button\" href=\"".$pages["login"]->getAddress(TRUE)."/?step=recover-account\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a></p>";
		}
	}
	
	/* Verify Email */
	elseif ($step == "send-verification") {
		$content .=	"<div class=\"box-title\">Verify Account</div>
		<form action=\"".$pages["login"]->getAddress(TRUE)."/?step=confirm-verification\" method=\"post\">
		<p class=\"margin-bottom-small\">Please enter your user name or email to send your email verification code.</p>
		<p><input type=\"text\" name=\"verification-data\"	/></p>
		<p><input class=\"white-button\" type=\"submit\" value=\"Send verification email\" /></p>
		</form>";
	} elseif ($step == "confirm-verification") {
		$content .= "<div class=\"box-title\">Verify Account</div>";
		if (!empty($_POST["verification-data"])) {
			$userVerificationData = $_POST["verification-data"];
			
			// Determine if user submitted an email or a user name
			if ((filter_var($userVerificationData, FILTER_VALIDATE_EMAIL) && $userId = emailToUserId($userVerificationData, $db)) || ((preg_match("/^[A-Za-z][A-Za-z0-9_]*$/", $userVerificationData) && strlen($userVerificationData) < 31) && $userId = getUserId($userVerificationData, $db))) {
				$userName = getUserName($userId, $db);
				$userEmail = getUserEmail($userId, $db);
				$userDisplayName = getDisplayName($userId, $db);
				$userStatusId = getUserStatus($userId, $db);
				
				if ($userStatusId == 3) {
					$verificationCode = createSecretCode ($userId, 3, $db);
					sendVerificationEmail($verificationCode, $userEmail, $userName, $userDisplayName);
				} else {
					$content .= "<p class=\"ss-warning\">This account ($userName) has already been verified.</p>
					<p><a class=\"white-button\" href=\"".$pages["login"]->getAddress(TRUE)."\">Log In</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a></p>";
					return $content;
				}
			} else {
				$content .= "<p class=\"ss-error\">The submitted email or user was not found in our database.</p>
				<p><a class=\"white-button\" href=\"".$pages["login"]->getAddress(TRUE)."/?step=send-verification\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a></p>";
				return $content;
			}
		}
		
		$content .= "<p class=\"ss-successful\">An email has been sent to you address to verify your account.</p>
		<form action=\"".$pages["login"]->getAddress(TRUE)."/?step=verify-email\" name=\"login\" method=\"post\">
		<p class=\"margin-bottom-small\">Please enter your verification code below or follow the link sent with the email.</p>
		<p><input type=\"text\" name=\"verification-code\" /></p>
		<p><input class=\"white-button\" type=\"submit\" value=\"Submit\" /></p>
		</form>";
	} elseif ($step == "verify-email") {
		$content .=	"<div class=\"box-title\">Verify Account</div>";
		
		if (!empty($_REQUEST["verification-code"])) {
			$verificationCode = $_REQUEST["verification-code"];
			$verificationStatus = secretCodeToUserId($verificationCode, 3, $db);
			
			if (!empty($verificationStatus["userId"])) {
				$userId = $verificationStatus["userId"];
			}
			
			// Check if code has expired
			if (!empty($verificationStatus["expired"]) && $verificationStatus["expired"] == TRUE) {
				$content .= "<p class=\"ss-error\">Your verification code has expired.</p>
				<a class=\"white-button\" href=\"".$pages["login"]->getAddress(TRUE)."/?step=send-verification\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
			} elseif (!empty($userId)) {
				editUserStatus ($userId, 0, $db);
				$authUser = new auth();
				$authUser->loginUser($userId, FALSE);
				$userName = getUserName($userId, $db);
				
				header("Location: $homeUrl/user/$userName/settings");
				
			} else {
				$content .= "<p class=\"ss-error\">Verification code not found in our database.</p>
				<a class=\"white-button\" href=\"".$pages["login"]->getAddress(TRUE)."/?step=send-verification\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
			}
		} else {
			$content .= "<p class=\"ss-error\">Verification code not submitted.</p>
			<a class=\"white-button\" href=\"".$pages["login"]->getAddress(TRUE)."/?step=send-verification\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
		}
		
		removeSecretCode($userId, 3, $db);
	}
	
	/* Log In */
	else {
		$content .=	"<div class=\"box-title\">Log In</div>";
		
		if (isset($_POST["user-name"])) {
			$userName = $_POST["user-name"];
			$userPass = $_POST["pass"];
			
			$auth = new auth;
			$userId = $auth->validateUser($userName, $userPass);
			if ($errors = $auth->errors) {
				foreach ($errors as $error) {
					$content .= "<p class=\"ss-error\">".$error["text"]."</p>";
					if ($error["id"] == 4) {
						$content .= "<p><a class=\"white-button\" href=\"".$pages["login"]->getAddress(TRUE)."/?step=send-verification\">Verify Account</a></p>";
					}
				}
			} else {
				if (isset($_POST["remember"]) && $_POST["remember"] == "true") {
					$rememberUser = TRUE;
				}
				$auth->loginUser($userId, $rememberUser);
				header("Location: ".$originalUrl);
			}
		}
		
		// Display log in form.
		$content .=	"<div class=\"half-box\">
		<form name=\"login\" method=\"post\">
		<p>User<br />
		<input type=\"text\" name=\"user-name\" size=\"30\" maxlength=\"20\" /></p>
		<p>Password<br />
		<input type=\"password\" name=\"pass\" size=\"30\" /><br />
		<a href=\"".$pages["login"]->getAddress(TRUE)."/?step=lost-password\">Lost user name / password</a>
		</p>
		<p>Remember me <input type=\"checkbox\" checked=\"checked\" name=\"remember\" value=\"true\" /></p>
		<input class=\"white-button\" style=\"width: 100%; padding: 6px 0px;\" type=\"submit\" value=\"Log In\" />
		</form>
		</div>
		<div class=\"half-box\" style=\"float: right;\"> 
		<h4>Or...</h4>
		<div class=\"center-text\">
		<p><a class=\"twitter-button\" href=\"".$pages["sync"]->getAddress(TRUE)."/?step=twitterAuth&amp;callback=".$pages["login"]->getAddress(TRUE)."&amp;url=".$originalUrl."\">Log in with Twitter</a></p>
		<p><a class=\"google-button\" href=\"".$pages["sync"]->getAddress(TRUE)."/?step=googleAuth&amp;callback=".$pages["login"]->getAddress(TRUE)."&amp;url=".$originalUrl."\">Log in with Google</a></p>
		<p><a class=\"white-button\" style=\"width: 100%; padding: 6px 0px;\" href=\"".$pages["register"]->getAddress(TRUE)."\">Register</a></p>
		</div>";
	}
	
	return $content;
}
?>
