<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function userSettings() {
	global $pages;
	
	$db = ssDbConnect();
	if (isLoggedIn()) {
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
		
		$step = NULL;
		if (isset($_REQUEST["step"])) {
			$step = $_REQUEST["step"];
		}
		preg_match('/(?<=\/user\/)[A-Za-z][A-Za-z0-9_]+/', $_SERVER["REQUEST_URI"], $matchResult);
		$userName = $matchResult[0];
		$userId = getUserId($userName, $db);
		
		if (empty($userId)) {
			print "<p class=\"ss-error\">User not found.</p>";
		}
		elseif ($userId == $authUserId || $userPriv > 1) { // Check if user or admin
		
			if (isset($_POST["form"]) && $_POST["form"] == "personal") {
				$newDisplayName = NULL;
				$newUrl = NULL;
				$newBio = NULL;
				$emailEdPicks = NULL;
				$emailAnnouncements = NULL;
				if (isset($_POST["display-name"])) {
					$newDisplayName = $_POST["display-name"];
				}
				if (isset($_POST["url"])) {
					$newUrl = $_POST["url"];
				}
				if (isset($_POST["bio"])) {
					$newBio = $_POST["bio"];
				}
				if (isset($_POST["email-edpicks"])) {
					$emailEdPicks = $_POST["email-edpicks"];
				}
				if (isset($_POST["email-announcements"])) {
					$emailAnnouncements = $_POST["email-announcements"];
				}
				
				$errors = checkUserPreferences($newUrl, $newBio);
				$errors .= checkUserData($authUserId, $userId, NULL, $newDisplayName, NULL, NULL, NULL, NULL, $db);
				if (!empty($errors)) {
					print "$errors";
				}
				else {
					if (empty($newDisplayName)) {
						$newDisplayName = $userName;
					}
					editUserPreferences($userId, $newUrl, $newBio, $emailEdPicks, $emailAnnouncements, $db);
					editDisplayName ($userId, $newDisplayName, $db);
				}
			}
			elseif (isset($_POST["form"]) && $_POST["form"] == "email") {
				$newEmail = NULL;
				$userPass = NULL;
				if (isset($_POST["email"])) {
					$newEmail = $_POST["email"];
				}
				if (isset($_POST["current-pass"])) {
					$userPass = $_POST["current-pass"];
				}
				
				$errors = checkUserData($authUserId, $userId, NULL, NULL, $newEmail, $userPass, NULL, NULL, $db);
				
				if (empty($userPass) || empty($newEmail)) {
					$errors .= "<p class=\"ss-error\">Missing value, please submit your current password and your desired new email.</p>";
				}
				if (emailToUserId($newEmail, $db)) {
					$errors .= "<p class=\"ss-error\">Submitted email already exists in our system.</p>";
				}
				
				if (!empty($errors)) {
					print "$errors";
				}
				else {
					editEmail($userId, $newEmail, $db);
				}
			}
			elseif (isset($_POST["form"]) && $_POST["form"] == "password") {
				$userPass = NULL;
				$newUserPass1 = NULL;
				$newUserPass2 = NULL;
				if (isset($_POST["current-pass"])) {
					$userPass = $_POST["current-pass"];
				}
				if (isset($_POST["new-pass"])) {
					$newUserPass1 = $_POST["new-pass"];
				}
				if (isset($_POST["new-pass2"])) {
					$newUserPass2 = $_POST["new-pass2"];
				}
				
				$errors = checkUserData($authUserId, $userId, NULL, NULL, NULL, $userPass, $newUserPass1, $newUserPass2, $db);
				
				if (empty($userPass) || empty($newUserPass1) || empty($newUserPass2)) {
					$errors .= "<p class=\"ss-error\">Missing value, please submit your current password and your desired new password.</p>";
				}
				
				if (!empty($errors)) {
					print "$errors";
				}
				else {
					if ($newUserPass1 == $newUserPass2) {
						editUserPass($userId, $newUserPass1);
					}
				}
			}
			
			$userPriv = getUserPrivilegeStatus($userId, $db);
			$userEmail = getUserEmail($userId, $db);
			$userDisplayName = getDisplayName($userId, $db);
			$userAvatar = getUserAvatar($userId, $db);
			$userPreferences = getUserPreferences($userId, $db);
			$userTwitter = getUserSocialAccount(1, $userId, $db);
			
			global $imagesUrl;
			if (!empty($userAvatar)) {
				$avatarSrc = $imagesUrl."/users/$userId/avatars/$userAvatar";
			}
			else {
				$avatarSrc = $imagesUrl."/icons/default-avatar.jpg";
			}
			
			$userUrl = NULL;
			$userBio = NULL;
			$emailEditorsPicks = NULL;
			$emailAnnouncements = NULL;
			while ($row = mysql_fetch_array($userPreferences)) {
				$userUrl = $row["USER_URL"];
				$userBio = $row["USER_BIOGRAPHY"];
				$emailEditorsPicks = $row["EMAIL_EDITOR_PICK"];
				$emailAnnouncements = $row["EMAIL_ANNOUNCEMENTS"];
			}
			
			$originalUrl = getURL();
			
			print "<div>
			<h3>Personal Information</h3>
			<form class=\"avatar-form\" method=\"post\" action=\"".$pages["crop"]->getAddress()."/?url=$originalUrl \" enctype=\"multipart/form-data\">
			<input type=\"hidden\" name=\"userId\" value=\"$userId\" />
			<p class=\"margin-bottom-small\"><img src=\"$avatarSrc\" title=\"User avatar\" /></p>
			<p><input type=\"file\" name=\"image\" accept=\"image/*\" /><br /><input class=\"ss-button\" type=\"submit\" value=\"Upload\" /></p>
			</form>
			<form method=\"post\">
			<input type=\"hidden\" name=\"form\" value=\"personal\" />
			<p>Display Name<br />
			<input name=\"display-name\" type=\"text\" value=\"".htmlspecialchars($userDisplayName, ENT_QUOTES)."\" /></p>
			<p>URL<br /><input name=\"url\" type=\"text\" value=\"".htmlspecialchars($userUrl, ENT_QUOTES)."\" /></p>
			<p>Biography<br /><textarea name=\"bio\">$userBio</textarea></p>";
			
			if ($userTwitter == TRUE) {
				$currentUrl = getURL();
				print "<div class=\"sync-link\"><a title=\"Go to Twitter profile\" href=\"https://twitter.com/#!/".$userTwitter["SOCIAL_NETWORKING_ACCOUNT_NAME"]."\"><div class=\"twitter-icon\"></div> ".$userTwitter["SOCIAL_NETWORKING_ACCOUNT_NAME"]."</a> | <a title=\"Go to synchronization page\" href=\"".$pages["twitter"]->getAddress()."/?url=$currentUrl&amp;remove=true\">Remove</a></div>";
			}
			else {
				print "<div class=\"sync-link\"><a title=\"Go to synchronization page\" href=\"".$pages["twitter"]->getAddress()."\"><div class=\"twitter-icon\"></div> Sync Twitter</a></div>";
			}
			
			print "<br />
			<h3>Notifications</h3>
			<p><input name=\"email-edpicks\" type=\"checkbox\" value=\"True\" ";
			if ($emailEditorsPicks == "1") {
				print "checked=\"checked\"";
			}
			print " /> Enable email notifications when you receive an \"Editor's Pick\".</p>
			<p><input name=\"email-announcements\" type=\"checkbox\" value=\"True\" ";
			if ($emailAnnouncements == "1") {
				print "checked=\"checked\"";
			}
			print " /> Enable email notifications for announcements.</p>
			<p><input class=\"ss-button\" type=\"submit\" value=\"Change Settings\" /></p>
			</form>
			<hr class=\"margin-bottom\" />
			<form method=\"post\">
			<input type=\"hidden\" name=\"form\" value=\"email\" />
			<h3>Change Email</h3>
			<p>Email<br />
			<input name=\"email\" type=\"text\"  value=\"".htmlspecialchars($userEmail, ENT_QUOTES)."\" /></p>
			<p>Current Password<br />
			<input name=\"current-pass\" type=\"password\" value=\"\" /></p>
			<p><input class=\"ss-button\" type=\"submit\" value=\"Change Email\" /></p>
			</form>
			<hr class=\"margin-bottom\" />
			<form method=\"post\">
			<input type=\"hidden\" name=\"form\" value=\"password\" />
			<h3>Change Password</h3>
			<p>Current Password<br />
			<input name=\"current-pass\" type=\"password\" value=\"\" /></p>
			<p>New Password<br />
			<input name=\"new-pass\" type=\"password\" value=\"\" /></p>
			<p>Re-type Password<br />
			<input name=\"new-pass2\" type=\"password\" value=\"\" /></p>
			<p><input class=\"ss-button\" type=\"submit\" value=\"Change Password\" /></p>
			</div>
			</form>";
		}
		else {
			print "<p class=\"ss-error\">You don't have permission to access this page</p>";
		}
	} else { // not logged in
		print "<p class=\"ss-warning\">Please log in.</p>";
	}
}

function editEmail($userId, $userEmail, $db) {
	$userEmail = mysql_real_escape_string($userEmail);
	
	$sql = "UPDATE USER SET EMAIL_ADDRESS='$userEmail' WHERE USER_ID='$userId'";
	mysql_query($sql, $db);
}
?>