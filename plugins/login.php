<?php
/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function displayLogin() {
	global $sitename;
	global $pages;
	global $hashFile;
	include_once $hashFile;
	
	$db = ssDbConnect();
	$step = NULL;
	if (isset($_GET["step"])) {
		$step = $_GET["step"];
	}
	
	// Get original URL for redirection
	global $homeUrl;
	$originalUrl = $homeUrl;
	if (isset($_GET["url"])) {
		$originalUrl = $_GET["url"];	
	}
	
	// If user is logged in, log out or ask.
	if (isLoggedIn()) {
		if ($_REQUEST["logout"] == "true") {
			$authUser = new auth();
			$authUser->logout();
			header("Location: ".$originalUrl);
		}
		else {
			$content =  "<div class=\"box-title\">Log In</div>
			<p>You are logged in</p>
			<p><a class=\"white-button\" href=\"".$pages["login"]->getAddress()."/?logout=true\">Log Out</a></p>";
		}
	}
	else {
		// Check if user is coming from Twitter
		if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
			$content = "<div class=\"box-title\">Log In</div>
			<p>Twitter session expired!</p>";
		}
		elseif (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] == $_REQUEST['oauth_token']) {
			// Get User ID from Auth Tokens for log in
			$twitterConnection = getTwitterAuthTokens ($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
			$twitterCredentials = $twitterConnection->getAccessToken();
			$userId = socialAccountToUserId(1, $twitterCredentials["screen_name"], $db);
			
			// Log in and create cookie if successful
			$authUser = new auth();
			if ($userId == TRUE && $authUser->validateUser($userId, $db) == TRUE) {
				createCookie($userId, $db);
				header("Location: ".$originalUrl);
			}
			// If user is unverified, send to verification
			elseif ($userId == TRUE && $authUser->error == 2) {
				header("Location: ".$pages["login"]->getAddress()."/?step=send-verification");
			}
			// If user doesn't exist, redirect to registration with settings now imported
			else {
				$_SESSION["regStep"] = "one";
				$_SESSION["user_id"] = $twitterCredentials['user_id'];
				$_SESSION["screen_name"] = $twitterCredentials["screen_name"];
				$_SESSION["oauth_token"] = $twitterCredentials['oauth_token'];
				$_SESSION["oauth_token_secret"] = $twitterCredentials['oauth_token_secret'];
				header("Location: ".$pages["register"]->getAddress());
			}
		}
		
		/* Recover Account */
		
		elseif ($step == "lost-password") {
			$content = "<div class=\"box-title\">Recover Account</div>
			<form action=\"".$pages["login"]->getAddress()."/?step=recover-account\" name=\"login\" method=\"post\">
			<p>Please enter your user name or email to recover your account<br />
			<input type=\"text\" name=\"recovery-data\" size=\"30\" /></p>
			<p><input class=\"white-button\" type=\"submit\" value=\"Send recovery email\" /></p>
			</form>";
		}
		elseif ($step == "recover-account") {
			$content = "<div class=\"box-title\">Recover Account</div>";
			if (isset($_POST["recovery-data"])) {
				$userRecoveryData = $_POST["recovery-data"];
				
				// Determine if user submitted an email or a user name
				if ((filter_var($userRecoveryData, FILTER_VALIDATE_EMAIL) && $userId = emailToUserId($userRecoveryData, $db)) || ((preg_match("/^[A-Za-z][A-Za-z0-9_]*$/", $userRecoveryData) && strlen($userRecoveryData) < 31) && $userId = getUserId($userRecoveryData, $db))) {
					$userName = getUserName($userId, $db);
					$userEmail = getUserEmail($userId, $db);
					$userDisplayName = getDisplayName($userId, $db);
					$userStatusId = getUserStatus($userId, $db);
					$recoveryCode = createSecretCode ($userId, 2, $db);
					sendRecoveryEmail($recoveryCode, $userEmail, $userName, $userDisplayName);
				}
				else {
					$content .= "<p class=\"ss-error\">The submitted email or user was not found in our database.</p>
					<a class=\"white-button\" href=\"".$pages["login"]->getAddress()."/?step=lost-password\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
					return $content;
				}
			}
			$content .= "<p class=\"ss-successful\">An email has been sent to you address to recover your account.</p>
			<form action=\"".$pages["login"]->getAddress()."/?step=verify-recovery\" name=\"login\" method=\"post\">
			<p class=\"margin-bottom-small\">Please enter your recovery code below or follow the link sent with the email.</p>
			<p><input type=\"text\" name=\"recovery-code\" /></p>
			<p><input class=\"white-button\" type=\"submit\" value=\"Submit\" /></p>
			</form>";
		}
		elseif ($step == "verify-recovery") {
			$content = "<div class=\"box-title\">Recover Account</div>";
			
			if (isset($_REQUEST["recovery-code"])) {
				$recoveryCode = $_REQUEST["recovery-code"];
				$recoveryStatus = secretCodeToUserId($recoveryCode, 2, $db);
				
				// Check if code has expired
				if (isset($recoveryStatus["expired"]) && $recoveryStatus["expired"] == TRUE) {
					$content .= "<p class=\"ss-error\">Your recovery code has expired.</p>";
					$userId = $recoveryStatus["userId"];
					removeSecretCode($userId, 2, $db);
				}
				// Check if it's time to reset the password
				elseif ($recoveryStatus["userId"] && $_POST["new-pass"]) {
					$userId = $recoveryStatus["userId"];
					$newUserPass1 = NULL;
					$newUserPass2 = NULL;
					if (isset($_POST["new-pass"])) {
						$newUserPass1 = $_POST["new-pass"];
					}
					if (isset($_POST["new-pass2"])) {
						$newUserPass2 = $_POST["new-pass2"];
					}
					
					$errors = checkUserData(NULL, NULL, NULL, NULL, NULL, NULL, $newUserPass1, $newUserPass2, $db);
					if (empty($newUserPass1) || empty($newUserPass2)) {
						$errors .= "<p class=\"ss-error\">Missing value, please submit your new password and confirmation password.</p>";
					}
					
					if (!empty($errors)) {
						$content .= "$errors";
					}
					else {
						if ($newUserPass1 == $newUserPass2) {
							$userId = $recoveryStatus["userId"];
							editUserPass($userId, $newUserPass1, $db);
							removeSecretCode($userId, 2, $db);
							
							$content .= "<p class=\"ss-successful\">Password has been changed.</p>
							<a class=\"white-button\" href=\"".$pages["login"]->getAddress()."\">Log In</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
						}
					}
				}
				// Check if recovery code is valid.
				elseif (isset($recoveryStatus["userId"])) {
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
				}
				else {
					$content .= "<p class=\"ss-error\">Recovery code not found.</p>
					<a class=\"white-button\" href=\"".$pages["login"]->getAddress()."/?step=recover-account\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
				}
			}
			else {
				$content .= "<p class=\"ss-error\">You must submit a recovery code.</p>
				<a class=\"white-button\" href=\"".$pages["login"]->getAddress()."/?step=recover-account\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
			}
		}
		
		/* Verify Email */
		
		elseif ($step == "send-verification") {
			$content =  "<div class=\"box-title\">Verify Account</div>
			<form action=\"".$pages["login"]->getAddress()."/?step=confirm-verification\" method=\"post\">
			<p class=\"margin-bottom-small\">Please enter your user name or email to send your email verification code.</p>
			<p><input type=\"text\" name=\"verification-data\"  /></p>
			<p><input class=\"white-button\" type=\"submit\" value=\"Send verification email\" /></p>
			</form>";
		}
		elseif ($step == "confirm-verification") {
			$content = "<div class=\"box-title\">Verify Account</div>";
			if (isset($_POST["verification-data"])) {
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
					}
					else {
						$content .= "<p class=\"ss-warning\">This account ($userName) has already been verified.</p>";
						return $content;
					}
				}
				else {
					$content .= "<p class=\"ss-error\">The submitted email or user was not found in our database.</p>
					<a class=\"white-button\" href=\"".$pages["login"]->getAddress()."/?step=send-verification\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
					return $content;
				}
			}
			
			$content .= "<p class=\"ss-successful\">An email has been sent to you address to verify your account.</p>
			<form action=\"".$pages["login"]->getAddress()."/?step=verify-email\" name=\"login\" method=\"post\">
			<p class=\"margin-bottom-small\">Please enter your verification code below or follow the link sent with the email.</p>
			<p><input type=\"text\" name=\"verification-code\" /></p>
			<p><input class=\"white-button\" type=\"submit\" value=\"Submit\" /></p>
			</form>";
		}
		elseif ($step == "verify-email") {
			$content =  "<div class=\"box-title\">Verify Account</div>";
			
			if (isset($_REQUEST["verification-code"])) {
				$verificationCode = $_REQUEST["verification-code"];
				$verificationStatus = secretCodeToUserId($verificationCode, 3, $db);
				
				if (isset($verificationStatus["userId"])) {
					$userId = $verificationStatus["userId"];
				}
				
				// Check if code has expired
				if (isset($verificationStatus["expired"]) && $verificationStatus["expired"] == TRUE) {
					$content .= "<p class=\"ss-error\">Your verification code has expired.</p>
					<a class=\"white-button\" href=\"".$pages["login"]->getAddress()."/?step=send-verification\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
				}
				elseif (!empty($userId)) {
					editUserStatus ($userId, 0, $db);
					$content .= "<p class=\"ss-successful\">Your account has been verified.</p>
					<a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
				}
				else {
					$content .= "<p class=\"ss-error\">Verification code not found in our database.</p>
					<a class=\"white-button\" href=\"".$pages["login"]->getAddress()."/?step=send-verification\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
				}
			}
			else {
				$content .= "<p class=\"ss-error\">Verification code not submitted.</p>
				<a class=\"white-button\" href=\"".$pages["login"]->getAddress()."/?step=send-verification\">Retry</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a>";
			}
			
			removeSecretCode($userId, 3, $db);
		}
		
		/* Log In */
		
		else {
			$content =  "<div class=\"box-title\">Log In</div>";
			
			if (isset($_POST["user-name"])) {
				$userName = mysql_real_escape_string($_POST["user-name"]);
				if (isset($_POST["pass"])) {
					$password = $_POST["pass"];
				}
				
				$sql = "SELECT USER_ID, PASSWORD FROM USER WHERE USER_NAME = '$userName'";
				$result = mysql_query($sql, $db);
				$row = mysql_fetch_array($result);
				
				$hasher = new PasswordHash(8, TRUE);
				
				$userId = $row["USER_ID"];
				$passwordHashed = $row["PASSWORD"];
				
				if ($hasher->CheckPassword($password, $passwordHashed) || $passwordHashed == md5($password)) {
					$authUser = new auth();
					if ($authUser->validateUser($userId, $db) == TRUE) {
						if (isset($_POST["remember"]) && $_POST["remember"] == "true") {
							createCookie($userId, $db);
						}
						header("Location: ".$originalUrl);
					}
					elseif ($authUser->error == 1) {
						$content .= "<p class=\"ss-error\">This account is disabled.</p>";
					}
					elseif ($authUser->error == 2) {
						$content .= "<p class=\"ss-error\">User email is unverified.</p>
						<p><a class=\"white-button\" href=\"".$pages["login"]->getAddress()."/?step=send-verification\">Verify Account</a> <a class=\"white-button\" href=\"$originalUrl\">Back to $sitename</a></p>";
					}
					elseif ($authUser->error == 3) {
						$content .= "<p class=\"ss-error\">User doesn't exist.</p>";
					}
				}
				else {
					$content .= "<p class=\"ss-error\">Wrong user name or password.</p>
					<p><a class=\"white-button\" href=\"$originalUrl\">Back to homepage</a></p>";
				}
			}
			
			// Get Twitter Auth URL
			$twitterUrl = getTwitterAuthURL ($pages["login"]->getAddress()."/?url=".$originalUrl, TRUE);
			
			// Display log in form.
			$content .=  "<div class=\"half-box\">
			<form name=\"login\" method=\"post\">
			<p>User<br />
			<input type=\"text\" name=\"user-name\" size=\"30\" maxlength=\"20\" /></p>
			<p>Password<br />
			<input type=\"password\" name=\"pass\" size=\"30\" /><br />
			<a href=\"".$pages["login"]->getAddress()."/?step=lost-password\">Lost user name / password</a>
			</p>
			<p>Remember me <input type=\"checkbox\" checked=\"checked\" name=\"remember\" value=\"true\" /></p>
			<input class=\"white-button\" style=\"width: 100%; padding: 6px 0px;\" type=\"submit\" value=\"Log In\" />
			</form>
			</div>
			<div class=\"half-box\" style=\"float: right;\"> 
			<h4>Or...</h4>
			<div class=\"center-text\">
			<p><a class=\"twitter-button\" href=\"$twitterUrl\">Log in with Twitter</a></p>
			<p><a class=\"white-button\" style=\"width: 100%; padding: 6px 0px;\" href=\"".$pages["register"]->getAddress()."\">Register</a></p>
			</div>";
		}
	}
	
	return $content;
}
?>
