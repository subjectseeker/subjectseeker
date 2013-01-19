<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function userSettings() {
	global $homeUrl, $pages;
	
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
		// Read name on URL
		preg_match('/(?<=\/user\/)[A-Za-z][A-Za-z0-9_]+/', $_SERVER["REQUEST_URI"], $matchResult);
		$userName = $matchResult[0];
		$userId = getUserId($userName, $db);
		
		if (empty($userId)) {
			print "<p class=\"ss-error\">User not found.</p>";
			
		} elseif ($userId == $authUserId || $userPriv > 1) { // Check if user or admin
			// Check which of the forms have been submitted, if any
			if (isset($_POST["form"]) && $_POST["form"] == "personal") {
				$displayName = NULL;
				$url = NULL;
				$bio = NULL;
				$emailEdPicks = NULL;
				$emailAnnouncements = NULL;
				if (isset($_POST["display-name"])) {
					$displayName = $_POST["display-name"];
				}
				if (isset($_POST["url"])) {
					$url = $_POST["url"];
				}
				if (isset($_POST["bio"])) {
					$bio = $_POST["bio"];
				}
				if (isset($_POST["location"])) {
					$location = $_POST["location"];
				}
				if (isset($_POST["email-edpicks"])) {
					$emailEdPicks = $_POST["email-edpicks"];
				}
				if (isset($_POST["email-announcements"])) {
					$emailAnnouncements = $_POST["email-announcements"];
				}
				
				$errors = checkUserPreferences($url, $bio);
				$errors .= checkUserData($authUserId, $userId, NULL, $displayName, NULL, NULL, NULL, NULL, $db);
				if (!empty($errors)) {
					print "$errors";
				} else {
					if (empty($displayName)) {
						$displayName = $userName;
					}
					editUserPreferences($userId, $url, $bio, $location, $emailEdPicks, $emailAnnouncements, $db);
					editDisplayName ($userId, $displayName, $db);
					
					print "<p class=\"ss-successful\">You settings have been updated.</p>";
				}
			} elseif (isset($_POST["form"]) && $_POST["form"] == "email") {
				$email = NULL;
				$userPass = NULL;
				if (isset($_POST["email"])) {
					$newEmail = $_POST["email"];
				}
				if (isset($_POST["current-pass"])) {
					$userPass = $_POST["current-pass"];
				}
				
				$errors = checkUserData($authUserId, $userId, NULL, NULL, $email, $userPass, NULL, NULL, $db);
				
				if (empty($userPass) || empty($email)) {
					$errors .= "<p class=\"ss-error\">Missing value, please submit your current password and your desired new email.</p>";
				}
				
				if (!empty($errors)) {
					print "$errors";
				} else {
					editEmail($userId, $email, $db);
					
					print "<p class=\"ss-successful\">You settings have been updated.</p>";
				}
			} elseif (isset($_POST["form"]) && $_POST["form"] == "password") {
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
				} else {
					if ($newUserPass1 == $newUserPass2) {
						editUserPass($userId, $newUserPass1);
						
						print "<p class=\"ss-successful\">You settings have been updated.</p>";
					}
				}
			}
			
			// Get user info
			$userAvatar = getUserAvatar($userId, $db);
			$userData = getUserData($userId, $db);
			$userTwitter = getSocialNetworkUser(1, $userId, "userId", $db);
			$userGoogle = getSocialNetworkUser(3, $userId, "userId", $db);
			$userDisplayName = $userData["userDisplayName"];
			$userEmail = $userData["userEmail"];
			$userUrl = $userData["userUrl"];
			$userBio = $userData["userBio"];
			$userLocation = $userData["userLocation"];
			$emailEditorsPicks = $userData["emailEditorsPicks"];
			$emailAnnouncements = $userData["emailAnnouncements"];
			
			$originalUrl = getURL();
			
			print "<div>
			<h3>Personal Information</h3>
			<form class=\"avatar-form\" method=\"post\" action=\"".$pages["crop"]->getAddress()."/?url=$originalUrl \" enctype=\"multipart/form-data\">
			<input type=\"hidden\" name=\"userId\" value=\"$userId\" />
			<p class=\"margin-bottom-small\"><img src=\"".$userAvatar["big"]."\" title=\"User avatar\" /></p>
			<p><input type=\"file\" name=\"image\" accept=\"image/*\" /><br /><input class=\"ss-button\" type=\"submit\" value=\"Upload\" /></p>
			</form>
			<form method=\"post\">
			<input type=\"hidden\" name=\"form\" value=\"personal\" />
			<p>Display Name<br />
			<input name=\"display-name\" type=\"text\" value=\"".htmlspecialchars($userDisplayName, ENT_QUOTES)."\" /></p>
			<p>URL<br /><input name=\"url\" type=\"text\" value=\"".htmlspecialchars($userUrl, ENT_QUOTES)."\" /></p>
			<p>Location<br />
			<input name=\"location\" type=\"text\" value=\"".htmlspecialchars($userLocation, ENT_QUOTES)."\" /></p>
			<p>Biography<br />
			<textarea name=\"bio\">$userBio</textarea></p>
			<ul>";
			
			$currentUrl = getURL();
			if ($userTwitter) {
				print "<li class=\"sync-link\"><a title=\"Twitter account\" href=\"https://twitter.com/#!/".$userTwitter["socialNetworkUserName"]."\"><div class=\"twitter-icon\"></div> ".$userTwitter["socialNetworkUserName"]."</a> | <a title=\"Synchronization page\" href=\"".$pages["sync"]->getAddress(TRUE)."/?url=$currentUrl&amp;remove=true&amp;network=twitter\">Remove</a></li>";
			} else {
				print "<li class=\"sync-link\"><a title=\"Synchronization page\" href=\"".$pages["sync"]->getAddress(TRUE)."/?url=$currentUrl\"><div class=\"twitter-icon\"></div> Sync Twitter</a></li>";
			}
			
			if ($userGoogle) {
				print "<li class=\"sync-link\"><a title=\"Google account\" href=\"https://plus.google.com/".$userGoogle["socialNetworkUserExtId"]."\"><div class=\"googleplus-icon\"></div> ".$userGoogle["socialNetworkUserName"]."</a> | <a title=\"Synchronization page\" href=\"".$pages["sync"]->getAddress(TRUE)."/?url=$currentUrl&amp;remove=true&amp;network=google\">Remove</a></li>";
			} else {
				print "<li class=\"sync-link\"><a title=\"Synchronization page\" href=\"".$pages["sync"]->getAddress(TRUE)."/?url=$currentUrl\"><div class=\"googleplus-icon\"></div> Sync Google+</a></li>";
			}
			
			print "</ul>
			<br />
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
			<form method=\"post\" action=\"".$pages["crop"]->getAddress()."/?url=$currentUrl&amp;type=user-banner\" enctype=\"multipart/form-data\">
			<input type=\"hidden\" name=\"userId\" value=\"$userId\" />
			<h3>Profile Banner</h3>
			<p><input type=\"file\" name=\"image\" /> <input class=\"ss-button\" type=\"submit\" value=\"Upload\" /></p>
			</form>
			<hr class=\"margin-bottom\" />
			<form method=\"post\">
			<input type=\"hidden\" name=\"form\" value=\"email\" />
			<h3>Change Email</h3>
			<p>Email<br />
			<input name=\"email\" type=\"text\"	value=\"".htmlspecialchars($userEmail, ENT_QUOTES)."\" /></p>
			<p>Confirmation Password<br />
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
		} else {
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