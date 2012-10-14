<?php

function displayRegistration() {
	global $homeUrl;
	global $pages;
	$db = ssDbConnect();
	
	$originalUrl = $homeUrl;
	if (isset($_GET["url"])) {
		$originalUrl = $_GET["url"];	
	}
	
	// If logged in, send to log in page to deal with this.
	if (isLoggedIn()) {
		header("Location: ".$pages["login"]->getAddress()."/?url=$originalUrl");
	}
	else {
		$content = "<div class=\"box-title\">Create Account</div>";
		include_once('../third-party/recaptcha/recaptchalib.php');
		
		$userName = NULL;
		// Check if user has submitted information
		if (isset($_POST['user-name'])) {
			$userName = mysql_escape_string($_POST['user-name']);
			$userEmail = mysql_escape_string($_POST['email']);
			$userPass1 = $_POST['pass1'];
			$userPass2 = $_POST['pass2'];
			
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
			if (empty($_SESSION["oauth_token"]) || $_SESSION["regStep"] != "two") {
				global $recaptchaPrivateKey;
				$resp = recaptcha_check_answer ($recaptchaPrivateKey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
				if (!$resp->is_valid) {
					$errors .= "<p class=\"ss-error\">Submitted captcha code is invalid, please try again.</p>";
				}
			}
			
			if (!empty($errors)) {
				if ($_SESSION["regStep"] == "two") {
					$_SESSION["regStep"] = "one";
				}
				$content .= "$errors";
			}
			else {
				$hashedPass = hashPassword($userPass1);
				$userId = addUser($userName, $userEmail, $hashedPass, $db);
				editUserStatus ($userId, 3, $db);
				
				// Check if Twitter details have been imported
				if (isset($_SESSION["oauth_token"], $_SESSION["oauth_token_secret"]) && $_SESSION["regStep"] == "two") {
					addToTwitterList($_SESSION['user_id']);
					addUserSocialAccount (1, $_SESSION['screen_name'], $_SESSION['oauth_token'], $_SESSION['oauth_token_secret'], $userId, $db);
					$twitterUserDetails = getTwitterUserDetails($_SESSION['user_id']);
					editUserPreferences($userId, $twitterUserDetails->url, $twitterUserDetails->description, 0, 0, $db);
					$userDisplayName = $twitterUserDetails->name;
					if (empty($userDisplayName)) {
						$userDisplayName = $userName;
					}
					editDisplayName ($userId, $userDisplayName, $db);
				}
				
				$verificationCode = createSecretCode ($userId, 3, $db);
				sendVerificationEmail($verificationCode, $userEmail, $userName, $userDisplayName);

				header("Location: ".$pages["login"]->getAddress()."/?step=confirm-verification");
			}
		}
		
		// Check if user is coming from Twitter
		if (isset($_SESSION["regStep"]) && $_SESSION["regStep"] == "one") {
			$userName = $_SESSION["screen_name"];
		
			$content .= "<p class=\"ss-successful\">You settings have been imported.</p>
			<p>To complete your registration, please fill this form:</p>";
		}
		
		global $recaptchaPublicKey;
		$twitterUrl = getTwitterAuthURL ($pages["login"]->getAddress()."/?=".$originalUrl, TRUE);
		$content .= "<div class=\"half-box\">
		<form name=\"register\" method=\"post\">
		<p>E-mail<br />
		<input type=\"text\" name=\"email\" /></p>
		<p>User Name<br />
		<input type=\"text\" name=\"user-name\" size=\"30\" maxlength=\"20\" value=\"$userName\" /></p>
		<p>Password<br />
		<input type=\"password\" name=\"pass1\" /></p>
		<p>Re-type Password<br />
		<input type=\"password\". name=\"pass2\" /></p>";
		if (empty($_SESSION["regStep"]) || $_SESSION["regStep"] != "one") {
			$content .= "<p>".recaptcha_get_html($recaptchaPublicKey)."</p>";
		}
		$content .= "<p><input class=\"white-button\" style=\"width: 100%; padding: 6px 0px;\" type=\"submit\" value=\"Register\" /></p>
		</form>
		</div>";
		if (empty($_SESSION["regStep"]) || $_SESSION["regStep"] != "one") {
			$content .= "<div class=\"half-box\" style=\"float: right;\"> 
			<h4>Or...</h4>
			<div class=\"center-text\">
			<p><a class=\"twitter-button\" href=\"$twitterUrl\">Create account with Twitter</a></p>
			<p><a class=\"white-button\" style=\"width: 100%; padding: 6px 0px;\" href=\"".$pages["login"]->getAddress()."\">Log in</a></p>
			</div>";
		}
		
		if (isset($_SESSION["regStep"]) && $_SESSION["regStep"] == "one") {
			$_SESSION["regStep"] = "two";
		}
	}
	
	return $content;
}
