<?php

/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

include_once (dirname(__FILE__)."/../initialize.php");

// Only recommend if user is logged in.
if (isLoggedIn()) {
	$db = ssDbConnect();
	$authUser = new auth();
	$authUserId = $authUser->userId;
	$authUserPriv = getUserPrivilegeStatus($authUserId, $db);
	$userId = $_REQUEST["userId"];
	$groupId = $_REQUEST["groupId"];
	
	if (isGroupManager($groupId, $authUserId, 2, $db)) {
		removeGroupManager($groupId, $userId, $db);
	}
}

function removeGroupManager($groupId, $userId, $db) {
	$groupId = mysql_real_escape_string($groupId);
	$userId = mysql_real_escape_string($userId);
	
	$sql = "DELETE FROM GROUP_MANAGER WHERE GROUP_ID = '$groupId' AND USER_ID = '$userId'";
	mysql_query($sql, $db);
}

?>