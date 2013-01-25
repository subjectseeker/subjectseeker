#!/usr/local/bin/php

<?php

/*
Copyright © 2010–2012 Christopher R. Maden, Jessica Perry Hekman and Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

include_once (dirname(__FILE__)."/../config/globals.php");
include_once (dirname(__FILE__)."/../scripts/util.php");
	
$db = ssDbConnect();

$pendingPosts = getPendingNotifications($db);
while ($row = mysql_fetch_array($pendingPosts)) {
	$notificationId = $row["NOTIFICATION_ID"];
	$notificationTypeId = $row["NOTIFICATION_TYPE_ID"];
	$objectId = $row["OBJECT_ID"];
	$objectTypeId = $row["OBJECT_TYPE_ID"];
	
	if ($notificationTypeId == 1) {
		// Get post author user ID
		$sql = "SELECT BA.USER_ID FROM BLOG_POST AS BP INNER JOIN BLOG_AUTHOR AS BA ON BP.BLOG_AUTHOR_ID = BA.BLOG_AUTHOR_ID INNER JOIN USER_PREFERENCE AS UP ON BA.USER_ID = UP.USER_ID WHERE BP.BLOG_POST_ID = '$objectId' AND EMAIL_EDITOR_PICK = '1'";
		$result = mysql_query($sql, $db);
		
		// If post author has no user, try to find any author for this source
		if (mysql_num_rows($result) == 0) {
			$sql = "SELECT BA.USER_ID FROM BLOG_POST AS BP INNER JOIN BLOG_AUTHOR AS BA ON BP.BLOG_ID = BA.BLOG_ID INNER JOIN USER_PREFERENCE AS UP ON BA.USER_ID = UP.USER_ID WHERE BP.BLOG_POST_ID = '$objectId' AND EMAIL_EDITOR_PICK = '1'";
			$result = mysql_query($sql, $db);
		}
		$row = mysql_fetch_array($result);
		
		if ($row == TRUE) {
			$userId = $row["USER_ID"];
			$userEmail = getUserEmail($userId, $db);
			$userName = getUserName($userId, $db);
			$userDisplayName = getDisplayName($userId, $db);
			sendEPEmail ($objectId, $userEmail, $userName, $userDisplayName, $db);
		}
		
	} elseif ($notificationTypeId == 2) {
		if ($objectTypeId == 2) {
			$followerUserId = $row["USER_ID"];
			$user = getUser($objectId, $db);
			$emailFollows = $user["emailFollows"];
			
			if ($emailFollows) {
				sendUserFollowerNotification($followerUserId, $objectId, $db);
			}
		} elseif ($objectTypeId == 4) {
			// TO DO: Add notifications for groups.
		}
	}
	
	updateNotificationStatus($notificationId, $db);
}

// Input: DB Handle
// Output: Get posts marked for notifications
function getPendingNotifications ($db) {
	$sql = "SELECT * FROM NOTIFICATION WHERE NOTIFICATION_STATUS_ID = '0' AND NOW() > DATE_ADD(NOTIFICATION_DATE_TIME,INTERVAL 10 second)";
	$results = mysql_query($sql, $db);
	
	return $results;
}

function updateNotificationStatus($notificationId, $db) {
	$sql = "UPDATE NOTIFICATION SET NOTIFICATION_STATUS_ID='1' WHERE NOTIFICATION_ID = '$notificationId'";
	mysql_query($sql, $db);
}

// Input: Post ID, User Email, User Name, User Display Name, DB Handle
// Action: Send editors pick email
function sendEPEmail ($postId, $userEmail, $userName, $userDisplayName, $db) {
	global $homeUrl;
	global $sitename;
	
	$postData = getPost($postId, $db);
	$postTitle = $postData["BLOG_POST_TITLE"];+
	
	$subject = $sitename. " Editor's Pick";
	$message = "Good news, ".$userDisplayName."!

Your post $postTitle has been recommended by one of our editors.

You can visit your post profile here:
$homeUrl/post/$postId

The ".$sitename." Team.";
	sendMail($userEmail, $subject, $message);
}

function sendUserFollowerNotification($followerUserId, $userId, $db) {
	global $homeUrl, $sitename;
	
	$follower = getUser($followerUserId, $db);
	$followerUserName = $follower["userName"];
	$user = getUser($userId, $db);
	$userName = $user["userName"];
	$userEmail = $user["userEmail"];
	
	$subject = "$followerUserName is now following you on $sitename";
	$message = "You've got a new follower on $sitename.
	
$followerUserName's profile: 
$homeUrl/user/$followerUserName

You can change your email notification preferences on your settings page:
$homeUrl/user/$userName/settings

The ".$sitename." Team.";
	sendMail($userEmail, $subject, $message);
}

?>
