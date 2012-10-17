<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function displayContactForm() {
	
	include_once(dirname(__FILE__)."/../third-party/recaptcha/recaptchalib.php");
	
	$step = NULL;
	$userDisplayName = NULL;
	$userEmail = NULL;
	if (!empty($_REQUEST["step"])) $step = $_REQUEST["step"];
	$db = ssDbConnect();
	if (isLoggedIn()) {
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userDisplayName = getDisplayName($authUserId, $db);
		$userEmail = getUserEmail($authUserId, $db);
	}
	
	if ($step == "send") {
		$errors = "";
		if (!empty($_POST["name"])) {
			$name = strip_tags($_POST["name"]);
		} else {
			$errors .= "<p class=\"ss-error\">Please submit a name.</p>";
		}
		if (!empty($_POST["email"]) && filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
			$email = strip_tags($_POST["email"]);
		} else {
			$errors .= "<p class=\"ss-error\">Please submit a valid email.</p>";
		}
		if (!empty($_POST["subject"])) {
			$subject = htmlspecialchars($_POST["subject"]);
		} else {
			$errors .= "<p class=\"ss-error\">Please submit a subject.</p>";
		}
		if (!empty($_POST["name"])) {
			$message = htmlspecialchars($_POST["message"]);
		} else {
			$errors .= "<p class=\"ss-error\">Please submit a message.</p>";
		}
		if (empty($authUserId)) {
			global $recaptchaPrivateKey;
			$resp = recaptcha_check_answer ($recaptchaPrivateKey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
			if (!$resp->is_valid) {
				$errors .= "<p class=\"ss-error\">Submitted captcha code is invalid, please try again.</p>";
			}
		}
		
		if (!empty($errors)) {
			print "$errors";
		} else {
			global $contactEmail;
			$content = "Author: ".$name."
Author Email: ".$email."
	
".$message;
			
			sendMail($contactEmail, $subject, $content);
			print "<p class=\"ss-successful\">Your message has been successfully sent.</p>";
		}
	}
	
	print "<form method=\"post\">
	<input type=\"hidden\" name=\"step\" value=\"send\" />
	<p>Name<br />
	<input name=\"name\" type=\"text\" maxlength=\"40\" value=\"".htmlspecialchars($userDisplayName, ENT_QUOTES)."\" /></p>
	<p>Email<br />
	<input name=\"email\" type=\"text\" maxlength=\"256\" value=\"".htmlspecialchars($userEmail, ENT_QUOTES)."\" /></p>
	<p>Subject<br />
	<input name=\"subject\" maxlength=\"40\" type=\"text\" /></p>
	<p>Message<br />
	<textarea name=\"message\"></textarea></p>";
	if (empty($authUserId)) {
		global $recaptchaPublicKey;
		print "<p>".recaptcha_get_html($recaptchaPublicKey)."<p>";
	}
	print "<p><input class=\"ss-button\" type=\"submit\" value=\"Send\" /></p>
	</form>";
}

?>