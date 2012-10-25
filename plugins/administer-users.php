<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function adminUsers() {
	$db = ssDbConnect();
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
		
		if ($userPriv > 1) { // moderator or admin
			$step = NULL;
			if (isset($_REQUEST["step"])) {
				$step = $_REQUEST["step"];
			}
			
			// TO DO: Integrate with the API
			$pagesize = "30";
			$offset = "0";
			if (isset($_REQUEST["n"])) {
				$pagesize = $_REQUEST["n"];
			}
			if (isset($_REQUEST["offset"])) {
				$offset = $_REQUEST["offset"];
			}
			
			print "<div class=\"toggle-button\">Display Options</div>
			<div class=\"ss-slide-wrapper\">
			<div class=\"ss-div-2\" id=\"filter-panel\">
			<form method=\"get\">
			<input type=\"hidden\" name=\"filters\" value=\"filters\" />
			Entries per page: <input class=\"small-input\" type=\"text\" name=\"n\" size=\"2\" value=\"$pagesize\"/> | Start at: <input class=\"small-input\" type=\"text\" name=\"offset\" size=\"2\" value=\"$offset\"/>
			<br /><input class=\"ss-button\" type=\"submit\" value=\"Go\" />
			</form>
			</div>
			</div>
			<br />";
			
			if ($step != NULL) {
				$userId = NULL;
				$userName = NULL;
				$userDisplayName = NULL;
				$userStatus = NULL;
				$userEmail = NULL;
				$userPass = NULL;
				$userPrivilege = NULL;
				$delete = NULL;
				if (!empty($_POST["userId"])) {
					$userId = $_POST["userId"];
				}
				if (!empty($_POST["userName"])) {
					$userName = $_POST["userName"];
				}
				if (!empty($_POST["userDisplayName"])) {
					$userDisplayName = $_POST["userDisplayName"];
				}
				if (!empty($_POST["userStatus"])) {
					$userStatus = $_POST["userStatus"];
				}
				if (!empty($_POST["userEmail"])) {
					$userEmail = $_POST["userEmail"];
				}
				if (!empty($_POST["userPass"])) {
					$userPass = $_POST["userPass"];
				}
				if (!empty($_POST["userPrivilege"])) {
					$userPrivilege = $_POST["userPrivilege"];
				}
				if (!empty($_REQUEST["delete"])) {
					$delete = $_REQUEST["delete"];
				}
				$oldUserName = getUserName($userId, $db);
				
				// Check that submitted data is valid
				$errors = checkUserData($authUserId, $userId, NULL, $userDisplayName, $userEmail, NULL, $userPass, $userPass, $db);
				// Check for missing values.
				if (empty($userName)) {
					$errors .= "<p class=\"ss-error\">You must submit a user name.</p>";
				}
				if (empty($userEmail)) {
					$errors .= "<p class=\"ss-error\">You must submit an email.</p>";
				}
				
				if ($step == 'confirmed' || ($errors == NULL && $step == 'edit')) {			
					editUser ($userId, $userName, $userDisplayName, $userStatus, $userEmail, $userPrivilege, $userPass, $db);
					global $debugSite;
					if ($delete == TRUE && $debugSite == "true") {
						deleteUser($userId, $db);
					}
					
					print "<p class=\"ss-successful\">$userName (id $userId) was updated.</p>";	
				} 
				elseif ($errors != NULL && $step == 'edit') {
					print "<div class=\"margin-bottom\"><p>$oldUserName (id $userId)</p>
					$errors
					<form class=\"ss-div\" method=\"post\">
					<input type=\"hidden\" name=\"step\" value=\"confirmed\" />
					<input type=\"hidden\" name=\"userId\" value=\"".htmlspecialchars($userId, ENT_QUOTES)."\" />
					<input type=\"hidden\" name=\"userName\" value=\"".htmlspecialchars($userName, ENT_QUOTES)."\" />
					<input type=\"hidden\" name=\"userDisplayName\" value=\"".htmlspecialchars($userDisplayName, ENT_QUOTES)."\" />
					<input type=\"hidden\" name=\"userStatus\" value=\"".htmlspecialchars($userStatus, ENT_QUOTES)."\" />
					<input type=\"hidden\" name=\"userEmail\" value=\"".htmlspecialchars($userEmail, ENT_QUOTES)."\" />
					<input type=\"hidden\" name=\"userPrivilege\" value=\"".htmlspecialchars($userPrivilege, ENT_QUOTES)."\" />
					<p>There has been an error, are you sure you want to apply these changes?</p>
					<input class=\"ss-button\" name=\"confirm\" type=\"submit\" value=\"Confirm\" />
					</form>
					</div>";
				}
			}
			$userList = getUsers ($pagesize, $offset, $db);
			$total = array_shift(mysql_fetch_array(mysql_query("SELECT FOUND_ROWS()", $db)));
			if ($userList == null) {
				print "There are no more users in the system.<br />";
			}
			else {
				print "<div class=\"entries\">";
				while ($row = mysql_fetch_array($userList)) {
					$userId = $row["USER_ID"];
					$userName = $row["USER_NAME"];
					$userStatusId = $row["USER_STATUS_ID"];
					$userPrivilegeId = $row["USER_PRIVILEGE_ID"];
					$userEmail = $row["EMAIL_ADDRESS"];
					$userDisplayName = $row["DISPLAY_NAME"];
					$userStatus = ucwords(userStatusIdToName ($userStatusId, $db));
					$userPrivilege = ucwords(userPrivilegeIdToName ($userPrivilegeId, $db));
					print "<div class=\"ss-entry-wrapper\">
					$userId | $userName | $userStatus | $userPrivilege
					<div class=\"ss-slide-wrapper\">
					<br />
					<form method=\"post\">
					<input type=\"hidden\" name=\"step\" value=\"edit\" />
					<input type=\"hidden\" name=\"userId\" value=\"$userId\" />\n
					<p>User Name<br />
					<input type=\"text\" name=\"userName\" value=\"".htmlspecialchars($userName, ENT_QUOTES)."\"/></p>\n
					<p>Display Name<br />
					<input type=\"text\" name=\"userDisplayName\" value=\"".htmlspecialchars($userDisplayName, ENT_QUOTES)."\"/></p>\n
					<p>Email<br />
					<input type=\"text\" name=\"userEmail\" value=\"".htmlspecialchars($userEmail, ENT_QUOTES)."\"/></p>\n
					<p>Password<br />
					<input type=\"password\" name=\"userPass\"/></p>\n
					<p>User Status<br />
					<select name='userStatus'>\n";
					$statusList = getUserStatusList ($db);
					while ($row = mysql_fetch_array($statusList)) {
						print "<option value='" . $row["USER_STATUS_ID"] . "'";
						if ($row["USER_STATUS_ID"] == $userStatusId) {
							print " selected";
									}
						print ">" . ucwords($row["USER_STATUS_DESCRIPTION"]) . "</option>\n";
					}
					print "</select></p>\n
					<p>User Privilege<br />
					<select name='userPrivilege'>\n";
					$privilegeList = getUserPrivilegeList ($db);
					while ($row = mysql_fetch_array($privilegeList)) {
						print "<option value='" . $row["USER_PRIVILEGE_ID"] . "'";
						if ($row["USER_PRIVILEGE_ID"] == $userPrivilegeId) {
							print " selected";
									}
						print ">" . ucwords($row["USER_PRIVILEGE_DESCRIPTION"]) . "</option>\n";
					}
					print "</select></p>\n";
					global $debugSite;
					if ($debugSite == "true" && $userPriv > 0) {
						print "<p><input type=\"checkbox\" class=\"checkbox\" name=\"delete\" value=\"1\" /> Delete from database (debug only).</p>";
					}
					print "<p><input id=\"submit-$userId\" class=\"ss-button\"type=\"submit\" value=\"Submit\" /></p>
					</form>
					</div>
					</div>";
				}
				print "</div>";
			}
			global $pages;
			pageButtons ($pages["administer-users"]->getAddress(), $pagesize, $total);
			
		} else { // not moderator or admin
			print "<p class=\"ss-warning\">You are not authorized to administrate users.</p>";
		}
	} else { // not logged in
		print "<p class=\"ss-warning\">Please log in.</p>";
	}
}
?>