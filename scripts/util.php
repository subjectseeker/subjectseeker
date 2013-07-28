<?php

/*
Copyright © 2010–2012 Christopher R. Maden, Jessica Perry Hekman and Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

global $basedir;

include_once(dirname(__FILE__)."/api.php");

/*
 * DB Connection functions
 */

// Open a connection to the DB. Return a handle to it.
function ssDbConnect() {

	global $dbUser;
	global $dbPass;
	global $host;
	global $dbName;

	$cid = mysql_connect($host,$dbUser,$dbPass);
	if (empty($cid)) { echo("ERROR: " . mysql_error() . "\n"); }
	mysql_select_db($dbName, $cid);

	$success = mysql_set_charset("utf8", $cid);
	if (empty($success)) { echo("ERROR: " . mysql_error() . "\n"); }

	return $cid;
}

// Given a handle to the DB, close the connection.
function ssDbClose($dbConnection) {
	mysql_close($dbConnection);
}

/*
 * Log In
 */

// Used to get and manage current user info
class auth {
	var $errors = array();
	var $userId = NULL;
	var $userName = NULL;
	
	// If logged in, set user ID and user name
	function __construct() {
		if (isset($_SESSION["logged"])) {	
			$db = ssDbConnect();
		
			$this->userId = $_SESSION["authUserId"];
			$this->userName = getUserName($this->userId, $db);
		}
	}
	
	// Log in user if possible
	public function loginUser($userId, $rememberUser) {
		session_regenerate_id(TRUE);
		$_SESSION["logged"] = TRUE;
		$_SESSION["authUserId"] = $userId;
		
		if ($rememberUser) {
			$db = ssDbConnect();
			createCookie($userId, $db);
		}
	}
	
	public function logout() {
		$_SESSION = array();
		setcookie ("ss-login", "", time() - 3600, "/");
		session_destroy();
	}
	
	public function validateUser($userName, $userPass = NULL) {
		$db = ssDbConnect();
		
		$userId = getUserId($userName, $db);
		if (!$userId) {
			$error["id"] = 1;
			$error["text"] = "User name was not found in our system";
			array_push($this->errors, $error);
			
			return FALSE;
		}
		
		if ($userPass) {
			$hashedPass = getUserPass($userId, $db);
			$hasher = new PasswordHash(8, TRUE);
			if (!$hasher->CheckPassword($userPass, $hashedPass)) {
				$error["id"] = 2;
				$error["text"] = "User name and password don't match";
				array_push($this->errors, $error);
			}
		}
		
		// Get user status ID
		$userStatusId = getUserStatus($userId, $db);
		if ($userStatusId == 1 || $userStatusId == 2) { // Check if user account has been disabled
			$error["id"] = 3;
			$error["text"] = "User account is disabled";
			array_push($this->errors, $error);
			
		} elseif ($userStatusId == 3) { // Check if user account is unverified
			$error["id"] = 4;
			$error["text"] = "User email is unverified";
			array_push($this->errors, $error);
			
		} elseif ($userStatusId != 0) { // User doesn't have a valid status
			$error["id"] = 5;
			$error["text"] = "Invalid status";
			array_push($this->errors, $error);
		}
		
		if ($this->errors)
			return FALSE;
		
		return $userId;
	}
	
}

// Action: Check if current user is logged in.
function isLoggedIn() {
	$authUser = new auth;
	if (isset($authUser->userId, $authUser->userName)) {
		return TRUE;
	}
	return FALSE;
}

// Input: User ID, Cookie Secret Code, DB Handle
// Action: Valide cookie secret code
function validateCookie($userId, $cookieCode, $db) {
	$userId = mysql_real_escape_string($userId);
	$cookieCode = mysql_real_escape_string($cookieCode);
	
	$sql = "SELECT USER_ID FROM USER_SECRET_CODE WHERE USER_ID = '$userId' AND USER_SECRET_CODE_TEXT = '$cookieCode' AND USER_SECRET_CODE_SOURCE_ID = '1' AND DATE_ADD(USER_SECRET_CODE_DATE_TIME, INTERVAL 3 MONTH) > NOW()";
	$result = mysql_query($sql, $db);
	
	// Remove used cookie
	removeCookie($userId, $cookieCode, $db);
	
	if (!$result || mysql_num_rows($result) == 0) {
		return FALSE;
	}

	$row = mysql_fetch_array($result);
	return $row["USER_ID"];
}

// Input: User ID, DB Handle
// Action: Create login cookie on browser and database
function createCookie($userId, $db) {
	$cookieCode = md5(uniqid());
	setcookie("ss-login", "$userId/$cookieCode", strtotime("+90 day"), "/");
	
	$sql = "REPLACE INTO USER_SECRET_CODE (USER_ID, USER_SECRET_CODE_TEXT, USER_SECRET_CODE_DATE_TIME, USER_SECRET_CODE_SOURCE_ID) VALUES ('$userId', '$cookieCode', NOW(), '1')";
	mysql_query($sql, $db);
}

// Input: User ID, Secret Cookie Code, DB Handle
// Action: Remove cookie from database
function removeCookie($userId, $cookieCode, $db) {
	$userId = mysql_real_escape_string($userId);
	$cookieCode = mysql_real_escape_string($cookieCode);
	
	$sql = "DELETE FROM USER_SECRET_CODE WHERE USER_ID = '$userId' AND USER_SECRET_CODE_TEXT = '$cookieCode' AND USER_SECRET_CODE_SOURCE_ID = '1';
	DELETE FROM USER_SECRET_CODE WHERE USER_SECRET_CODE_SOURCE_ID = '1' AND DATE_ADD(USER_SECRET_CODE_DATE_TIME, INTERVAL 3 MONTH) < NOW();";
	mysql_query($sql, $db);
}

// Input: User ID, Secret Code Source ID, DB Handle
// Action: Create login cookie on browser and database
function createSecretCode ($userId, $codeSourceId, $db) {
	$recoveryCode = md5(uniqid());
	
	$sql = "REPLACE INTO USER_SECRET_CODE (USER_ID, USER_SECRET_CODE_TEXT, USER_SECRET_CODE_DATE_TIME, USER_SECRET_CODE_SOURCE_ID) VALUES ('$userId', '$recoveryCode', NOW(), '$codeSourceId')";
	mysql_query($sql, $db);
	
	return $recoveryCode;
}

// Input: Secret Code, Secret Code Source ID, DB Handle
// Output: User ID or NULL
function secretCodeToUserId($codeText, $codeSourceId, $db) {
	$recoveryCode = mysql_real_escape_string($codeText);
	
	$sql = "SELECT USER_ID, USER_SECRET_CODE_DATE_TIME FROM USER_SECRET_CODE WHERE USER_SECRET_CODE_TEXT='$codeText' AND USER_SECRET_CODE_SOURCE_ID = '$codeSourceId'";
	$result = mysql_query($sql, $db);
	if ($result == null || mysql_num_rows($result) == 0) {
		return null;
	}
	$row = mysql_fetch_array($result);
	
	$codeDate = strtotime($row["USER_SECRET_CODE_DATE_TIME"]);
	$expirationDate = strtotime("-1 day");
	
	$codeStatus = array();
	if ($codeDate < $expirationDate) {
		$codeStatus["userId"] = $row["USER_ID"];
		$codeStatus["expired"] = TRUE;
		return $codeStatus;
	}
	
	$codeStatus["userId"] = $row["USER_ID"];
	
	return $codeStatus;
}

// Input: User ID, Secret Code Source ID, DB Handle
// Action: Remove secret code from database
function removeSecretCode($userId, $codeSourceId, $db) {
	$sql = "DELETE FROM USER_SECRET_CODE WHERE USER_ID = '$userId' AND USER_SECRET_CODE_SOURCE_ID = '$codeSourceId'";
	mysql_query($sql, $db);
}

// Input: Recovery Secret Code, User Email, User Name, User Display Name
// Action: Send email to reset password
function sendRecoveryEmail ($recoveryCode, $userEmail, $userName, $userDisplayName) {
	global $pages;
	global $sitename;
	
	$subject = $sitename. " Account Recovery";
	$message = "Hello, ".$userDisplayName.".

A request was made to recover your ".$sitename." account. If you didn't make this request, please ignore this email.

User name: ".$userName."

To reset your password, use the following code in the recovery page: ".$recoveryCode."

Or visit this link: ".$pages["login"]->getAddress(TRUE)."/?step=verify-recovery&recovery-code=".$recoveryCode."

The ".$sitename." Team.";
	sendMail($userEmail, $subject, $message);
}

// Input: Verification Secret Code, User Email, User Name, User Display Name
// Action: Send email to verify account
function sendVerificationEmail ($verificationCode, $userEmail, $userName, $userDisplayName) {
	global $pages;
	global $sitename;
	
	$subject = $sitename. " Email Verification";
	$message = "Hello, ".$userDisplayName.".

To verify your ".$sitename." account, use the following code in the verification page: ".$verificationCode."

Or visit this link: ".$pages["login"]->getAddress(TRUE)."/?step=verify-email&verification-code=".$verificationCode."

The ".$sitename." Team.";
	sendMail($userEmail, $subject, $message);
}

/*
 * Crawler
 */

// Input: Array with blog data, DB handle
// Action: Scans blog for new posts and adds them to the system.
function crawlBlogs($site, $db) {
	$siteFeedUrl = $site["siteFeedUrl"];
	$siteId = $site["siteId"];
	$siteName = $site["siteName"];
	$postIds = array();
	$message = NULL;

	$feed = getSimplePie($siteFeedUrl);
	if ($feed->error()) {
		$message = "<p class=\"ss-error\">ERROR: $siteFeedUrl (ID $siteId): " . $feed->error() . "</p>\n";
		return $message;
	}
	foreach ($feed->get_items(0, 50) as $item) {
		$postId = addSimplePieItem($item, $feed->get_language(), $siteId, $db);
		$item = NULL;
		if (!empty($postId)) {
			$postIds[] = $postId;
		}
	}
	markCrawled($siteId, $db);

	$newPostCount = count($postIds);
	$message = "<p class=\"ss-successful\">$siteName (ID $siteId) has been scanned; $newPostCount new posts found.</p>";
	$feed = NULL;

	return $message;
}

/*
 * Cache
 */

class cache {
	// TODO save cache_time in ss-globals.php
	var $cacheTime = 3600;//How much time will keep the cache files in seconds.
	var $cacheDir = "";
	var $caching = FALSE;
	var $file = "";
	
	function __construct($name, $varCache = FALSE, $useUrl = TRUE) {
		global $cachedir;
		global $cacheTime;
		$this->cacheDir = $cachedir;
		$this->cacheTime = $cacheTime;
		
		$fileName = md5($name);
		if ($useUrl == TRUE) {
			$fileName = md5($name . urlencode($_SERVER["REQUEST_URI"]));
		}
		
		// Constructor of the class
		$this->file = $this->cacheDir . "/$fileName.txt";
		if (file_exists($this->file) && (filemtime($this->file) + 3600) > time()) {
			if ($varCache != TRUE) {
				$this->htmlCache();
			}
		} else {
			// Create cache
			$this->caching = true;
			if ($varCache != TRUE)
				ob_start();
		}
	}
	
	private function htmlCache() {
		//Grab the cache:
		$handle = fopen($this->file , "r");
		do {
			$data = fread($handle, 8192);
			if (strlen($data) == 0) {
				break;
			}
			echo $data;
		} while (true);
		fclose($handle);
	}
	
	public function storeHtml() {
		// You should have this at the end of each page
		if ($this->caching) {
			// You were caching the contents so display them, and write the cache file
			$data = ob_get_clean();
			echo $data;
			$fp = fopen($this->file, "w");
			fwrite ($fp , $data);
			fclose ($fp);
		}
	}
	
	public function storeVars($vars) {
		$contentCache = serialize($vars);
		$fp = fopen($this->file,"w"); // open file with Write permission
		fputs($fp, $contentCache);
		fclose($fp);
	}
	
	public function varCache() {
		$cacheContent = unserialize(implode('',file($this->file)));
		
		return $cacheContent;
	}
}

/*
 * Curl functions
 */

// Input: type of search; search parameters (hash array)
// Output: Curl for performing the search
function getPage ($url, $post = NULL) {
	$ch = curl_init();		// initialize curl handle
	curl_setopt($ch, CURLOPT_URL,$url); // set url to post to
	curl_setopt($ch, CURLOPT_FAILONERROR, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
	curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 4s
	if ($post) {
		curl_setopt($ch, CURLOPT_POST, 1); // set POST method
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	$data = curl_exec($ch);
	curl_close($ch);
	
	return $data;
}

// Input: map of params
// Return: string of param name=value formatted for appending to URL in GET query
function paramArrayToString($params) {
	if ($params == null || count ($params) == 0) {
		return "";
	}
	$retVal = "?";
	$first = true;

	foreach ($params as $name => $value) {
		if (empty($first)) {
			$retVal .= "&";
		}
		$first = false;
		$retVal .= "$name=$value";
	}
	return $retVal;
}

/*
 * HTTP request functions
 */
 
// Input: Optional http query, allow override.
// Output: list of SSFilter objects representing search query
function parseHttpParams($query = NULL, $allowOverride = TRUE) {
	parse_str($query, $parsedQuery);
	// Allow users to override the parameters with GET or POST
	if ($allowOverride == TRUE) {
		$parsedQuery = array_merge($parsedQuery, $_REQUEST);
	}
	
	$i = 0;
	$searchParams["string"] = "";
	$searchParams["parameters"] = array();
	$searchParams["filters"] = array();
	while (array_key_exists ("filter$i", $parsedQuery)) {
		$searchParams["filters"][$i]["name"] = $parsedQuery["filter$i"];
		$searchParams["string"] .= "&filter$i=".$parsedQuery["filter$i"];
		$searchParams["filters"][$i]["value"] = NULL;
		$searchParams["filters"][$i]["modifier"] = NULL;

		if (array_key_exists("value$i", $parsedQuery)) {
			$searchParams["filters"][$i]["value"] = $parsedQuery["value$i"];
			$searchParams["string"] .= "&value$i=".$parsedQuery["value$i"];
		}

		if (array_key_exists("modifier$i", $parsedQuery)) {
			$searchParams["filters"][$i]["modifier"] = $parsedQuery["modifier$i"];
			$searchParams["string"] .= "&modifier$i=".$parsedQuery["modifier$i"];
		}
		
		++$i;
	}
	
	$results = array();
	$parameters = array("n", "offset", "type", "show-all", "citation-in-summary", "source-in-title", "sort", "order", "max-date", "min-date", "max-id", "min-id");
	foreach ($parameters as $parameter) {
		$searchParams["parameters"][$parameter] = NULL;
		if (isset($parsedQuery[$parameter])) {
			$searchParams["parameters"][$parameter] = $parsedQuery[$parameter];
			$searchParams["string"] .= "&$parameter=".$parsedQuery[$parameter];
		}
	}
	
	$searchParams["string"] = substr($searchParams["string"], 1);
	
	return $searchParams;
}

// Get current url
function getURL () {
	$pageURL = 'http';
	
	if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
		$pageURL .= "s";
	}
	
	$pageURL .= "://";
	
	if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	}
	else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	
	return $pageURL;	
}

// Input: URL
// Return: true if URL starts with http:// or https://, otherwise false
function hasProtocol ($url) {
	return (preg_match("/^https?:\/\//", $url) > 0);
}

// Return: string appropriate to tack on to the end of a URL
function getUrlParamString($ignoreParams) {
	$paramStr = "";
	foreach ($_REQUEST as $paramName => $paramValue) {
		$paramValue = $paramValue;
		if ($ignoreParams == null || ! in_array($paramName, $ignoreParams)) {
			if ($paramStr !== "") {
				$paramStr .= "&";
			}
			$paramStr .= "$paramName=$paramValue";
		}
	}

	if ($paramStr !== "") {
		return $paramStr;
	}
	return null;
}

/*
 * HTML cleaning functions
 */
function sanitize( $htmlString ) {
	return str_replace( array( '&', '<' ),
											array( '&amp;', '&lt;' ),
											$htmlString );
}

function insanitize( $htmlString ) {
	return str_replace( array( '&amp;', '&lt;' ),
											array( '&', '<' ),
											$htmlString );
}

/*
 * General useful functions
 */
 
function getObjectTypeName($objectTypeId) {
	$objectTypes = array("1" => "post", "2" => "user", "3" => "site", "4" => "group");
	
	return $objectTypes[$objectTypeId];
}

function getObjectTypeId($objectTypeName) {
	$objectTypes = array("post" => "1", "user" => "2", "site" => "3", "group" => "4");
	
	return $objectTypes[$objectTypeName];
}
 
function sendMail($userEmail, $subject, $message) {
	global $sitename, $contactEmail;
	$headers = "Mime-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nFrom: $sitename <$contactEmail>\r\nReply-To: $sitename <$contactEmail>\r\nX-Mailer: PHP/" . phpversion();
	
	if (! mail($userEmail, $subject, $message, $headers)) {
		// TODO: Log this.
	}
}

function getUserActivity($id, $typeId, $userId, $actionType, $limit, $db) {
	global $homeUrl;
	$userName = "";
	$socialNetworkUserId = "";
	if ($userId) {
		$userName = getUserName($userId, $db);
		$userAvatar = getUserAvatar($userId, $db);
		$userTwitter = getSocialNetworkUser(1, $userId, "userId", $db);
		$userAvatarSmall = $userAvatar["small"];
		$socialNetworkUserId = $userTwitter["socialNetworkUserId"];
		$twitterUserName = $userTwitter["socialNetworkUserName"];
	}
	
	$actions = array();
	
	if ($actionType == NULL || $actionType == "posts") {
		$query = "filter0=author&modifier0=user-name&value0=$userName&n=$limit";
		if ($id) {
			$query .= "filter1=identifier&value1=$id";
		}
		
		$api = new API;
		$api->searchDb($query, FALSE, "post");
		foreach ($api->posts as $post) {
			if (empty($userId)) {
				$postUserId = getAuthorUserId($post["postAuthorId"], $db);
				$userName = getUserName($postUserId, $db);
				$userAvatar = getUserAvatar($postUserId, $db);
				$userAvatarSmall = $userAvatar["small"];
			}
			$action["avatar"] = "<img src='$userAvatarSmall' alt='User Avatar' />";
			$action["title"] = "<a class='action-user' href='$homeUrl/user/$userName'>$userName</a> posted <a href='$homeUrl/post/".$post["postId"]."'>".$post["postTitle"]."</a> on <a href='".$post["siteUrl"]."'>".$post["siteName"]."</a>";
			$action["text"] = strip_tags($post["postSummary"]);
			$action["date"] = $post["postDate"];
			$action["type"] = "post";
			
			array_push($actions, $action);
		}
	}
	
	if ($actionType == NULL || $actionType == "recs") {
		$recommendations = getRecommendations($id, $typeId, $userId, NULL, $limit, 0, $db); 
		foreach ($recommendations as $rec) {
			if (empty($userId)) {
				$userName = getUserName($rec["userId"], $db);
				$userAvatar = getUserAvatar($rec["userId"], $db);
				$userAvatarSmall = $userAvatar["small"];
			}
			if ($rec["objectTypeId"] == 1) {
				$post = getPost($rec["objectId"], $db);
				$action["title"] = "<a class='action-user' href='$homeUrl/user/$userName'>$userName</a> recommended <a href='$homeUrl/post/".$post["BLOG_POST_ID"]."'>".$post["BLOG_POST_TITLE"]."</a>";
				
			} elseif ($rec["objectTypeId"] == 2) {
				$user = getUser($rec["objectId"], $db);
				$action["title"] = "<a class='action-user' href='$homeUrl/user/$userName'>$userName</a> recommended user <a href='$homeUrl/user/".$user["userName"]."'>".$user["userName"]."</a>";
				
			} elseif ($rec["objectTypeId"] == 3) {
				$site = getSite($rec["objectId"], $db);
				$action["title"] = "<a class='action-user' href='$homeUrl/user/$userName'>$userName</a> recommended site <a href='$homeUrl/site/".$site["siteId"]."'>".$site["siteName"]."</a>.";
				
			} elseif ($rec["objectTypeId"] == 4) {
				$group = getGroup($rec["objectId"], $db);
				$groupId = $group["groupId"];
				$groupName = $group["groupName"];
				$action["title"] = "<a class='action-user' href='$homeUrl/user/$userName'>$userName</a> recommended group <a href='$homeUrl/group/$groupId'>$groupName</a>";
			}
			$action["avatar"] = "<img src='$userAvatarSmall' alt='User Avatar' />";
			$action["date"] = $rec["recDate"];
			$action["text"] = NULL;
			$action["type"] = "rec";
			
			array_push($actions, $action);
		}
	}
	
	if ($actionType == NULL || $actionType == "comments") {
		$comments = getComments($id, $typeId, NULL, $userId, $limit, 0, $db); 
		foreach ($comments as $comment) {
			if (empty($userId)) {
				$userName = getUserName($comment["userId"], $db);
				$userAvatar = getUserAvatar($comment["userId"], $db);
				$userAvatarSmall = $userAvatar["small"];
			}
			if ($comment["objectTypeId"] == 1) {
				$post = getPost($comment["objectId"], $db);
				$action["title"] = "<a class='action-user' href='$homeUrl/user/$userName'>$userName</a> commented on <a href='$homeUrl/post/".$post["BLOG_POST_ID"]."'>".$post["BLOG_POST_TITLE"]."</a>";
				
			} elseif ($comment["objectTypeId"] == 3) {
				$site = getSite($comment["objectId"], $db);
				$action["title"] = "<a class='action-user' href='$homeUrl/user/$userName'>$userName</a> commented on <a href='$homeUrl/site/".$site["siteId"]."'>".$site["siteName"]."</a>";
				
			} elseif ($comment["objectTypeId"] == 4) {
				$group = getGroup($comment["objectId"], $db);
				$groupId = $group["groupId"];
				$groupName = $group["groupName"];
				$action["title"] = "<a class='action-user' href='$homeUrl/user/$userName'>$userName</a> commented on group <a href='$homeUrl/group/$groupId'>$groupName</a>";
			}
			$action["avatar"] = "<img src='$userAvatarSmall' alt='User Avatar' />";
			$action["text"] = $comment["commentText"];
			$action["date"] = $comment["commentDate"];
			$action["type"] = "comment";
			
			array_push($actions, $action);
		}
	}
	
	if (($actionType == NULL || $actionType == "tweets") && (!$userId || $socialNetworkUserId)) {
		$userTweets = getUserTweets($socialNetworkUserId, $limit, 0, $db);	
		foreach ($userTweets as $tweet) {
			if (empty($userId)) {
				$socialNetworkUser = getSocialNetworkUser (1, $tweet["socialNetworkUserId"], "socialNetworkUserId", $db);
				$twitterUserName = $socialNetworkUser["socialNetworkUserName"];
				
				if ($tweetUserId = $socialNetworkUser["userId"]) {
					$userName = getUserName($tweetUserId, $db);
					$userName = "<a class='action-user' href='$homeUrl/user/$userName'>$userName</a>";
					$userAvatar = getUserAvatar($tweetUserId, $db);
					$userAvatarSmall = $userAvatar["small"];
				} else {
					$userName = $twitterUserName;
					$userAvatarSmall = $socialNetworkUser["socialNetworkUserAvatar"];
				}
			}
			
			$action["avatar"] = "<img src='$userAvatarSmall' height='50' width='50' alt='User Avatar' />";
			$action["title"] = "<span class='action-user'>$userName</span> tweeted: <span class='action-handle'>@$twitterUserName</span>";
			$action["text"] = tweetContent($tweet["tweetText"]);
			$action["date"] = $tweet["tweetDate"];
			$action["type"] = "tweet";
			
			array_push($actions, $action);
		}
	}
	
	usort($actions, "sortDate");
	$actions = array_slice($actions, 0, $limit);
	
	return $actions;
}

function getRecommendations($id, $typeId, $userId, $editorRecs, $limit, $offset, $db) {
	$from = "RECOMMENDATION rec";
	$where = "";
	$limit = "";
	$offset = "";
	
	$whereList = array();
	if ($id) {
		$whereList[] = "rec.OBJECT_ID = '$id'";
	}
	if ($typeId) {
		$whereList[] = "rec.OBJECT_TYPE_ID = '$typeId'";
	}
	if ($userId) {
		$whereList[] = "rec.USER_ID = '$userId'";
	}
	if ($editorRecs) {
		$from .= " INNER JOIN USER user ON user.USER_ID = rec.USER_ID";
		$whereList[] = "user.USER_PRIVILEGE_ID > 0";
	}
	if ($limit) {
		$limit = "LIMIT $limit";
	}
	if ($offset) {
		$offset = "OFFSET $offset";
	}
	
	
	if ($whereList) {
		$where = "WHERE ".implode(" AND ", $whereList);
	}

	$sql = "SELECT * FROM $from $where ORDER BY REC_DATE_TIME DESC $limit $offset";
	$results = mysql_query($sql, $db);
	
	if ($results == NULL || mysql_num_rows($results) == 0) {
		return array();
	}
	
	$recommendations = array();
	while ($row = mysql_fetch_array($results)) {
		$recommendation["recId"] = $row["RECOMMENDATION_ID"];
		$recommendation["objectId"] = $row["OBJECT_ID"];
		$recommendation["objectTypeId"] = $row["OBJECT_TYPE_ID"];
		$recommendation["userId"] = $row["USER_ID"];
		$recommendation["recDate"] = $row["REC_DATE_TIME"];
		array_push($recommendations, $recommendation);
	}
	
	return $recommendations;
}

function getComments($id, $typeId, $sourceId, $userId, $limit, $offset, $db) {
	$where = "";
	$limit = "";
	$offset = "";
	
	$whereList = array();
	if ($id)
		$whereList[] = "OBJECT_ID = '$id'";
	if ($typeId)
		$whereList[] = "OBJECT_TYPE_ID = '$typeId'";
	if ($sourceId)
		$whereList[] = "COMMENT_SOURCE_ID = '$sourceId'";
	if ($userId)
		$whereList[] = "USER_ID = '$userId'";
	if ($limit)
		$limit = "LIMIT $limit";
	if ($offset)
		$offset = "OFFSET $offset";
	
	if ($whereList) {
		$where = "WHERE ".implode(" AND ", $whereList);
	}

	$sql = "SELECT * FROM COMMENT $where ORDER BY COMMENT_DATE_TIME DESC $limit $offset";
	$results = mysql_query($sql, $db);
	
	if ($results == NULL || mysql_num_rows($results) == 0) {
		return array();
	}
	
	$comments = array();
	while ($row = mysql_fetch_array($results)) {
		$comment["commentId"] = $row["COMMENT_ID"];
		$comment["objectId"] = $row["OBJECT_ID"];
		$comment["objectTypeId"] = $row["OBJECT_TYPE_ID"];
		$comment["commentSourceId"] = $row["COMMENT_SOURCE_ID"];
		$comment["commentDate"] = $row["COMMENT_DATE_TIME"];
		$comment["commentText"] = $row["COMMENT_TEXT"];
		$comment["userId"] = $row["USER_ID"];
		array_push($comments, $comment);
	}
	
	return $comments;
}

function getComment($commentId, $db) {
	$sql = "SELECT * FROM COMMENT WHERE COMMENT_ID = '$commentId' ORDER BY COMMENT_DATE_TIME DESC";
	$result = mysql_query($sql, $db);
	
	if ($result == NULL || mysql_num_rows($result) == 0) {
		return NULL;
	}
	
	$row = mysql_fetch_array($result);
	$comment["commentId"] = $row["COMMENT_ID"];
	$comment["commentedId"] = $row["OBJECT_ID"];
	$comment["commentTypeId"] = $row["OBJECT_TYPE_ID"];
	$comment["commentSourceId"] = $row["COMMENT_SOURCE_ID"];
	$comment["commentDate"] = $row["COMMENT_DATE_TIME"];
	$comment["commentText"] = $row["COMMENT_TEXT"];
	$comment["userId"] = $row["USER_ID"];
	
	return $comment;
}

function getFollowers($objectId, $objectTypeId, $userId, $db) {
	$sql = "SELECT * FROM FOLLOWER WHERE OBJECT_ID = '$objectId' AND OBJECT_TYPE_ID = '$objectTypeId'";
	if ($userId) {
		$sql .= " AND USER_ID = '$userId'";
	}
	$results = mysql_query($sql, $db);
	
	$followers = array();
	while ($row = mysql_fetch_array($results)) {
		$follower["userId"] = $row["USER_ID"];
		$follower["objectId"] = $row["OBJECT_ID"];
		$follower["objectTypeId"] = $row["OBJECT_TYPE_ID"];
		array_push($followers, $follower);
	}
	
	return $followers;
}

function sortDate($a, $b) {
	 return strtotime($b["date"]) - strtotime($a["date"]);
}

function getUserTweets($socialNetworkUserId, $limit, $offset, $db) {
	$sql = "SELECT * FROM TWEET";
	if ($socialNetworkUserId) {
		$sql .= " WHERE SOCIAL_NETWORK_USER_ID = '$socialNetworkUserId'";
	}
	$sql .= " ORDER BY TWEET_DATE_TIME DESC LIMIT $limit OFFSET $offset";
	$results = mysql_query($sql, $db);
	
	if ($results == NULL || mysql_num_rows($results) == 0) {
		return array();
	}
	
	$tweets = array();
	while ($row = mysql_fetch_array($results)) {
		$tweet["tweetId"] = $row["TWEET_ID"];
		$tweet["tweetTwitterId"] = $row["TWEET_TWITTER_ID"];
		$tweet["tweetText"] = $row["TWEET_TEXT"];
		$tweet["tweetDate"] = $row["TWEET_DATE_TIME"];
		$tweet["socialNetworkUserId"] = $row["SOCIAL_NETWORK_USER_ID"];
		array_push($tweets, $tweet);
	}
	
	return $tweets;
}

/*
 * DB query functions
 */
 
function follow($id, $typeId, $userId, $db) {
	$sql = "INSERT IGNORE INTO FOLLOWER (USER_ID, OBJECT_ID, OBJECT_TYPE_ID) VALUES ($userId, $id, $typeId)";
	mysql_query($sql, $db);
}

function unfollow ($id, $typeId, $userId, $db) {
	$sql = "DELETE FROM FOLLOWER WHERE OBJECT_ID = '$id' AND OBJECT_TYPE_ID = '$typeId' AND USER_ID = '$userId'";
	mysql_query($sql, $db);
}
 
function addRecommendation ($id, $typeId, $userId, $db) {
	// Insert recommendation
	$timestamp = dateStringToSql("now");
	$sql = "INSERT IGNORE INTO RECOMMENDATION (OBJECT_ID, OBJECT_TYPE_ID, USER_ID, REC_DATE_TIME) VALUES ($id, $typeId, $userId, '$timestamp')";
	mysql_query($sql, $db);
}

function removeRecommendation ($id, $typeId, $userId, $db) {
	$sql = "DELETE FROM RECOMMENDATION WHERE OBJECT_ID = '$id' AND USER_ID = '$userId' AND OBJECT_TYPE_ID = '$typeId'";
	mysql_query($sql, $db);
}

function addComment($id, $typeId, $sourceId, $userId, $commentText, $commentDate, $db) {
	$commentText = mysql_real_escape_string(strip_tags(substr($commentText, 0, 140)));

	// Check if comment from twitter exists
	$sql = "SELECT COMMENT_ID FROM COMMENT WHERE USER_ID = '$userId' AND COMMENT_TEXT = '$commentText'";
	$result = mysql_query($sql, $db);

	if (mysql_num_rows($result) > 0) {
		return null;
	}

	$sql = "INSERT IGNORE INTO COMMENT (OBJECT_ID, OBJECT_TYPE_ID, COMMENT_SOURCE_ID, USER_ID, COMMENT_DATE_TIME, COMMENT_TEXT) VALUES ($id, $typeId, $sourceId, $userId, '$commentDate', '$commentText')";
	mysql_query($sql, $db);
	
	return mysql_insert_id();
}

function removeComment ($commentId, $db) {
	$sql = "DELETE FROM COMMENT WHERE COMMENT_ID = '$commentId'";
	mysql_query($sql, $db);
}

// Input: Arrange type, order, number of users, offset, DB handle
// Return: Users data
function getUsers ($pagesize, $offset, $db) {
	$sql = "SELECT SQL_CALC_FOUND_ROWS USER_ID, USER_NAME, DISPLAY_NAME, USER_STATUS_ID, USER_PRIVILEGE_ID, EMAIL_ADDRESS FROM USER ORDER BY USER_ID LIMIT $pagesize OFFSET $offset";
	$results = mysql_query($sql, $db);
	
	return $results;
}

// Input: Username, DB handle
// Return: User ID or null, error message
function usernameToId ($username, $db) {

	$sql = "SELECT USER_ID FROM USER WHERE USER_NAME='$username'";
	$results = mysql_query($sql, $db);

	if (empty($results)) {
		return array(null, mysql_error());
	}

	if (mysql_num_rows > 1) {
		return array(null, "Found multiple users with username $username");
	}

	$row = mysql_fetch_array($results);

	return array($row["USER_ID"], null);
}

// Input: Blog URI, DB handle
// Return: Blog ID or null
function blogUriToId ($bloguri, $db) {

	$sql = "SELECT BLOG_ID FROM BLOG WHERE BLOG_URI='$bloguri'";
	$results = mysql_query($sql, $db);

	if (!$results || mysql_num_rows($results) == 0) {
		return null;
	}

	$row = mysql_fetch_array($results);

	return $row["BLOG_ID"];
}

// Input: Blog syndication URI, DB handle
// Return: Blog ID or null
function blogSyndicationUriToId ($blogsyndicationuri, $db) {

	$sql = "SELECT BLOG_ID FROM BLOG WHERE BLOG_SYNDICATION_URI='$blogsyndicationuri'";
	$results = mysql_query($sql, $db);

	if (!$results || mysql_num_rows($results) == 0) {
		return null;
	}

	$row = mysql_fetch_array($results);

	return $row["BLOG_ID"];
}

// Input: Post ID, DB handle
// Return: Blog ID or null
function postIdToBlogId ($postId, $db) {
	$sql = "SELECT BLOG_ID FROM BLOG_POST WHERE BLOG_POST_ID='$postId'";
	$results = mysql_query($sql, $db);
	
	if (!$results || mysql_num_rows($results) == 0) {
		return NULL;
	}

	$row = mysql_fetch_array($results);

	return $row["BLOG_ID"];
}

// Input: Post ID, DB Handle
// Return: Language Name or default (english)
function postIdToLanguageName ($postId, $db) {
	// Language is optional.
	$sql = "SELECT LANGUAGE_ENGLISH_NAME FROM LANGUAGE AS L INNER JOIN BLOG_POST as P ON L.LANGUAGE_ID = P.LANGUAGE_ID WHERE BLOG_POST_ID = '$postId'";
	$result = mysql_query($sql, $db);
	$row = mysql_fetch_array($result);
	
	if (!empty($row)) {
		$postLanguage = $row["LANGUAGE_ENGLISH_NAME"];
	}
	else {
		$postLanguage = "English";
	}
	
	return $postLanguage;
}

// Input: URI
// Output: URI +/- a / on the end, in an array of various possibilities
function alternateUris($uri) {
	$uris = array($uri);

	if (substr($uri, strlen($uri) - 1) === "/") {
		// Try it without a trailing slash
		array_push($uris, substr($uri, 0, strlen($uri) - 1));
	} else {
		// Try it with a trailing slash
		array_push($uris, $uri . "/");
	}

	return $uris;
}

// Input: DB handle
// Output: array of arrays; sub-arrays contain ["uri"] and ["id"]
function getSparseBlogs($db, $limit=1000) {
	// status 0 => active
	$sql = "SELECT BLOG_SYNDICATION_URI, BLOG_ID FROM BLOG WHERE BLOG_STATUS_ID = '0' ORDER BY CRAWLED_DATE_TIME LIMIT $limit";
	$blogs = array();
	$results = mysql_query($sql, $db);
	while ($row = mysql_fetch_array($results)) {
		$blog["id"] = $row["BLOG_ID"];
		$blog["uri"] = $row["BLOG_SYNDICATION_URI"];
		array_push($blogs, $blog);
	}
	return $blogs;
}

// Input: DB handle
// Output: array of hashes of blogs (id, name, uri, description, syndication uri)
function getBlogList ($blogIds, $arrange, $order, $pagesize, $offset, $db) {
	global $hashData;
	$column = $hashData["$arrange"];
	$direction = $hashData["$order"];
	
	$sql = "SELECT BLOG_ID, BLOG_NAME, BLOG_STATUS_ID, BLOG_URI, BLOG_DESCRIPTION, BLOG_SYNDICATION_URI, ADDED_DATE_TIME, CRAWLED_DATE_TIME FROM BLOG ";
	if ($blogIds != NULL) {
		$firstBlogId = array_shift($blogIds);
		$sql .= "WHERE (BLOG_ID = $firstBlogId) ";
		foreach ($blogIds as $blogId) {
			$sql .= "OR (BLOG_ID = $blogId) ";
		}
	}
	
	$sql .= "ORDER BY $column $direction LIMIT $pagesize OFFSET $offset";
	
	$blogs = array();
	$results = mysql_query($sql, $db);
	while ($row = mysql_fetch_array($results)) {
		$blog["id"] = $row["BLOG_ID"];
		$blog["name"] = $row["BLOG_NAME"];
		$blog["description"] = $row["BLOG_DESCRIPTION"];
		$blog["uri"] = $row["BLOG_URI"];
		$blog["syndicationUri"] = $row["BLOG_SYNDICATION_URI"];
		$blog["status"] = $row["BLOG_STATUS_ID"];
		$blog["addedDate"] = $row["ADDED_DATE_TIME"];
		$blog["crawledDate"] = $row["CRAWLED_DATE_TIME"];
		array_push($blogs, $blog);
	}
	return $blogs;
}

// Input: DB handle
// Output: array of hashes of pending blogs (id, name, uri)
function getPendingBlogs($db) {
	// status 1 => pending
	$sql = "SELECT BLOG_NAME, BLOG_ID, BLOG_URI, BLOG_DESCRIPTION, BLOG_SYNDICATION_URI FROM BLOG WHERE BLOG_STATUS_ID=1";
	$blogs = array();
	$results = mysql_query($sql, $db);
	while ($row = mysql_fetch_array($results)) {
		$blog["id"] = $row["BLOG_ID"];
		$blog["name"] = $row["BLOG_NAME"];
		$blog["blogdescription"] = $row["BLOG_DESCRIPTION"];
		$blog["uri"] = $row["BLOG_URI"];
		$blog["syndicationuri"] = $row["BLOG_SYNDICATION_URI"];
		array_push($blogs, $blog);
	}
	return $blogs;
}

// Input: blogID, DB handle
// Action: mark blog as recently crawled
function markCrawled ($blogId, $db) {
	$sql = "UPDATE BLOG SET CRAWLED_DATE_TIME=NOW() WHERE BLOG_ID=$blogId";
	mysql_query($sql, $db);
}

// Input: blog ID, DB handle
// Return: array of email addresses of people associated with this blog
function getBlogContacts($blogId, $db) {
	$sql = "select u.EMAIL_ADDRESS, u.DISPLAY_NAME from BLOG_AUTHOR ba, USER u where ba.BLOG_ID=$blogId and u.USER_ID=ba.USER_ID";
	$results = mysql_query($sql, $db);
	
	return $results;
}

// Input: blogId, DB handle
// Return: status ID of blog
function getBlogStatusId ($blogId, $db) {
	$sql = "SELECT BLOG_STATUS_ID FROM BLOG WHERE BLOG_ID=$blogId";
	$results = mysql_query($sql, $db);

	if (!$results || mysql_num_rows($results) == 0) {
		return null;
	}

	$row = mysql_fetch_array($results);

	return $row["BLOG_STATUS_ID"];
}

// Input: blogId, DB handle
// Return: status ID of blog
function getPostStatusId ($postId, $db) {
	$sql = "SELECT BLOG_POST_STATUS_ID FROM BLOG_POST WHERE BLOG_POST_ID=$postId";
	$results = mysql_query($sql, $db);

	if (!$results || mysql_num_rows($results) == 0) {
		return null;
	}

	$row = mysql_fetch_array($results);

	return $row["BLOG_POST_STATUS_ID"];
}

// Input: DB handle
// Return: user status list
function getUserStatusList ($db) {
	$sql = "SELECT USER_STATUS_ID, USER_STATUS_DESCRIPTION FROM USER_STATUS ORDER BY USER_STATUS_DESCRIPTION";
	$results =	mysql_query($sql, $db);

	if (mysql_error() != null) {
		print "ERROR: " . mysql_error() . "<br />";
	}

	return $results;
}

// Input: DB handle
// Return: user privilege list
function getUserPrivilegeList ($db) {
	$sql = "SELECT USER_PRIVILEGE_ID, USER_PRIVILEGE_DESCRIPTION FROM USER_PRIVILEGE ORDER BY USER_PRIVILEGE_DESCRIPTION";
	$results =	mysql_query($sql, $db);

	if (mysql_error() != null) {
		print "ERROR: " . mysql_error() . "<br />";
	}

	return $results;
}

// Input: DB handle
// Return: blog status list
function getBlogStatusList ($db) {
	$sql = "SELECT BLOG_STATUS_ID, BLOG_STATUS_DESCRIPTION FROM BLOG_STATUS ORDER BY BLOG_STATUS_DESCRIPTION";
	$results =	mysql_query($sql, $db);

	if (mysql_error() != null) {
		print "ERROR: " . mysql_error() . "<br />";
	}

	return $results;
}

// Input: DB handle
// Return: blog post status list
function getBlogPostStatusList ($db) {
	$sql = "SELECT BLOG_POST_STATUS_ID, BLOG_POST_STATUS_DESCRIPTION FROM BLOG_POST_STATUS ORDER BY BLOG_POST_STATUS_DESCRIPTION";
	$results =	mysql_query($sql, $db);

	if (mysql_error() != null) {
		print "ERROR: " . mysql_error() . "<br />";
	}

	return $results;
}


// Input: topLevel (bool), DB handle
// Return: array of topics (topLevel only if topLevel == true)
function getTopicList ($topLevel, $db) {
	$sql = "SELECT TOPIC_ID, TOPIC_NAME, TOPIC_TOP_LEVEL_INDICATOR FROM TOPIC";
	if ($topLevel || $topLevel == 1) {
		$sql .= " WHERE TOPIC_TOP_LEVEL_INDICATOR = 1";
	}
	$sql .= " ORDER BY TOPIC_NAME";

	//	return mysql_query($sql, $db);
	$results =	mysql_query($sql, $db);

	if (mysql_error() != null) {
		print "ERROR: " . mysql_error() . "<br />";
	}

	return $results;
}

// Input: user ID, DB handle
// Output: ids of blogs owned by this user
function getBlogIdsByUserId ($userId, $db) {

	$sql = "select ba.BLOG_ID, user.DISPLAY_NAME from USER user, BLOG_AUTHOR ba, BLOG pa where user.USER_ID=$userId and ba.USER_ID=user.USER_ID and (pa.BLOG_STATUS_ID=0 or pa.BLOG_STATUS_ID=3) and pa.BLOG_ID=ba.BLOG_ID";
	$results = mysql_query($sql, $db);
	$blogIds = array();
	if ($results != null) {
		while ($row = mysql_fetch_array($results)) {
			array_push($blogIds, $row["BLOG_ID"]);
		}
	}
	return $blogIds;
}

// Input: blog ID, DB handle
// Return: id of "Unknown" author associated with this blog, else null
function getUnknownAuthorId ($blogId, $db) {
	$sql = "select BLOG_AUTHOR_ID from BLOG_AUTHOR WHERE BLOG_ID=$blogId and BLOG_AUTHOR_ACCOUNT_NAME='Unknown'";
	$results = mysql_query($sql, $db);
	if ($results != null) {
		while ($row = mysql_fetch_array($results)) {
			return $row["BLOG_AUTHOR_ID"];
		}
	}
	return null;
}

// Input: blog ID, check feed for authors or not, DB handle
// Action: extract list of authors from DB; also, crawl blog URI for more authors to offer
// Return: map of author ID -> author name of authors associated with this blog
function getAuthorList ($blogId, $checkFeed = FALSE, $db) {
		
	if ($blogId == null) {
		print "<span class=\"ss-error\">Please specify blog ID (getAuthorList)</span>\n";
		return;
	}
	
	if ($checkFeed == TRUE) {
		// List all author names/ids from DB
		$sql = "SELECT BLOG_AUTHOR_ID, BLOG_AUTHOR_ACCOUNT_NAME, USER_ID FROM BLOG_AUTHOR WHERE BLOG_ID=$blogId";
		$authorList = mysql_query($sql, $db);
		
		// Get blog feed.
		$sql = "SELECT BLOG_SYNDICATION_URI FROM BLOG WHERE BLOG_ID=$blogId";
		$results =	mysql_query($sql, $db);
		if ($results == null || mysql_num_rows($results) == 0) {
			// TODO error message to log
			// this should not have been empty
			return NULL;
		}
		
		$row = mysql_fetch_array($results);
		$uri = $row["BLOG_SYNDICATION_URI"];
		
		$feed = getSimplePie($uri);
		foreach ($feed->get_items() as $item) {
			$author = $item->get_author();
			// Add all authors from the feed before generating the list.
			if (isset($author)) {
				$authorName = $author->get_name();
				addBlogAuthor($authorName, $blogId, $db);
			}
			unset($item);
		}
		unset ($feed);
	}

	// List all author names/ids from DB
	$sql = "SELECT BLOG_AUTHOR_ID, BLOG_AUTHOR_ACCOUNT_NAME, USER_ID FROM BLOG_AUTHOR WHERE BLOG_ID=$blogId";
	$authorList = mysql_query($sql, $db);

	return $authorList;
}

// Input: User status id, DB handle
// Return: user status name according to id
function userStatusIdToName ($userStatusId, $db) {
	
	$sql = "SELECT USER_STATUS_DESCRIPTION FROM USER_STATUS WHERE USER_STATUS_ID = '$userStatusId'";
	$results = mysql_query($sql, $db);

	if (!$results || mysql_num_rows($results) == 0) {
		return null;
	}

	$name = mysql_fetch_array($results);

	return $name["USER_STATUS_DESCRIPTION"];

}

// Input: User status id, DB handle
// Return: user privilege name according to id
function userPrivilegeIdToName ($userPrivilegeId, $db) {
	
	$sql = "SELECT USER_PRIVILEGE_DESCRIPTION FROM USER_PRIVILEGE WHERE USER_PRIVILEGE_ID = '$userPrivilegeId'";
	$results = mysql_query($sql, $db);

	if (!$results || mysql_num_rows($results) == 0) {
		return null;
	}

	$name = mysql_fetch_array($results);

	return $name["USER_PRIVILEGE_DESCRIPTION"];
}

// Input: Blog status id, DB handle
// Return: blog status name according to id
function blogStatusIdToName ($blogStatusId, $db) {
	
	$sql = "SELECT BLOG_STATUS_DESCRIPTION FROM BLOG_STATUS WHERE BLOG_STATUS_ID = '$blogStatusId'";
	$results = mysql_query($sql, $db);

	if (!$results || mysql_num_rows($results) == 0) {
		return null;
	}

	$name = mysql_fetch_array($results);

	return $name["BLOG_STATUS_DESCRIPTION"];

}

// Input: Blog status id, DB handle
// Return: blog status name according to id
function blogPostStatusIdToName ($blogPostStatusId, $db) {
	
	$sql = "SELECT BLOG_POST_STATUS_DESCRIPTION FROM BLOG_POST_STATUS WHERE BLOG_POST_STATUS_ID = '$blogPostStatusId'";
	$results = mysql_query($sql, $db);

	if (!$results || mysql_num_rows($results) == 0) {
		return null;
	}

	$name = mysql_fetch_array($results);

	return $name["BLOG_POST_STATUS_DESCRIPTION"];
}

// Input: array of topic names, DB handle
// Return: array of topic IDs (not necessarily in same order)
function topicNamesToIds ($topicNames, $db) {

	$firstTopic = array_shift ($topicNames);

	$sql = "SELECT TOPIC_ID FROM TOPIC WHERE TOPIC_NAME = '$firstTopic'";

	foreach ($topicNames as $topicName) {
		$sql .= " OR TOPIC_NAME = '";
		$sql .= mysql_real_escape_string($topicName) . "'";
	}

	$results =	mysql_query($sql, $db);

	if (mysql_error() != null) {
		print "ERROR: " . mysql_error() . "<br />";
	}

	// Convert to array
	$topicIds = array();
	while ($row = mysql_fetch_array($results)) {
		array_push ($topicIds, $row["TOPIC_ID"]);
	}

	return $topicIds;

}

// Input: Post author ID, DB Handle
// Output: User ID or NULL
function getAuthorUserId($postAuthorId, $db) {
	$sql = "SELECT USER_ID FROM BLOG_AUTHOR WHERE BLOG_AUTHOR_ID='$postAuthorId'";
	$result =	mysql_query($sql, $db);
	
	if ($result == NULL || mysql_num_rows($result) == 0) {
		return NULL;
	}
	
	$row = mysql_fetch_array($result);
	return $row["USER_ID"];
}

// Input: DB handle
// Return: array of blog IDs for all active blogs in the system
function getBlogIds ($db) {
	$sql = "SELECT BLOG_ID FROM BLOG WHERE BLOG_STATUS_ID=0";
	$results = mysql_query($sql, $db);

	if (mysql_error() != null) {
		print "ERROR: " . mysql_error() . "<br />";
	}

	// Convert to array
	$blogIds = array();
	while ($row = mysql_fetch_array($results)) {
		array_push ($blogIds, $row["BLOG_ID"]);
	}

	return array_unique ($blogIds);
}

// Input: Citation Text, DB handle
// Output: Citation ID
function citationTextToCitationId ($citation, $db) {
	$sql = "SELECT CITATION_ID FROM CITATION WHERE CITATION_TEXT = '$citation'";
	$result = mysql_query($sql, $db);

	$row = mysql_fetch_array($result);
	$citationId = $row["CITATION_ID"];

	return $citationId;
}

// Input: Post ID, DB handle
// Output: Citation Data
function postIdToCitation ($postId, $db) {
	$sql = "SELECT c.* FROM CITATION c, POST_CITATION pc WHERE pc.BLOG_POST_ID = $postId AND c.CITATION_ID = pc.CITATION_ID";
	$results = mysql_query($sql, $db);
	
	$citations = array();
	while($row = mysql_fetch_array($results)) {
		$citation["id"] = $row["CITATION_ID"];
		$citation["text"] = $row["CITATION_TEXT"];
		$citation["articleId"] = $row["ARTICLE_ID"];
		array_push($citations, $citation);
	}

	return $citations;
}

// Input: Post ID, DB handle
// Output: Citation Data
function articleIdToArticleIdentifier ($articleId, $db) {
	// TO DO: Modify query to take into account other article Ids with the same Identifier text, and then select other identifiers with that same article ID
	$sql = "SELECT ARTICLE_IDENTIFIER_TYPE, ARTICLE_IDENTIFIER_TEXT FROM ARTICLE_IDENTIFIER WHERE ARTICLE_ID = $articleId";
	$results = mysql_query($sql, $db);
	
	$articleIdentifiers = array();
	while($row = mysql_fetch_array($results)) {
		$articleIdentifier["idType"] = $row["ARTICLE_IDENTIFIER_TYPE"];
		$articleIdentifier["text"] = $row["ARTICLE_IDENTIFIER_TEXT"];
		array_push($articleIdentifiers, $articleIdentifier);
	}

	return $articleIdentifiers;
}

// Input: array of blog IDs, DB handle
// Output: mysql rows of blog data
function blogIdsToBlogData ($blogIds, $db) {
	
	$firstBlog = array_shift ($blogIds);

	$sql = "SELECT * FROM BLOG WHERE BLOG_ID = $firstBlog";

	foreach ($blogIds as $blogId) {
		$sql .= " OR BLOG_ID = $blogId";
	}
	$sql .= " ORDER BY BLOG_NAME";

	$results =	mysql_query($sql, $db);

	if (mysql_error() != null) {
		print "ERROR: " . mysql_error() . "<br />";
		return;
	}

	return $results;
}

// Input: array of blog IDs, arrangement keyword, direction keyword, limit of results, offset, DB handle
// Output: mysql rows of blog post data
function blogIdsToBlogPostData ($blogIds, $arrange, $order, $pagesize, $offset, $db) {
	global $hashData;
	$column = $hashData["$arrange"];
	$direction = $hashData["$order"];
	
	$firstBlog = array_shift ($blogIds);
	
	$sql = "SELECT BLOG_POST_ID, BLOG_ID, BLOG_POST_URI, BLOG_POST_SUMMARY, BLOG_POST_TITLE, BLOG_POST_HAS_CITATION FROM BLOG_POST WHERE BLOG_ID = $firstBlog AND BLOG_POST_STATUS_ID = 0";

	foreach ($blogIds as $blogId) {
		$sql .= " OR BLOG_ID = $blogId AND BLOG_POST_STATUS_ID = 0";
	}
	$sql .= " ORDER BY $column $direction LIMIT $pagesize OFFSET $offset";
	
	$results =	mysql_query($sql, $db);

	if (mysql_error() != null) {
		print "ERROR: " . mysql_error() . "<br />";
		return;
	}

	return $results;

}

// Input: string containing IETF BCP 47 code for a language or locale, DB handle
// Return: ID of indicated language in DB, or null
function languageToId($language, $db) {
	if (strlen($language) < 2) {
		return null;
	}

	// underscores to hyphens in case someone messed up
	$language = str_replace("_", "-", $language);

	// case insensitive match for the whole thing
	$sql = "SELECT LANGUAGE_ID, LANGUAGE_IETF_CODE FROM LANGUAGE WHERE LANGUAGE_IETF_CODE = '$language'";
	$results =	mysql_query($sql, $db);
	if (mysql_error()) {
		die ("languageToId: " . mysql_error());
	}

	if (mysql_num_rows($results) == 1) {
		$row = mysql_fetch_array($results);
		return $row["LANGUAGE_ID"];
	}

	if (mysql_num_rows($results) == 0) {
		$tokens = explode("-",$language);
		// we only have one token ("en")
		if (count ($tokens) == 1) {
			return null;
		}

		// we have multiple tokens ("en-gb")
		array_pop ($tokens);
		return languageToId(implode("-", $tokens), $db);
	}
}

// Input: string representing date
// Return: string representing date in SQL-amenable syntax (and GMT)
function dateStringToSql($datestr) {
	$timestamp = strtotime($datestr);
	$date = new DateTime(date("Y/m/d H.i.s", $timestamp));
	$date->setTimezone (new DateTimezone("GMT"));
	return $date->format('Y-m-d H:i:s');
}

// Input: SimplePie item (post) to add to db, DB handle
// Action: check to see if a post with a matching URI already exists in the DB.If so, return the ID of that post object. Else, add this new post object and return its ID
// Return: post object ID
function addSimplePieItem ($item, $language, $blogId, $db) {
	$itemURI = insanitize( $item->get_permalink() );
	$postTitle = mysql_real_escape_string($item->get_title());
	$postDate = $item->get_local_date();
	if (isset($postDate)) {
		$timestamp = dateStringToSql($postDate);
	} else {
		$timestamp = date("Y-m-d H:i:s");
	}
	
	$sql = "SELECT BLOG_POST_ID FROM BLOG_POST WHERE (BLOG_POST_TITLE = '$postTitle' AND BLOG_POST_DATE_TIME = '$timestamp') OR (BLOG_POST_URI = '$itemURI')";
	$result =	mysql_query($sql, $db);
	
	if (! $result || mysql_num_rows($result) != 0) {
		return NULL;
	}

	$blogAuthor = $item->get_author();
	$blogAuthorName = "Unknown";

	$authorList = getAuthorList ($blogId, FALSE, $db);

	if (mysql_num_rows($authorList) == 1) {
		// if exactly one author, set default author name to it
		// (instead of "Unknown")
		$row = mysql_fetch_array($authorList);
		$blogAuthorName = $row["BLOG_AUTHOR_ACCOUNT_NAME"];
	}

	if ($blogAuthor && strlen($blogAuthor->get_name()) > 0) {
		$blogAuthorName = $blogAuthor->get_name();
	}
	$blogAuthorId = addBlogAuthor($blogAuthorName, $blogId, $db);

	$languageId = languageToId($language, $db);
	if (empty($languageId)) {
		$languageId = "NULL";
	}

	$summary = smartyTruncate($item->get_description(), 500);
	if (strlen ($summary) < strlen ($item->get_description())) {
		$summary .= " […]";
	}

	$blogPostStatusId = 0; // active
	$sql = "INSERT INTO BLOG_POST (BLOG_ID, BLOG_AUTHOR_ID, LANGUAGE_ID, BLOG_POST_STATUS_ID, BLOG_POST_URI, BLOG_POST_DATE_TIME, BLOG_POST_INGEST_DATE_TIME, BLOG_POST_SUMMARY, BLOG_POST_TITLE) VALUES ($blogId, $blogAuthorId, $languageId, $blogPostStatusId, '". mysql_real_escape_string( htmlspecialchars($itemURI) ) . "' , '" . $timestamp . "', NOW(), '" . mysql_real_escape_string($summary) . "' ,'" . mysql_real_escape_string($item->get_title()) . "')";
	mysql_query($sql, $db);

	//print "SQL: $sql\n";

	if (mysql_error()) {
		die ("addSimplePieItem: " . mysql_error() . " ($sql)\n");
	}
	$dbId = mysql_insert_id();

	$categories = $item->get_categories();
	if (isset($categories)) {
		foreach ($categories as $category) {
			$tag = trim($category->get_label());
			if (strlen($tag) > 0) {
				$topicId = addTopic($tag, $db);
				linkTopicToPost($dbId, $topicId, 0, $db);
			}
		}
	}
	
	$item = NULL;
	$itemURI = NULL;
	$summary = NULL;

	return $dbId;
}

// Input: uri to check
// Return: true if uri can be fetched and parsed, false otherwise
function uriFetchable ($uri) {
	$ch = curl_init();		// initialize curl handle
	curl_setopt($ch, CURLOPT_URL,$uri); // set url to post to
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
	curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 8s
	$result = curl_exec($ch);
	$cerror = curl_error($ch);
	if (($cerror != null & strlen($cerror) > 0) || ! hasProtocol($uri)) {
		return false;
	}
	return true;
}

// Input: ID of post, ID of topic, Topic source, DB handle
// Action: link IDs of post and topic in DB
function linkTopicToPost($postId, $topicId, $topicSourceId, $db) {
	$sql = "INSERT IGNORE INTO TAG (TOPIC_ID, OBJECT_ID, OBJECT_TYPE_ID, TOPIC_SOURCE_ID, CREATION_DATE_TIME) VALUES ('$topicId', '$postId', '1', '$topicSourceId', NOW())";
	mysql_query($sql, $db);
	if (mysql_error()) {
		die ("linkTopicToPost: " . mysql_error() . "\n");
	}
}

// Input: Social Network ID, User ID, DB Handle
// Output: User social name and tokens
function getSocialNetworkUser ($socialNetworkId, $id, $column, $db) {
	if ($column == "userId")
		$column = "USER_ID";
	if ($column == "siteId")
		$column = "BLOG_ID";
	elseif ($column == "socialNetworkUserExtId")
		$column = "SOCIAL_NETWORK_USER_EXT_ID";
	elseif ($column == "socialNetworkUserId")
		$column = "SOCIAL_NETWORK_USER_ID";
	
	$sql = "SELECT * FROM SOCIAL_NETWORK_USER WHERE SOCIAL_NETWORK_ID = '$socialNetworkId' AND $column = '$id'";
	$result = mysql_query($sql, $db);
	
	if ($result == NULL || mysql_num_rows($result) == 0) {
		return NULL;
	}
	
	$row = mysql_fetch_array($result);
	$output["socialNetworkUserId"] = $row["SOCIAL_NETWORK_USER_ID"];
	$output["socialNetworkUserExtId"] = $row["SOCIAL_NETWORK_USER_EXT_ID"];
	$output["socialNetworkUserName"] = $row["SOCIAL_NETWORK_USER_NAME"];
	$output["socialNetworkUserAvatar"] = $row["SOCIAL_NETWORK_USER_AVATAR"];
	$output["userId"] = $row["USER_ID"];
	$output["oauthToken"] = $row["OAUTH_TOKEN"];
	$output["oauthSecretToken"] = $row["OAUTH_SECRET_TOKEN"];
	
	return $output;
}

// Input: Social Network ID, User Social Name, OAuth Token, OAuth Secret Token, User ID, DB handle
// Action: Add user social account
function addSocialNetworkUser($socialNetworkId, $socialNetworkUserExtId, $socialNetworkUserName, $socialNetworkUserAvatar, $userId, $siteId, $oauthToken, $oauthSecretToken, $oauthRefreshToken, $db) {
	
	$sql = "INSERT IGNORE INTO SOCIAL_NETWORK_USER (SOCIAL_NETWORK_ID, SOCIAL_NETWORK_USER_EXT_ID) VALUES ('$socialNetworkId', '$socialNetworkUserExtId')";
	mysql_query($sql, $db);
	
	updateSocialNetworkUser($socialNetworkId, $socialNetworkUserExtId, $socialNetworkUserName, $socialNetworkUserAvatar, $userId, $siteId, $oauthToken, $oauthSecretToken, $oauthRefreshToken, $db);
	
	$socialNetworkUser = getSocialNetworkUser ($socialNetworkId, $socialNetworkUserExtId, "socialNetworkUserExtId", $db);
	
	return $socialNetworkUser["socialNetworkUserId"];
}

function updateSocialNetworkUser($socialNetworkId, $socialNetworkUserExtId, $socialNetworkUserName, $socialNetworkUserAvatar, $userId, $siteId, $oauthToken, $oauthSecretToken, $oauthRefreshToken, $db) {
	$socialNetworkUserName = mysql_real_escape_string($socialNetworkUserName);
	$socialNetworkUserAvatar = mysql_real_escape_string($socialNetworkUserAvatar);
	
	$updateList = array();
	if ($socialNetworkUserName)
		$updateList[] = "SOCIAL_NETWORK_USER_NAME = '$socialNetworkUserName'";
	if ($socialNetworkUserAvatar)
		$updateList[] = "SOCIAL_NETWORK_USER_AVATAR = '$socialNetworkUserAvatar'";
	if ($userId)
		$updateList[] = "USER_ID = '$userId'";
	if ($siteId)
		$updateList[] = "BLOG_ID = '$siteId'";
	if ($oauthToken)
		$updateList[] = "OAUTH_TOKEN = '$oauthToken'";
	if ($oauthSecretToken)
		$updateList[] = "OAUTH_SECRET_TOKEN = '$oauthSecretToken'";
	if ($oauthRefreshToken)
		$updateList[] = "OAUTH_REFRESH_TOKEN = '$oauthRefreshToken'";
	
	$update = implode(", ", $updateList);
	
	$sql = "UPDATE SOCIAL_NETWORK_USER SET $update WHERE SOCIAL_NETWORK_ID = '$socialNetworkId' AND SOCIAL_NETWORK_USER_EXT_ID = '$socialNetworkUserExtId'";
	mysql_query($sql, $db);
}

// Input: Social Network ID, User ID, DB handle
// Action: Remove user's social account
function unlinkSocialNetworkUser($socialNetworkId, $userId, $db) {
	$sql = "UPDATE SOCIAL_NETWORK_USER SET OAUTH_TOKEN = '', OAUTH_SECRET_TOKEN = '', USER_ID = NULL WHERE SOCIAL_NETWORK_ID = '$socialNetworkId' AND USER_ID = '$userId'";
	mysql_query($sql, $db);
}

function unlinkSocialNetworkSite($socialNetworkId, $siteId, $db) {
	$sql = "UPDATE SOCIAL_NETWORK_USER SET BLOG_ID = NULL WHERE SOCIAL_NETWORK_ID = '$socialNetworkId' AND BLOG_ID = '$siteId'";
	mysql_query($sql, $db);
}

// Input: name of topic, DB handle
// Action: if this topic does not yet exist, insert it
// Return: ID of (new or previously existing) topic
function addTopic ($topic, $db) {
	$topic = strtolower($topic);
	$topicId = getTopic($topic, $db);
	if ($topicId) {
		return $topicId;
	}
	$topic = mysql_real_escape_string($topic);

	$sql = "INSERT INTO TOPIC (TOPIC_NAME, TOPIC_TOP_LEVEL_INDICATOR) VALUES ('$topic', 0)";
	mysql_query($sql, $db);
	if (mysql_error()) {
		die ("addTopic: " . mysql_error());
	}
	return mysql_insert_id();
}

function loadSettings($db) {
	$sql = "SELECT * FROM SETTING";
	$results = mysql_query($sql, $db);

	$settings = Array();
	while ($row = mysql_fetch_array($results)) {
		$settings[$row["SETTING_NAME"]] = $row["SETTING_VALUE"];
	}

	return $settings;
}

function editSetttings($settings, $db) {
	foreach ($settings as $name => $setting) {
		$setting = mysql_real_escape_string($setting);
		$sql = "UPDATE SETTING SET SETTING_VALUE='$setting'  WHERE SETTING_NAME = '$name'";
		mysql_query($sql, $db);
	}

	return $settings;
}

// Input: Post ID, DB Handle
// Return: Post Data
function getPost ($postId, $db) {
	$sql = "SELECT * from BLOG_POST where BLOG_POST_ID = '$postId'";
	$results =	mysql_query($sql, $db);
	if (! $results || mysql_num_rows($results) == 0) {
		return null;
	}
	$row = mysql_fetch_array($results);

	return $row;
}

// Input: name of topic, DB handle
// Return: ID of corresponding topic, or null
function getTopic ($topic, $db) {
	$topic = mysql_real_escape_string($topic);
	$sql = "SELECT TOPIC_ID FROM TOPIC WHERE TOPIC_NAME = '$topic'";
	$results = mysql_query($sql, $db);
	if (mysql_error()) {
		die ("getTopic: " . mysql_error() . "(SQL: $sql)");
	}
	$row = mysql_fetch_array($results);
	return ($row["TOPIC_ID"]);
}

// Input: name of blog author, DB handle
// Action: if this blog author does not yet exist in DB, add it.
// Return: ID of (new or previously existing) blog author
function addBlogAuthor($name, $blogId, $db) {
	$blogAuthorId = getBlogAuthorId($name, $blogId, $db);
	if ($blogAuthorId) {
		return $blogAuthorId;
	}

	$name = mysql_real_escape_string($name);
	$sql = "INSERT INTO BLOG_AUTHOR (BLOG_ID, BLOG_AUTHOR_ACCOUNT_NAME) VALUES ($blogId, '$name')";
	mysql_query($sql, $db);
	if (mysql_error()) {
		die("Error inserting blog author $name: " . mysql_error());
	}
	return mysql_insert_id();
}

// Input: name of blog author, ID of blog, DB handle
// Return: ID of blog author or null
function getBlogAuthorId($name, $blogId, $db) {
	$name = mysql_real_escape_string($name);
	$sql = "SELECT BLOG_AUTHOR_ID FROM BLOG_AUTHOR WHERE BLOG_ID=$blogId AND BLOG_AUTHOR_ACCOUNT_NAME='$name'";
	$results = mysql_query($sql, $db);

	if (!$results || mysql_num_rows($results) === 0) {
		return null;
	}

	$row = mysql_fetch_array($results);
	return $row["BLOG_AUTHOR_ID"];
}

// Input: ID of blog, DB handle
// Return: List of IDs of users who are authors on this blog and have edit permissions
function getBlogAuthorIds ($blogId, $db) {
	$sql = "select user.USER_ID from USER user, BLOG_AUTHOR ba where user.USER_ID = ba.USER_ID and ba.BLOG_ID=$blogId";

	$results = mysql_query($sql, $db);

	if (!$results || mysql_num_rows($results) === 0) {
		return null;
	}

	$authorIds = array();
	while ($row = mysql_fetch_array($results)) {
		array_push($authorIds, $row["USER_ID"]);
	}
	return $authorIds;
}

// Input: ID of blog author, ID of blog, DB handle
// Return: name of blog author or null
function getBlogAuthorName($authorId, $blogId, $db) {
	$sql = "SELECT BLOG_AUTHOR_ACCOUNT_NAME FROM BLOG_AUTHOR WHERE BLOG_ID=$blogId AND BLOG_AUTHOR_ID=$authorId";
	$results = mysql_query($sql, $db);

	if (!$results || mysql_num_rows($results) === 0) {
		return null;
	}

	$row = mysql_fetch_array($results);
	return $row["BLOG_AUTHOR_ACCOUNT_NAME"];
}

// Input: syndication URI for blog
// Action: search by the provided URI and alternate versions of it
// Return: ID of blog in system, or NULL if it is not yet in the system
function getBlogByAltSyndicationUri($blogsyndicationuri, $db) {
	$blogsyndicationuriFormats = alternateUris($blogsyndicationuri);

	// check for duplicates by URI (URIs should be unique to each blog)
	$blogId = null;
	foreach ($blogsyndicationuriFormats as $uri) {
		$blogId = blogSyndicationUriToId($uri, $db);
		if ($blogId != null) {
			return $blogId;
		}
	}
	return null;

}

// Input: URI for blog
// Action: search by the provided URI and alternate versions of it
// Return: ID of blog in system, or NULL if it is not yet in the system
function getBlogByAltUri($bloguri, $db) {
	$bloguriFormats = alternateUris($bloguri);

	// check for duplicates by URI (URIs should be unique to each blog)
	$blogId = null;
	foreach ($bloguriFormats as $uri) {
		$blogId = blogUriToId($uri, $db);
		if ($blogId != null) {
			return $blogId;
		}
	}
	return null;

}

// Add a new Blog to the system.
// Input: Name of blog, URI of blog, syndication URI of blog, description of blog, primary topic #1, primary topic #2, DB handle
// Action: add blog to DB if it does not already exist
// Return: ID of (new or previously existing) blog
function addBlog($blogname, $bloguri, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $userId, $db) {

	global $siteApprovalEmail;
	global $sitename;
	global $approveUrl;

	$retval;

	$bloguriFormats = alternateUris($bloguri);
	$blogsyndicationuriFormats = alternateUris($blogsyndicationuri);

	// check for duplicates by URI (URIs should be unique to each blog)
 $blogId = getBlogByAltUri($bloguri, $db);
	if ($blogId != null) {
		$retval["errormsg"] = "This blog is already in the system.";
		$retval["id"] = $blogId;
		return $retval;
	}

	// check for duplicates by syndication URI (syndication URIs should be unique to each blog)
	$blogId = getBlogByAltSyndicationUri($blogsyndicationuri, $db);
	if ($blogId != null) {
		$retval["errormsg"] = "The feed for this blog is already in the system.";
		$retval["id"] = $blogId;
		return $retval;
	}
	
	$status = 1;
	if (!empty($userId)) {
		$userPriv = getUserPrivilegeStatus($userId, $db);
		if ($userPriv > 0) { // moderator or admin
			$status = 0; // active
		} else {
			# Send email to site admin with notification that a blog is waiting for approval
			$mailSent = mail ($siteApprovalEmail, "[$sitename admin] Pending blog submission", "Pending blog submission at $approveUrl");
			
			if (! $mailSent) {
				# TODO log this
			}
		}
	}

	$blogname = mysql_real_escape_string($blogname);
	$blogdescription = mysql_real_escape_string($blogdescription);
	$bloguri = mysql_real_escape_string($bloguri);
	
	$sql = "INSERT INTO BLOG (BLOG_NAME, BLOG_STATUS_ID, BLOG_URI, BLOG_SYNDICATION_URI, BLOG_DESCRIPTION, ADDED_DATE_TIME) VALUES ('$blogname', $status, '$bloguri', '$blogsyndicationuri', '$blogdescription', NOW())";

	mysql_query($sql, $db);
	$blogId = mysql_insert_id();

	// Add topics if specified
	if ($topic1 != "-1") {
		associateTopic($topic1, $blogId, $db);
	}

	if ($topic2 != "-1") {
		associateTopic($topic2, $blogId, $db);
	}

	$retval["id"] = $blogId;
	return $retval;
}

// Input: topic string, blog ID, DB handle
// Action: associate this topic with this blog ID
// TODO handle errors
function associateTopic($topicId, $blogId, $db) {
	$sql = "INSERT IGNORE INTO TAG (TOPIC_ID, OBJECT_ID, OBJECT_TYPE_ID, TOPIC_SOURCE_ID, CREATION_DATE_TIME) VALUES ('$topicId', '$blogId', '3', 1, NOW())";
	mysql_query($sql, $db);
}

// Input: blog ID, DB handle
// Output: list of topic IDs for this blog
function getBlogTopics($blogId, $db) {
	$sql = "SELECT bt.TOPIC_ID, t.TOPIC_NAME FROM TAG tag INNER JOIN TOPIC t ON tag.TOPIC_ID = t.TOPIC_ID WHERE tag.OBJECT_ID = '$blogId' AND OBJECT_TYPE_ID = '3' ORDER BY tag.TOPIC_ID DESC";
	$results = mysql_query($sql, $db);
	
	$topics = array();
	while ($row = mysql_fetch_array($results)) {
		$topic["topicId"] = $row["TOPIC_ID"];
		$topic["topicName"] = $row["TOPIC_NAME"];
		array_push($topics, $topic);
	}

	return $topics;
}

// Input: post ID, DB handle
// Output: list of topic IDs for this post
/*function getUserTags($postId, $db) {
	$sql = "SELECT tag.*, t.TOPIC_NAME FROM TAG tag INNER JOIN TOPIC t ON tag.TOPIC_ID = t.TOPIC_ID WHERE tag.OBJECT_ID='$postId' AND tag.OBJECT_TYPE_ID='1' AND tag.TOPIC_SOURCE_ID = '3' ORDER BY tag.TOPIC_ID DESC";
	$results = mysql_query($sql, $db);
	
	$topics = array();
	while ($row = mysql_fetch_array($results)) {
		$topic["tagId"] = $row["TAG_ID"];
		$topic["topicId"] = $row["TOPIC_ID"];
		$topic["topicName"] = $row["TOPIC_NAME"];
		$topic["objectId"] = $row["OBJECT_ID"];
		$topic["objectTypeId"] = $row["OBJECT_TYPE_ID"];
		$topic["userId"] = $row["USER_ID"];
		$topic["tagPrivacy"] = $row["TOPIC_NAME"];
		$topic["tagDate"] = $row["CREATION_DATE_TIME"];
		array_push($topics, $topic);
	}

	return $topics;
}*/

function getTags($objectId, $objectTypeId, $topicSourceId, $db) {
	$sql = "SELECT tag.*, t.TOPIC_NAME FROM TAG tag INNER JOIN TOPIC t ON tag.TOPIC_ID = t.TOPIC_ID WHERE tag.OBJECT_ID='$objectId' AND tag.OBJECT_TYPE_ID='$objectTypeId'";
	if ($topicSourceId !== NULL) {
		$sql .= " AND tag.TOPIC_SOURCE_ID = '$topicSourceId'";
	}
	$sql .= " ORDER BY TOPIC_SOURCE_ID, tag.PRIVATE_STATUS ASC";
	$results = mysql_query($sql, $db);
	
	$tags = array();
	while ($row = mysql_fetch_array($results)) {
		$tag["tagId"] = $row["TAG_ID"];
		$tag["topicId"] = $row["TOPIC_ID"];
		$tag["topicName"] = $row["TOPIC_NAME"];
		$tag["topicSourceId"] = $row["TOPIC_SOURCE_ID"];
		$tag["objectId"] = $row["OBJECT_ID"];
		$tag["objectTypeId"] = $row["OBJECT_TYPE_ID"];
		$tag["userId"] = $row["USER_ID"];
		$tag["tagPrivacy"] = $row["PRIVATE_STATUS"];
		$tag["tagDate"] = $row["CREATION_DATE_TIME"];
		array_push($tags, $tag);
	}

	return $tags;
}

function addTag($topicName, $objectId, $objectTypeId, $topicSourceId, $userId, $privateStatus, $db) {
	$topicId = addTopic ($topicName, $db);
	
	$sql = "REPLACE INTO TAG (TOPIC_ID, OBJECT_ID, OBJECT_TYPE_ID, TOPIC_SOURCE_ID, USER_ID, PRIVATE_STATUS, CREATION_DATE_TIME) VALUES ('$topicId', '$objectId', '$objectTypeId', '$topicSourceId', $userId, '$privateStatus', NOW())";
	mysql_query($sql, $db);
	
	return mysql_insert_id();
}

function getTag($tagId, $db) {
	$sql = "SELECT *, topic.TOPIC_NAME FROM TAG tag INNER JOIN TOPIC topic ON tag.TOPIC_ID = topic.TOPIC_ID WHERE TAG_ID = '$tagId'";
	$result = mysql_query($sql, $db);
	
	$row = mysql_fetch_array($result);
	$tag["tagId"] = $row["TAG_ID"];
	$tag["topicId"] = $row["TOPIC_ID"];
	$tag["topicName"] = $row["TOPIC_NAME"];
	$tag["topicSourceId"] = $row["TOPIC_SOURCE_ID"];
	$tag["objectId"] = $row["OBJECT_ID"];
	$tag["objectTypeId"] = $row["OBJECT_TYPE_ID"];
	$tag["userId"] = $row["USER_ID"];
	$tag["tagPrivacy"] = $row["PRIVATE_STATUS"];
	$tag["tagDate"] = $row["CREATION_DATE_TIME"];

	return $tag;
}

function getGroup($groupId, $db) {
	$sql = "SELECT * FROM `GROUP` WHERE GROUP_ID = '$groupId'";
	$result = mysql_query($sql, $db);
	
	$row = mysql_fetch_array($result);
	$group["groupId"] = $row["GROUP_ID"];
	$group["groupName"] = $row["GROUP_NAME"];
	$group["groupDescription"] = $row["GROUP_DESCRIPTION"];
	$group["groupMatchedPosts"] = $row["GROUP_MATCHING_POSTS"];
	$group["groupMatchedSitePosts"] = $row["GROUP_MATCHING_SITES"];
	$group["groupCreationDate"] = $row["CREATION_DATE_TIME"];
	
	return $group;
}

function getGroupManagers($groupId, $userId, $db) {
	$sql = "SELECT * FROM GROUP_MANAGER WHERE GROUP_ID = '$groupId'";
	if ($userId) {
		$sql .= " AND USER_ID = '$userId'";
	}
	$results = mysql_query($sql, $db);
	
	$managers = array();
	while ($row = mysql_fetch_array($results)) {
		$manager["userId"] = $row["USER_ID"];
		$manager["groupId"] = $row["GROUP_ID"];
		$manager["groupManagerPrivId"] = $row["MANAGER_PRIVILEGE_ID"];
		array_push($managers, $manager);
	}
	
	return $managers;
}

function isGroupManager($groupId, $userId, $managerPrivilegeId, $db) {
	$sql = "SELECT * FROM GROUP_MANAGER WHERE GROUP_ID = '$groupId' AND USER_ID = '$userId'";
	if ($managerPrivilegeId)
		$sql .= " AND MANAGER_PRIVILEGE_ID = '$managerPrivilegeId'";
	$result = mysql_query($sql, $db);
	
	if ($result == NULL || mysql_num_rows($result) == 0) {
		return FALSE;
	}
	
	return TRUE;
}

// Input: blog ID, DB handle
// TODO handle errors
function removeTopics($blogId, $db) {
	$sql = "DELETE FROM TAG WHERE OBJECT_ID = '$blogId' AND OBJECT_TYPE_ID = '3'";
	mysql_query($sql, $db);
}

/*
 * Common HTML code
 */
 
function recButton($objectId, $objectTypeId, $userId, $ajax, $db) {
	$recStatus = getRecommendations($objectId, $objectTypeId, $userId, NULL, NULL, NULL, $db);
	$recCount = count(getRecommendations($objectId, $objectTypeId, NULL, NULL, NULL, NULL, $db));
	
	if (!$ajax)
		print "<div class='rec-box' data-id='$objectId' data-type='$objectTypeId'>";
	
	if ($userId && $recStatus)
		print "<div class='recommended' title='Remove recommendation'></div>";
	else
		print "<div class='recommend' title='Recommend'></div>";
		
	if ($recCount != 0)
		print "<span class='rec-count'>$recCount</span>";
		
	if (!$ajax)
		print "</div>";
}

function trophyButton($postId, $ajax, $db) {
	$sql = "SELECT *, topic.TOPIC_NAME FROM TAG tag INNER JOIN TOPIC topic ON tag.TOPIC_ID = topic.TOPIC_ID WHERE OBJECT_ID = '$postId' AND OBJECT_TYPE_ID = '1' AND TOPIC_NAME = 'ssawards'";
	$result = mysql_query($sql, $db);
	$row = mysql_fetch_array($result);
	
	/*if (!$ajax)
		print "<div class='trophy-box' data-id='$postId'>";
	
	if ($row)
		print "<div class='nominated' title='Nominated'></div>";
	else
		print "<div class='nominate' title='Nominate'></div>";
		
	if (!$ajax)
		print "</div>";*/
}

function commentButton($objectId, $objectTypeId, $db) {
	$commentCount = count(getComments($objectId, $objectTypeId, NULL, NULL, NULL, NULL, $db));
	$objectTypeName = getObjectTypeName($objectTypeId);
	
	global $homeUrl;
	print "<div class=\"comment-box\" data-number=\"$commentCount\" data-id='$objectId' data-type='$objectTypeId'>
	<a class=\"comment-icon\" href=\"$homeUrl/$objectTypeName/$objectId\" title=\"Profile page\"></a>";
	if ($commentCount != 0) {
	 print "<span class=\"comment-count\">$commentCount</span>";
	}
	print "</div>";
}

function followButton($objectId, $objectTypeId, $userId, $db) {
	$followerStatus = FALSE;
	$followerCount = count(getFollowers($objectId, $objectTypeId, NULL, $db));
	if ($userId)
		$followerStatus = getFollowers($objectId, $objectTypeId, $userId, $db);
	
	print "<div class='follow-status' data-id='$objectId' data-type='$objectTypeId'>";
	if ($followerStatus)
		print "<div class='unfollow-button'>Unfollow</div>";
	else
		print "<div class='follow-button'>Follow</div>";
		
	if ($followerCount != 0) {
		//print "<span class='follow-count'>$followerCount</span>";
	}
		
	print "</div>";
}
 
// Input: URL to update without parameters, Next page button text, previous page button text.
// Output: HTML code for page buttons.
function pageButtons ($baseUrl, $pagesize, $total, $nextText = "»", $prevText = "«") {
	
	$offset = 0;
	if (empty($pagesize)) {
		global $numResults;
		$pagesize = $numResults;
	}
	if (isset($_REQUEST["offset"])) {
		$offset = $_REQUEST["offset"];
	}
	if (isset($_SERVER["QUERY_STRING"])) {
		$httpQuery = $_SERVER["QUERY_STRING"];
	}
	
	parse_str($httpQuery, $queryResults);
	$queryResults["n"] = $pagesize;
	$pages = ceil($total / $pagesize);
	$nextOffset = $offset + $pagesize;
	$prevOffset = $offset - $pagesize;
	
	print "<div class=\"page-buttons\">";
	if ($total != 0) {
		$queryResults["offset"] = $nextOffset;
		$nextPage = htmlspecialchars(http_build_query($queryResults));
		
		if ($prevOffset >= 0) {
			$queryResults["offset"] = $prevOffset;
			$prevPage = htmlspecialchars(http_build_query($queryResults));
			print "<a class=\"arrow-left\" title=\"Previous page\" href=\"$baseUrl/?$prevPage\"></a>";
		}
		
		$i = 1;
		$currentPage = floor(($offset / $pagesize) + 1);
		$p = ceil($currentPage - 4);
		$pageOffset = $offset - ($pagesize * 4);
		if ($p <= 0) {
			$p = 1;
			$pageOffset = 0;
		}
		do {
			if ($i != 1) {
				$pageOffset = $pageOffset + $pagesize;
			}
			$queryResults["offset"] = $pageOffset;
			$pageQuery = htmlspecialchars(http_build_query($queryResults));
			if ($currentPage == $p) {
				print "<span class=\"page-number-selected\">$p</span>";
			} else {
				print "<a class=\"page-number\" href=\"$baseUrl/?$pageQuery\">$p</a>";
			}
			$p++;
			$i++;
		}
		while ($p<=$pages && $i<=9);
		
		if ($currentPage < $pages && $offset <= $total) {
			print "<a class=\"arrow-right\" title=\"Next page\" href=\"$baseUrl/?$nextPage\"></a>";
		}
	}
	print "<div class=\"subtle-text\">".number_format($total, 0, ".", ",")." Result";
	if ($total != 1) {
		print "s";
	}
	print "</div>
	</div>";
}

function userActivity($id = NULL, $typeId = NULL, $userId = NULL, $actionType = NULL, $limit = 8, $db) {
	$actions = getUserActivity($id, $typeId, $userId, $actionType, $limit, $db);
	print "<div class='user-actions'>";
	foreach ($actions as $action) {
		$actionAvatar = $action["avatar"];
		$actionDate = $action["date"];
		$actionTitle = $action["title"];
		$actionText = $action["text"];
		$actionType = $action["type"];
		
		print "<div class='action' data-type='$actionType'>
		<div class='floater-wrapper'>
		<div class='action-avatar'>$actionAvatar</div>
		<div class='action-title'>$actionTitle</div>";
		if (!empty($actionText)) {
			print "<div class='action-text'>$actionText</div>";
		}
		print "</div>
		<div class='action-date'>$actionDate</div>
		</div>";
	}
	print "</div>";
}

// Input: Array of blog data, user privilege, Slider "open" or closed, DB handle
// Output: HTML Edit blog form.
function editBlogForm ($site, $userPriv, $open, $db) {
	$blogId = $site["siteId"];
	$blogName = $site["siteName"];
	$blogUri = $site["siteUrl"];
	$blogDescription = $site["siteSummary"];
	$blogSyndicationUri = $site["siteFeedUrl"];
	$blogAddedDate = $site["siteAddedDate"];
	$blogCrawledDate = $site["siteCrawledDate"];
	$blogStatusId = $site["siteStatus"];
	$twitterUser = getSocialNetworkUser(1, $blogId, "siteId", $db);
	$blogTopics = getTags($blogId, 3, 1, $db);
	$blogStatus = ucwords(blogStatusIdToName ($blogStatusId, $db));
	
	print "<div class=\"ss-entry-wrapper\">
	<div class=\"entry-indicator\">";
	if ($open == TRUE) {
		print "-";
	} else {
		print "+";
	}
	print "</div>
	<div class=\"post-header\">$blogId | <a class=\"red-title\" href=\"$blogUri\" target=\"_blank\">$blogName</a> | $blogStatus | $blogAddedDate</div>";
	if ($open == TRUE) {
		print "<div class=\"ss-slide-wrapper\" style=\"display: block;\">";
	}
	else {
		print "<div class=\"ss-slide-wrapper\">";
	}
	print "<br />
	<h3>General Information</h3>
	<form method=\"post\">
	<input type=\"hidden\" name=\"step\" value=\"edit\" />
	<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />
	<p>Added Date: $blogAddedDate</p>
	<p>Last Crawl Date: $blogCrawledDate</p>
	<p>Site Name<br />
	<input type=\"text\" name=\"blogName\" size=\"40\" value=\"".htmlspecialchars($blogName, ENT_QUOTES)."\"/></p>\n
	<p><a href=\"$blogUri\" target=\"_blank\">Homepage URL</a><br />
	<input type=\"text\" name=\"blogUri\" size=\"55\" value=\"".htmlspecialchars($blogUri, ENT_QUOTES)."\" /><br /><span class=\"subtle-text\">(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)</span></p>
	<p><a href=\"$blogSyndicationUri\" target=\"_blank\">Feed URL</a><br />
	<input type=\"text\" name=\"blogSyndicationUri\" size=\"55\" value=\"".htmlspecialchars($blogSyndicationUri, ENT_QUOTES)."\" /><br /><span class=\"subtle-text\">(RSS or Atom feed. Must start with \"http://\", e.g., <em>http://feeds.feedburner.com/blogname/</em>.)</span></p>
	<p>Description<br />
	<textarea name=\"blogDescription\" rows=\"5\" cols=\"55\">$blogDescription</textarea></p>\n
	<p>Categories: <select name='topic1'>\n
	<option value='-1'>None</option>\n";
	$topicList = getTopicList(true, $db);
	while ($row = mysql_fetch_array($topicList)) {
		$topicList2[] = $row;
		print "<option value='" . $row["TOPIC_ID"] . "'";
		if (isset($blogTopics[0]) && $row["TOPIC_ID"] == $blogTopics[0]["topicId"]) {
			print " selected";
		}
		print ">" . $row["TOPIC_NAME"] . "</option>\n";
	}
	print "</select>&nbsp;<select name='topic2'>\n
	<option value='-1'> None</option>\n";
	foreach ($topicList2 as $row) {
		print "<option value='" . $row["TOPIC_ID"] . "'";
		if (isset($blogTopics[1]) && $row["TOPIC_ID"] == $blogTopics[1]["topicId"]) {
			print " selected";
		}
		print ">" . $row["TOPIC_NAME"] . "</option>\n";
	}
	print "</select></p>\n
	<p>Status: <select name='blogStatus'>\n";
	$statusList = getBlogStatusList ($db);
	while ($row = mysql_fetch_array($statusList)) {
		if ($userPriv == 0) {
			if ($row["BLOG_STATUS_ID"] != 0 && $row["BLOG_STATUS_ID"] != 3) {
				continue;
			}
		}
		print "<option value='" . $row["BLOG_STATUS_ID"] . "'";
		if ($row["BLOG_STATUS_ID"] == $blogStatusId) {
			print " selected";
					}
		print ">" . ucwords($row["BLOG_STATUS_DESCRIPTION"]) . "</option>\n";
	}
	print "</select>";
	if ($userPriv > 0) {
		print " <input type=\"checkbox\" name=\"mailAuthor\" value=\"1\"";
		if ($open == TRUE) {
			print " checked=\"checked\"";
		}
		print " /> <span class=\"subtle-text\">Notify Author via E-mail</span>\n";
	}
	print "</p>\n";
	if ($userPriv > 0) {
		$authorList = getAuthorList($blogId, FALSE, $db);
		if (mysql_num_rows($authorList) != 0) {
			print "<div class=\"toggle-button\">Administer Authors</div>
			<div class=\"ss-slide-wrapper\">
			<br />";
			while ($row = mysql_fetch_array($authorList)) {
				print "<h4>Author</h4>
				<input type=\"hidden\" name=\"authorId[]\" value=\"".$row["BLOG_AUTHOR_ID"]."\" />
				<p>Author Name: ".$row["BLOG_AUTHOR_ACCOUNT_NAME"]."</p>
				<p>Author User ID: <input type=\"text\" name=\"authorUserId[]\" size=\"40\" value=\"".htmlspecialchars($row["USER_ID"], ENT_QUOTES)."\" /></p>";
			}
			print "</div>
			<br />";
		}
	}
	global $pages;
	$currentUrl = getURL();
	print "<h3>Social Networks</h3>
	<p>Twitter Handle <span class=\"subtle-text\">(Optional)</span><br />
	<input type=\"text\" name=\"twitterHandle\" size=\"40\" value=\"".$twitterUser["socialNetworkUserName"]."\"/></p>";
	global $debugSite;
	if ($debugSite == "true" && $userPriv > 0) {
		print "<p><input type=\"checkbox\" class=\"checkbox\" name=\"delete\" value=\"1\" /> Delete from database (debug only).</p>";
	}
	print "<p><input class=\"ss-button\" name=\"editBlog\" type=\"submit\" value=\"Save Changes\" /> <input class=\"ss-button\" name=\"crawl\" type=\"submit\" value=\"Scan for new posts\" /></p>
	</form>
	<hr />
	<h3>Profile Banner</h3>
	<form method=\"post\" action=\"".$pages["crop"]->getAddress()."/?url=$currentUrl&amp;type=site-banner&siteId=$blogId\" enctype=\"multipart/form-data\">
	<div class=\"margin-bottom\"><input type=\"file\" name=\"image\" /> <input class=\"ss-button\" type=\"submit\" value=\"Upload\" /></div>
	</form>
	</div>
	</div>";
}

// Input: Lists of posts from the DB, Minimalistic option, Slider option
// Action: Display a list of posts.
function displayPosts ($posts, $minimal = FALSE, $open = FALSE, $db) {
	global $homeUrl;
	global $pages;
	$authUser = new auth();
	$authUserId = $authUser->userId;
	
	$previousDay = NULL;
	foreach ($posts as $post) {
		$postId = $post["postId"];
		$blogId = $post["siteId"];
		$blogName = $post["siteName"];
		$blogUri = $post["siteUrl"];
		$postDate = strtotime($post["postDate"]);
		$formatHour = date("g:i A", $postDate);
		$postSummary = strip_tags($post["postSummary"]);
		$postTitle = $post["postTitle"];
		$postUri = $post["postUrl"];
		$postProfile = $homeUrl . "/post/" . $postId;
		$postHasCitation = $post["hasCitation"];
		$formatDay = date("F d, Y", $postDate);
		$blogTopics = getTags($blogId, 3, 1, $db);
		
		if (empty($postSummary)) {
			$postSummary = "No summary available for this post.";
		}
		
		// Check if this post should be grouped with other posts of the same day.
		if ($previousDay != $formatDay && $minimal != TRUE) {
			if (!empty($previousDay)) {
				print "</div>";
			}
			print "<div class=\"posts-day\">
			<h3>$formatDay</h3>";
		}
		
		// If post doesn't have a title, use the url instead.
		if (empty($postTitle))
			$postTitle = $postUri;
		
		// Get citations
		if ($postHasCitation)
			$postCitations = postIdToCitation($postId, $db);
		
		$editorsPicksStatus = getRecommendations($postId, 1, NULL, TRUE, NULL, NULL, $db);
		
		// Get number of comments for this post
		$commentCount = count(getComments($postId, 1, NULL, NULL, NULL, NULL, $db));
		
		print "<div class=\"ss-entry-wrapper\" data-id='$postId' data-type='post'>";
		if ($open == TRUE) {
			print "<div class=\"entry-indicator\">-</div>
			<div class=\"post-header\">";
			if ($minimal != TRUE) {
				print "$formatHour | ";
			}
			print "<a class=\"entry-title\" href=\"$postUri\" target=\"_blank\" rel=\"bookmark\" title=\"Permanent link to $postTitle\">$postTitle</a>
			</div>
			<div class=\"ss-slide-wrapper\" style=\"display: block;\">";
		} else {
			print "<div class=\"entry-indicator\">+</div>
			<div class=\"post-header\">";
			if ($minimal != TRUE) {
				print "$formatHour | ";
			}
			print "<a class=\"entry-title\" href=\"$postUri\" target=\"_blank\" rel=\"bookmark\" title=\"Permanent link to $postTitle\">$postTitle</a>
			</div>
			<div class=\"ss-slide-wrapper\">";
		}
		print "<div class=\"entry-description\" title=\"Summary\">$postSummary</div>";
		// Add citations to summary if available
		if ($postHasCitation == TRUE) {
			print "<div class=\"citation-wrapper\">";
			foreach ($postCitations as $citation) {
				print "<p>".$citation["text"]."</p>";
			}
			print '</div>';
		}
		print "</div>
		<div class=\"post-footer\">
		<a class=\"post-source\" href=\"$homeUrl/site/$blogId\" title=\"Permanent link to $blogName homepage\" rel=\"alternate\">$blogName</a>
		<div class=\"alignright\">
		<div class=\"post-categories\">";
		foreach ($blogTopics as $i => $blogTopic) {
			$topicName = $blogTopic["topicName"];
			
			if ($i != 0)
				print " | ";
			print "<a href=\"".$pages["posts"]->getAddress()."/?type=post&amp;filter0=blog&amp;modifier0=topic&amp;value0=".urlencode($topicName)."\" title=\"View all posts in $topicName\">$topicName</a>";
		}
		print "</div>
		<div class=\"recs\">";
		recButton($postId, 1, $authUserId, FALSE, $db);
		commentButton($postId, 1, $db);
		//trophyButton($postId, FALSE, $db);
		print "</div>
		</div>
		</div>";
		if ($postHasCitation == TRUE || $editorsPicksStatus == TRUE) {
			print "<div class=\"badges\">";
			if ($postHasCitation == TRUE) print "<span class=\"citation-mark\"></span>";
			if ($editorsPicksStatus == TRUE) print "<span class=\"editors-mark\"></span>";
			print "<div class=\"badges-wrapper\">";
				if ($postHasCitation == TRUE) {
					print "<div class=\"citation-mark-content\" title=\"Post citing a peer-reviewed source\">Citation</div>";
				}
				if ($editorsPicksStatus == TRUE) {
					print "<div class=\"editors-mark-content\" title=\"Recommended by our editors\">Editor's Pick</div>";
				}
			print "</div>
			</div>";
		}
		print "</div>";
		
		$previousDay = $formatDay;
	}
	
	if ($minimal != TRUE) {
		print "</div>";
	}
}

// Input: Post ID, DB Handle
// Action: Display notes for a post.
function displayComments ($objectId, $objectTypeId, $userId, $db) {	
	// Get comments list
	$commentList = getComments($objectId, $objectTypeId, NULL, NULL, NULL, NULL, $db);
	$commentCount = count($commentList);
	
	print "<div class=\"data-carrier\" data-id=\"$objectId\" data-type=\"$objectTypeId\" data-count=\"$commentCount\">
	<div class=\"comments\">
	<h3>Comments</h3>";
	if ($commentList) {
		// Display comments
		foreach ($commentList as $comment) {
			displayComment($comment, $userId, $db);
		}
	}
	print "</div>";
	
	global $pages;
	if ($userId) {
		$twitterUser = getSocialNetworkUser(1, $userId, "userId", $db);
		$userPriv = getUserPrivilegeStatus($userId, $db);
		
		print "<form method=\"post\" enctype=\"multipart/form-data\">
		<div class=\"char-count\" data-limit=\"140\">140</div>
		<textarea class=\"comment-area\" name=\"comment\"></textarea>
		<div class=\"margin-bottom\">
		<div class=\"tweet-preview-area margin-bottom\">
		<div class=\"subtle-text margin-bottom-small ss-bold\">Tweet Preview</div>
		<div class=\"tweet-preview\"><span class=\"tweet-message\"></span><span class=\"tweet-extras\"></span></div>
		</div>
		<input class=\"submit-comment ss-button\" type=\"button\" value=\"Submit\" />";
		if ($objectTypeId == 1) {
			if ($twitterUser) {
				print " <span class=\"subtle-text alignright\" title=\"The blog's twitter handle and post's url will be included in your tweet.\"><input class=\"tweet-note\" type=\"checkbox\" value=\"true\" /> Tweet this note.</span>";
			} else {
				$currentUrl = getURL();
				print " <a class=\"alignright subtle-text\" href=\"".$pages["sync"]->getAddress(TRUE)."/?url=$currentUrl\">Sync with Twitter</a>";
			}
		}
		print "</div>
		</form>";
	} else {
		print "<p><a href=\"".$pages["login"]->getAddress(TRUE)."\">Log in</a> to leave a comment</a></p>";
	}
	print "</div>";
}

function displayComment($comment, $userId, $db) {
	global $homeUrl;
	$commentId = $comment["commentId"];
	$commentUserId = $comment["userId"];
	$commentDate = $comment["commentDate"];
	$commentText = $comment["commentText"];
	$userName = getUserName($commentUserId, $db);
	$userAvatar = getUserAvatar($commentUserId, $db);
	
	print "<div class=\"comment\" data-comment-id=\"$commentId\" data-user-id=\"$commentUserId\">
	<div class=\"comment-header\"><a class=\"comment-username\" href=\"$homeUrl/user/$userName\">$userName</a><span class=\"comment-info\">";
	if ($commentUserId == $userId) {
		print "<span class='comment-delete'>Delete</span> | ";
	}
	print "$commentDate</span></div>
	<div class=\"comment-body\">
	<div class=\"comment-avatar\"><img src=\"".$userAvatar["small"]."\" alt=\"User Avatar\" /></div>
	<div class=\"comment-text\">$commentText</div>
	</div>
	</div>";
}

function displaySites($sites, $db) {
	print "<div class=\"entries\">";
	foreach ($sites as $site) {
		displaySite($site, $db);
	}
	print "</div>";
}

function displaySite($site, $db) {
	global $homeUrl, $pages;
	
	$blogId = $site["siteId"];
	$blogName = $site["siteName"];
	$blogUri = $site["siteUrl"];
	$blogSyndication = $site["siteFeedUrl"];
	$blogDescription = $site["siteSummary"];
	$blogTopics = getTags($blogId, 3, 1, $db);
	
	if (empty($blogDescription)) {
		$blogDescription = "No summary available for this site.";
	}
	
	print "<div class=\"ss-entry-wrapper\">
	<div class=\"entry-indicator\">+</div>
	<div class=\"post-header\">
	<a class=\"entry-title\" href=\"$homeUrl/site/$blogId\">".$blogName."</a>
	<div class=\"index-categories\">";
	foreach ($blogTopics as $i => $topic) {
		$topicName = $topic["topicName"];
		if ($i != 0)
			print " | ";
		print "<a href=\"".$pages["sources"]->getAddress()."/?type=blog&amp;filter0=topic&amp;value0=".urlencode($topicName)."\" title=\"View all posts in $topicName\">$topicName</a>";
	}
	print "</div>
	</div>
	<div class=\"ss-slide-wrapper\">
		<div class=\"entry-description\">$blogDescription</div>
		<div>
		<a class=\"ss-button\" href=\"$blogUri\">Home</a> <a class=\"ss-button\" href=\"".$pages["posts"]->getAddress()."/?type=posts&amp;filter0=blog&amp;modifier0=identifier&amp;value0=$blogId\">Posts</a> <a class=\"ss-button\" href=\"$homeUrl/claim/".$blogId."\">Claim this site</a>
		</div>
	</div>
	</div>";
}

function displayGroup($group, $db) {
	global $homeUrl;
	$authUser = new auth();
	$authUserId = $authUser->userId;
	
	$groupId = $group["groupId"];
	$groupName = $group["groupName"];
	$groupDescription = $group["groupDescription"];
	
	print "<div class=\"ss-entry-wrapper\">
	<div class=\"entry-indicator\">+</div>
	<div class=\"post-header\">
	<a class=\"entry-title\" href=\"$homeUrl/group/$groupId\">$groupName</a>
	</div>
	<div class=\"ss-slide-wrapper\">
	<div class=\"entry-description\">$groupDescription</div>
	<div class=\"ss-div\">";
	followButton($groupId, 4, $authUserId, $db);
	print "</div>
	</div>
	</div>";
}

function displayTags($objectId, $objectTypeId, $addTags, $db) {
	$tags = getTags($objectId, $objectTypeId, NULL, $db);
	print "<div class=\"tags\">";
	foreach ($tags as $tag) {
		displayTag($tag, $db);
	}
	if ($addTags) {
		print "<div class=\"add-tag\" data-id=\"$objectId\" data-type=\"$objectTypeId\">+ Add Tag</div>";
	}
	print "</div>";
}


function displayTag($tag, $db) {
	global $pages;
	$authUser = new auth();
	$authUserId = $authUser->userId;
	$authUserPriv = getUserPrivilegeStatus($authUserId, $db);
	
	$tagId = $tag["tagId"];
	$tagName = $tag["topicName"];
	$tagPrivacy = $tag["tagPrivacy"];
	$tagSourceId = $tag["topicSourceId"];
	
	if ($tagPrivacy)
		$className = "private-tag";
	else
		$className = "public-tag";
	
	print "<div class=\"tag\" data-id=\"$tagId\">
	<a class=\"$className\" href=\"".$pages["posts"]->getAddress()."/?type=post&amp;filter0=topic&amp;value0=".urlencode($tagName)."\" title=\"View all posts in $tagName\">$tagName</a>";
	if ($tagSourceId == 3 && ($tag["userId"] == $authUserId || $authUserPriv > 0)) {
		print "<span class=\"tag-remove\">X</span>";
	}
	print "</div>";
}

// Input: Step of the editing process, user ID, user Privilege, DB Handle
// Action: Check and edit submitted blog data.
function confirmEditBlog ($step, $db) {
	if (!empty($step)) {
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
		
		$blogName = NULL;
		$blogUri = NULL;
		$blogSyndicationUri = NULL;
		$blogDescription = NULL;
		$blogAddedDate = NULL;
		$blogCrawledDate = NULL;
		$topic1 = NULL;
		$topic2 = NULL;
		$blogStatusId = NULL;
		$twitterHandle = NULL;
		$mailAuthor = NULL;
		$crawl = NULL;
		$delete = NULL;
		
		if (isset($_REQUEST["blogId"])) {
			$blogId = $_REQUEST["blogId"];
		}
		if (isset($_REQUEST["blogName"])) {
			$blogName = $_REQUEST["blogName"];
		}
		if (isset($_REQUEST["blogUri"])) {
			$blogUri = htmlspecialchars($_REQUEST["blogUri"]);
		}
		if (isset($_REQUEST["blogSyndicationUri"])) {
			$blogSyndicationUri = htmlspecialchars($_REQUEST["blogSyndicationUri"]);
		}
		if (isset($_REQUEST["blogDescription"])) {
			$blogDescription = $_REQUEST["blogDescription"];
		}
		if (isset($_REQUEST["topic1"])) {
			$topic1 = $_REQUEST["topic1"];
		}
		if (isset($_REQUEST["topic2"])) {
			$topic2 = $_REQUEST["topic2"];
		}
		if (isset($_REQUEST["authorId"])) {
			$authorsId = $_REQUEST["authorId"];
		}
		if (isset($_REQUEST["authorUserId"])) {
			$authorsUserId = $_REQUEST["authorUserId"];
		}
		if (isset($_REQUEST["blogStatus"])) {
			$blogStatusId = $_REQUEST["blogStatus"];
		}
		if (isset($_REQUEST["twitterHandle"])) {
			$twitterHandle = str_replace("@","",$_REQUEST["twitterHandle"]);
		}
		if (isset($_REQUEST["mailAuthor"])) {
			$mailAuthor = $_REQUEST["mailAuthor"];
		}
		if (isset($_REQUEST["crawl"])) {
			$crawl = $_REQUEST["crawl"];
		}
		if (isset($_REQUEST["delete"])) {
			$delete = $_REQUEST["delete"];
		}
		
		$oldBlogName = getBlogName($blogId, $db);
		$errors = checkBlogData($blogId, $blogName, $blogUri, $blogSyndicationUri, $blogDescription, $blogStatusId, $topic1, $topic2, $twitterHandle, $authUserId, $db);
		
		// If user is requesting a blogUri or blogsyndicationuri change, ensure that they own the new url
		$origBlogSyndicationUri = getBlogSyndicationUri($blogId, $db);
		$origBlogUri = getBlogUri($blogId, $db);
	
		// If blog URL or syndication URL have changed, we need to re-verify the claim to the blog (the author's ability to write to it)
		if ($errors == NULL && $step == "edit" && $userPriv == 0 && ($origBlogSyndicationUri !== $blogSyndicationUri || $origBlogUri != $blogUri)) {
			$claimToken = retrieveVerifiedClaimToken ($blogId, $authUserId, $db);
			clearClaimToken($blogId, $authUserId, $claimToken, $db);
	
			$claimToken = retrievePendingClaimToken ($blogId, $authUserId, $db);
			if ($claimToken == null) {
				$claimToken = generateClaimToken();
				storeClaimToken($claimToken, $blogId, $authUserId, $db);
			}
			
			displayBlogClaimToken($claimToken, $blogId, $db);
			return;
		} elseif (!empty($crawl)) {
			// Find new posts
			$blog = array("siteFeedUrl"=>$blogSyndicationUri, "siteId"=>$blogId, "siteName"=>$blogName);
			$result = crawlBlogs($blog, $db);
			
			// Get posts and find citations
			$api = new API;
			$api->searchDb("type=post&filter0=blog&modifier0=identifier&value0=$blogId&n=10", FALSE, "post");
			foreach ($api->posts as $post) {
				$postId = $post["postId"];
				$postUri = $post["postUrl"];
				$postTitle = $post["postTitle"];
				$citations = checkCitations($postUri, $postId, $db);
				if (!empty($citations)) {
					$result .= "<p class=\"ss-successful\">We found the following citation(s) on $blogName: <a href=\"$postUri\">$postTitle</a></p>";
					foreach ($citations as $citation) {
						$articleData = parseCitation($citation);
						if (!empty($articleData)) {
							$generatedCitation = storeCitation ($articleData, $postId, $db);
							// Display citation
							$result .= "<p>$generatedCitation</p>";
						}
					}
				}
			}
			
			print $result;
		} elseif ($step == "confirmed" || ($errors == NULL && $step == "edit")) {
			if (isset($authorsId)) {
				foreach ($authorsId as $key => $authorId) {
					$authorUserId = $authorsUserId[$key];
					editAuthor ($authorId, $authorUserId, $db);
				}
			}
			editBlog ($blogId, $blogName, $blogUri, $blogSyndicationUri, $blogDescription, $topic1, $topic2, $db);
			editBlogStatus ($blogId, $blogStatusId, $mailAuthor, $db);
			if ($twitterHandle) {
				$twitterUser = getTwitterUserDetails(NULL, $twitterHandle);
				
				if (isset($twitterUser->id)) {
					unlinkSocialNetworkSite(1, $blogId, $db);
					addSocialNetworkUser(1, $twitterUser->id, $twitterUser->screen_name, $twitterUser->profile_image_url, NULL, $blogId, NULL, NULL, NULL, $db);
				}
			} else {
				unlinkSocialNetworkSite(1, $blogId, $db);
			}
			
			global $debugSite;
			if ($delete == TRUE && $debugSite == "true" && $userPriv > 0) {
				deleteBlog($blogId, $db);
			}
			print "<p class=\"ss-successful\">$blogName (ID $blogId) has been updated.</p>"; 
			
		} elseif ($errors != NULL && $step == "edit") {
			editBlogStatus ($blogId, $blogStatusId, $mailAuthor, $db);
			print "<p>$oldBlogName (ID $blogId):</p>$errors";
			if ($userPriv > 0) {
				print "<form class=\"margin-bottom\" method=\"post\">
				<input type=\"hidden\" name=\"step\" value=\"confirmed\" />
				<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />
				<input type=\"hidden\" name=\"blogName\" value=\"".htmlspecialchars($blogName, ENT_QUOTES)."\" />
				<input type=\"hidden\" name=\"blogUri\" value=\"".htmlspecialchars($blogUri, ENT_QUOTES)."\" />
				<input type=\"hidden\" name=\"blogSyndicationUri\" value=\"".htmlspecialchars($blogSyndicationUri, ENT_QUOTES)."\" />
				<input type=\"hidden\" name=\"blogDescription\" value=\"".htmlspecialchars($blogDescription, ENT_QUOTES)."\" />
				<input type=\"hidden\" name=\"twitterHandle\" value=\"".htmlspecialchars($twitterHandle, ENT_QUOTES)."\" />";
				if (isset($authorId)) {
					foreach ($authorId as $key => $authorId) {
						$authorUserId = $authorsUserId[$key];
						print "<input type=\"hidden\" name=\"authorId[]\" value=\"$authorId\" />
						<input type=\"hidden\" name=\"authorUserId[]\" value=\"$authorUserId\" />";
					}
				}
				print "<input type=\"hidden\" name=\"topic1\" value=\"$topic1\" />
				<input type=\"hidden\" name=\"topic2\" value=\"$topic2\" />
				<input type=\"hidden\" name=\"blogStatus\" value=\"$blogStatusId\" />
				<input type=\"hidden\" name=\"crawl\" value=\"$crawl\" />
				<p>There has been an error, are you sure you want to apply these changes?</p>
				<input class=\"ss-button\" name=\"confirm\" type=\"submit\" value=\"Confirm\" />
				</form>";
			}
		} 
		else if ($step == "verify") {
			$result = verifyClaim($blogId, $authUserId, $blogUri, $blogSyndicationUri, $db);
		
			if ($result === "no-claim") {
				print "<p class=\"ss-error\">There has been a problem retrieving your claim token.</p>";
				return;
			} 
			else if ($result == "verified") {
				$claimToken = getClaimToken($blogId, $authUserId, $db);
				$success = markClaimTokenVerified($blogId, $authUserId, $claimToken, $db);
				if (! $success) {
					print "<p class=\"ss-error\">Failed to update database with your claim token.</p>";
					return;
				}
				else {
					editBlog ($blogId, $blogName, $blogUri, $blogSyndicationUri, $blogDescription, $topic1, $topic2, $db);
					editBlogStatus ($blogId, $blogStatusId, $mailAuthor, $db);
					if (isset($twitterHandle)) {
						addBlogSocialAccount($twitterHandle, 1, $blogId, $db);
					} else {
						removeBlogSocialAccount(1, $blogId, $db);
					}
					
					if (isset($authorId)) {
						foreach ($authorId as $key => $authorId) {
							$authorUserId = $authorUserId[$key];
							editAuthor ($authorId, $authorUserId, $db);
						}
					}		
					
					print "<p class=\"ss-successful\">$blogName (ID $blogId) has been updated.</p>";
					return;
				}
			} 
			else {
				$claimToken = getClaimToken($blogId, $authUserId, $db);
				print "<p class=\"ss-error\">Your claim token ($claimToken) was not found on your blog and/or your syndication feed.</p>\n";
				displayBlogClaimToken($claimToken, $blogId, $db);
				return;
			}
		}
	}
}

// Input: Array of posts data, user privilege, Slider "open" or closed, DB handle
// Output: HTML edit post form.
function editPostForm ($posts, $userPriv, $open, $db) {
	print "<div class=\"entries\">";
	foreach ($posts as $post) {
		$postId = $post["postId"];
		$blogId = $post["siteId"];
		$postTitle = $post["postTitle"];
		$postSummary = $post["postSummary"];
		$postAuthorName = $post["postAuthorName"];
		$postDate = $post["postDate"];
		$postUrl = $post["postUrl"];
		$hasCitation = $post["hasCitation"];
		$postStatusId = getPostStatusId ($postId, $db);
		$postStatus = ucwords(blogPostStatusIdToName ($postStatusId, $db));
		$blogName = getBlogName($blogId, $db);
		$postLanguage = postIdToLanguageName ($postId, $db);
		$editorsPicksStatus = getRecommendations($postId, 1, NULL, TRUE, NULL, NULL, $db);
		
		print "<div class=\"ss-entry-wrapper\">";
		if ($open == TRUE) {
			print "<div class=\"entry-indicator\">-</div>";
		} else {
			print "<div class=\"entry-indicator\">+</div>";
		}
		print "<div class=\"post-header\">
		$postId | <a href=\"$postUrl\" target=\"_blank\">$postTitle</a> | $blogName | $postStatus
		</div>";
		if ($open == TRUE) {
			print "<div class=\"ss-slide-wrapper\" style=\"display: block;\">";
		}
		else {
			print "<div class=\"ss-slide-wrapper\">";
		}
		print "<br />
		<form method=\"post\" enctype=\"multipart/form-data\">
		<input type=\"hidden\" name=\"step\" value=\"edit\" />
		<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />
		<input type=\"hidden\" name=\"postId\" value=\"$postId\" />
		<p>Source: $blogName</p>";
		if (!empty($postAuthor)) {
			print "<p>Author Name: $postAuthorName</p>";
		}
		if (!empty($postLanguage)) {
			print "<p>Language: $postLanguage</p>";
		}
		if ($userPriv > 0) {
			print "<p>Post Date<br />
			<input type=\"text\" name=\"postDate\" value=\"$postDate\"/></p>";
		} else {
			print "<p>Post Date: $postDate</p>";
		}
		print "<p>Title<br />
		<input type=\"text\" name=\"title\" value=\"".htmlspecialchars($postTitle, ENT_QUOTES)."\"/></p>
		<p><a href=\"$postUrl\" target=\"_blank\">URL</a><br />
		<input type=\"text\" name=\"url\" value=\"".htmlspecialchars($postUrl, ENT_QUOTES)."\" /></p>
		<p>Summary<br />
		<textarea name=\"summary\">$postSummary</textarea></p>";
		print "<p>Status: <select name='status'>";
		$statusList = getBlogPostStatusList ($db);
		while ($row = mysql_fetch_array($statusList)) {
			if ($userPriv == 0) {
				if ($row["BLOG_POST_STATUS_ID"] != 0 && $row["BLOG_POST_STATUS_ID"] != 1) {
					continue;
				}
			}
			print "<option value='" . $row["BLOG_POST_STATUS_ID"] . "'";
			if ($row["BLOG_POST_STATUS_ID"] == $postStatusId) {
				print " selected";
			}
			print ">" . ucwords($row["BLOG_POST_STATUS_DESCRIPTION"]) . "</option>";
		}
		print "</select></p>
		<p><input type=\"checkbox\" class=\"checkbox\" name=\"checkCitations\" value=\"1\" /> Check for citations.</p>
		<input class=\"ss-button\" type=\"submit\" value=\"Save Changes\" />
		</form>
		</div>";
		if ($hasCitation == TRUE || $editorsPicksStatus == TRUE) {
			print "<div class=\"badges\">";
				if ($hasCitation == TRUE) print "<span class=\"citation-mark\"></span>";
				if ($editorsPicksStatus == TRUE) print "<span class=\"editors-mark\"></span>";
				print "<div class=\"ss-slide-wrapper\" style=\"width: 100%; float: right;\">";
					if ($hasCitation == TRUE) {
						print "<div class=\"citation-mark-content\" title=\"Post citing a peer-reviewed source\">Citation</div>";
					}
					if ($editorsPicksStatus == TRUE) {
						print "<div class=\"editors-mark-content\" title=\"Recommended by our editors\">Editor's Pick</div>";
					}
				print "</div>
				</div>";
		}
		print "</div>";
	}
	print "</div>";
}

// Input: Step of editing, DB handle
// Action: Check and edit post data
function confirmEditPost($step, $db) {
	global $pages;
	if ($step != NULL) {
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
		
		$postId = NULL;
		$postUrl = NULL;
		$postTitle = NULL;
		$postSummary = NULL;
		$postStatus = NULL;
		$postDate = NULL;
		$addedDate = NULL;
		$check = NULL;
		if (isset($_REQUEST["postId"])) {
			$postId = $_REQUEST["postId"];
		}
		if (isset($_REQUEST["blogId"])) {
			$blogId = $_REQUEST["blogId"];
		}
		if (isset($_REQUEST["url"])) {
			$postUrl = $_REQUEST["url"];
		}
		if (isset($_REQUEST["title"])) {
			$postTitle = $_REQUEST["title"];
		}
		if (isset($_REQUEST["summary"])) {
			$postSummary = $_REQUEST["summary"];
		}
		if (isset($_REQUEST["status"])) {
			$postStatusId = $_REQUEST["status"];
		}
		if (isset($_REQUEST["postDate"])) {
			$postDate = $_REQUEST["postDate"];
		}
		if (isset($_REQUEST["checkCitations"])) {
			$check = $_REQUEST["checkCitations"];
		}
		$blogName = getBlogName($blogId, $db);
		$result = checkPostData($postId, $postTitle, $postSummary, $postUrl, $postDate, $postStatusId, $authUserId, $db);
		if (empty($postTitle)) {
			$result .= "<p class=\"ss-error\">You must submit a title for this post.</p>";
		}
		if ($postStatusId === NULL) {
			$result .= "<p class=\"ss-error\">You must submit a status for this post.</p>";
		}
		
		if (($step == "confirmed" && $userPriv > 0) || ($result == NULL && $step == "edit")) {
			if ($check == 1) {
				removeCitations($postId, NULL, $db);
				$results = checkCitations ($postUrl, $postId, $db);
				if (!empty($results) && is_array($results)) {
					print "<p class=\"ss-successful\">We found the following citation(s) on $blogName: <a href=\"$postUrl\">$postTitle</a></p>";
					foreach ($results as $citation) {
						$articleData = parseCitation($citation);
						if (!empty($articleData)) {
							$generatedCitation = storeCitation ($articleData, $postId, $db);
							// Display citation
							print "<p>$generatedCitation</p>";
						}
					}
				}
				elseif (empty($results)) {
					print "<p class=\"ss-successful\">No citations found on $blogName: <a href=\"$postUrl\">$postTitle</a></p>";
				}
				else {
					print "$results";
				}
			}
			editPost ($postId, $postTitle, $postUrl, $postSummary, $postStatusId, $db);
			if (!empty($postDate) && $userPriv > 0) {
				editPostDate ($postId, $postDate, $db);
			}
			
			print "<p class=\"ss-successful\">$postTitle (ID $postId) was updated.</p>";
		}
		if ($result != NULL && $step == "edit") {
			print "$result";
			if ($userPriv > 0) {
				print "<form method=\"post\">
				<input type=\"hidden\" name=\"step\" value=\"confirmed\" />
				<input type=\"hidden\" name=\"postId\" value=\"$postId\" />
				<input type=\"hidden\" name=\"title\" value=\"".htmlspecialchars($postTitle, ENT_QUOTES)."\" />
				<input type=\"hidden\" name=\"url\" value=\"".htmlspecialchars($postUrl, ENT_QUOTES)."\" />
				<input type=\"hidden\" name=\"summary\" value=\"".htmlspecialchars($postSummary, ENT_QUOTES)."\" />
				<input type=\"hidden\" name=\"postDate\" value=\"".htmlspecialchars($postDate, ENT_QUOTES)."\" />
				<input type=\"hidden\" name=\"status\" value=\"$postStatus\" />
				<p>There has been an error, are you sure you want to apply these changes?</p>
				<p><input class=\"ss-button\" name=\"confirm\" type=\"submit\" value=\"Confirm\" /></p>
				</form>";
			}
		}
	}
}

/*
 * Edit stuff
 */

// Input: blog ID, blog name, blog URI, blog syndication URI, blog description, first main topic, other main topic, user ID, user display name, DB handle
// Action: check blog metadata
// Return: error message or null
function checkBlogData($blogId, $blogname, $blogurl, $blogsyndicationuri, $blogdescription, $blogStatusId, $topic1, $topic2, $twitterHandle, $userId, $db) {
	global $pages;
	$result = NULL;
	
	if (isset($blogId)) {
		// blog exists? need blog status!
		$blogStatus = getBlogStatusId($blogId, $db);
		if ($blogStatus == null) {
			return $result .= "<p class=\"ss-error\">No such blog $blogId.</p>";
		}
		
		$oldBlogName = getBlogName($blogId, $db);
	
		if (isset($userId)) {
			// if not logged in as an author or as admin, fail
			if (! canEdit($userId, $blogId, $db)) {
				$result .= "<p class=\"ss-error\">You don't have editing privileges for $oldBlogName.</p>";
			}
			
			$userPriv = getUserPrivilegeStatus($userId, $db);
			if ($userPriv == 0 && ($blogStatusId =! 0 && $blogStatusId =! 3)) {
				$result .= "<p class=\"ss-error\">You don't have editing privileges to set this status.</p>";
			}
		}
	}
	
	// Check that there is a name
	if ($blogname == null) {
		$result .= "<p class=\"ss-error\">Name field is required.</p>";
	}
	
	// Check that there is a feed
	if (empty($blogsyndicationuri)) {
		$result .= "<p class=\"ss-error\">Syndication URL field is required.</p>";
	}
	else {
		// Check that syndication feed is parseable
		$feed = getSimplePie($blogsyndicationuri);
		if ($feed->get_type() == 0) {
			$result .= "<p class=\"ss-error\">Unable to parse feed at $blogsyndicationuri. Are you sure it is Atom or RSS?</p>";
		}
	}
	
	// Check that there is a home page
	if (empty($blogurl)) {
		$result .= "<p class=\"ss-error\">URL field is required.</p>";
	}
	// check that blog URL is fetchable
	elseif (! uriFetchable($blogurl)) {
		$result .= "<p class=\"ss-error\">Unable to fetch the contents of your blog at $blogurl. Did you remember to put \"http://\" before the URL when you entered it? If you did, make sure your blog page is actually working, or <a href='".$pages["contact"]->getAddress()."'>contact us</a> to ask for help in resolving this problem.</p>";
	}
	
	// check that blog URL and blog syndication URL are not the same
	if ($blogurl == $blogsyndicationuri) {
		$result .= ("<p class=\"ss-error\">The homepage URL and syndication URL (RSS or Atom feed) must be different.</p>");
	}
	
	// Check that the user has selected at least one topic
	if ($topic1 == -1 && $topic2 == -1) {
		$result .= ("<p class=\"ss-error\">You need to choose at least one topic.</p>");
	}
	
	if ($twitterHandle && preg_match("/[^@\w\d]+/", $twitterHandle)) {
		$result .= "<p class=\"ss-error\">$twitterHandle is not a valid Twitter handle.</p>";
	}

	return $result;
}

// Input: blog ID, blog name, blog URI, blog syndication URI, blog description, first main topic, other main topic, DB handle
// Action: edit blog metadata
function editBlog ($blogId, $blogName, $blogUrl, $blogSyndicationUrl, $blogDescription, $topic1, $topic2, $db) {
	
	// escape stuff
	$blogName = mysql_real_escape_string($blogName);
	$blogDescription = mysql_real_escape_string($blogDescription);
	$blogUrl = mysql_real_escape_string($blogUrl);
	$blogSyndicationUrl = mysql_real_escape_string($blogSyndicationUrl);
	
	// update easy data
	$sql = "UPDATE BLOG SET BLOG_NAME='$blogName', BLOG_URI='$blogUrl', BLOG_SYNDICATION_URI='$blogSyndicationUrl', BLOG_DESCRIPTION='$blogDescription' WHERE BLOG_ID=$blogId";
	mysql_query($sql, $db);
	
	// remove all topics for this blog
	removeTopics($blogId, $db);

	// insert the new ones
	if ($topic1 != "-1") {
		associateTopic($topic1, $blogId, $db);
	}

	if ($topic2 != "-1") {
		associateTopic($topic2, $blogId, $db);
	}
}

// Input: Blog ID, DB Handle
// Action: Remove blog from database
function deleteBlog ($blogId, $db) {
	$sql = "DELETE FROM BLOG WHERE BLOG_ID = '$blogId'";
	mysql_query($sql, $db);
}

// Input: Blog ID, DB Handle
// Action: Remove blog from database
function deleteUser ($userId, $db) {
	$sql = "DELETE FROM USER WHERE USER_ID = '$userId'";
	mysql_query($sql, $db);
}

// Input: blog ID, blog status ID, DB handle
// Action: Edit blog status
// TO DO: Don't send more than one email to the same address
function editBlogStatus ($blogId, $blogStatusId, $mailAuthor, $db) {
	if ($mailAuthor == 1) {
		global $sitename;
		global $contactEmail;
		$authorsList = getBlogContacts($blogId, $db);
		while ($row = mysql_fetch_array($authorsList)) {
			$userEmail = $row["EMAIL_ADDRESS"];
			$userDisplayName = $row["DISPLAY_NAME"];
			$blogName = getBlogName($blogId, $db);
			if ($blogStatusId == 0) {
				global $userBlogs;
				$subject = "Site Submission Status: Approved";
				$message = "Hello, ".$userDisplayName."!
	
".$blogName." has been approved by our editors and is now active at ".$sitename."
	
To manage your sites, go to: ".$userBlogs."
	
If you have any questions, feel free to contact us at: ".$contactEmail."
						
The ".$sitename." Team.";
				sendMail($userEmail, $subject, $message);
			}
			if ($blogStatusId == 2) {
				global $rejectedSiteReasons;
				$subject = "Site Submission Status: Rejected";
				$message = "Hello, ".$userDisplayName.".
	
Unfortunately, ".$blogName." does not meet our requirements to be approved in ".$sitename.", the reasons for this might include:
	
$rejectedSiteReasons
	
If you have any questions, feel free to contact us at: ".$contactEmail."
						
The ".$sitename." Team.";

				sendMail($userEmail, $subject, $message);
			}
		}
	}
	
	
	// update easy data
	$sql = "UPDATE BLOG SET BLOG_STATUS_ID='$blogStatusId' WHERE BLOG_ID=$blogId";
	mysql_query($sql, $db);
}

// Input: post ID, post title, post summary, post URL, user ID, user display name, DB handle
// Action: check post metadata
// Return: error message or null
function checkPostData($postId, $postTitle, $postSummary, $postUrl, $postDate, $postStatus, $userId, $db) {
	global $pages;
	$result = NULL;
	
	if ($userId) {
		// user exists? active (0)?
		$userName = getUserName($userId, $db);
		$userStatus = getUserStatus($userId, $db);
		if ($userStatus == null) {
			$result .= "<p class=\"ss-error\">No such user $userName.</p>";
		}
		if ($userStatus != 0) {
			$result .= "<p class=\"ss-error\">User $userName is not active.</p>";
		}
		
		$userPriv = getUserPrivilegeStatus($userId, $db);
		if ($postStatus != 0 && $postStatus != 1 && $userPriv == 0) {
			$result .= "<p class=\"ss-error\">You can't set this post status.</p>";
		}
		
		$blogId = postIdToBlogId ($postId, $db);
		// if not logged in as an author or as admin, fail
		if (! canEdit($userId, $blogId, $db)) {
			$result .= "<p class=\"ss-error\">You don't have editing privileges for this post.</p>";
		}
	}
	
	if ($postId) {
		$postStatus = getPostStatusId($postId, $db);
		if ($postStatus == null) {
			$result .= "<p class=\"ss-error\">No such post $postId.</p>";
		}
	}
	
	if (!empty($postSummary) && mb_strlen($postSummary) > 8000) {
		$result .= "<p class=\"ss-error\">Summary is too long, a maximum of 500 characters is allowed.</p>";
	}

	// check that blog URL is fetchable
	if (!empty($postUrl) && !uriFetchable($postUrl)) {
		$result .= ("<p class=\"ss-error\">Unable to fetch the contents of this post at $postUrl. Did you remember to put \"http://\" before the URL when you entered it? If you did, make sure your blog page is actually working, or <a href='".$pages["contact"]->getAddress()."'>contact us</a> to ask for help in resolving this problem.</p>");
	}
	
	if (!empty($postDate) && !preg_match("/\d+-\d+-\d+ \d+:\d+:\d+/", $postDate)) {
		$result .= "<p class=\"ss-error\">Post publication date is not a valid timestamp.</p>";
	}
	
	return $result;
}

// Input: Post ID, Post Title, Post URL, Post Summmary, Post Status ID, DB Handle
// Action: edit post metadata
function editPost ($postId, $postTitle, $postUrl, $postSummary, $postStatusId, $db) {
	
	// escape stuff
	$postTitle = mysql_real_escape_string($postTitle);
	$postSummary = mysql_real_escape_string($postSummary);
	$postUrl = mysql_real_escape_string($postUrl);

	$sql = "UPDATE BLOG_POST SET BLOG_POST_TITLE='$postTitle', BLOG_POST_URI='$postUrl', BLOG_POST_SUMMARY='$postSummary', BLOG_POST_STATUS_ID='$postStatusId' WHERE BLOG_POST_ID='$postId'";
	mysql_query($sql, $db);

}

// Input: Post ID, Post Date, DB Handle
// Action: edit post date
function editPostDate ($postId, $postDate, $db) {
	
	// escape stuff
	$postDate = mysql_real_escape_string($postDate);

	$sql = "UPDATE BLOG_POST SET BLOG_POST_DATE_TIME='$postDate' WHERE BLOG_POST_ID='$postId'";
	mysql_query($sql, $db);
}

// Input: Post ID, Post Date, DB Handle
// Action: edit post date
function editPostStatus ($postId, $postStatus, $db) {

	$sql = "UPDATE BLOG_POST SET BLOG_POST_STATUS_ID='$postStatus' WHERE BLOG_POST_ID='$postId'";
	mysql_query($sql, $db);
}

// Input: user ID, user name, display name, user status, user privilege status, user email, administrator id, administrator privilege, administrator display name, WordPress DB handle, DB handle
// Action: check user metadata
// Return: error message or null
function checkUserData($loggedUserId, $userId, $userName, $userDisplayName, $userEmail, $userPass, $newUserPass1, $newUserPass2, $db) {
	
	$result = "";
	
	if (isset($userId)) {
		$userPriv = getUserPrivilegeStatus($loggedUserId, $db);
		
		// if not logged in as the user or admin, fail
		if ($loggedUserId != $userId && $userPriv < 2) {
			$result .= "<p class=\"ss-error\">You don't have privileges to edit this account.</p>";
		}
	
		// User exists? active (0)?
		$checkUserStatus = getUserStatus($userId, $db);
		if ($checkUserStatus == null) {
			$result .= "<p class=\"ss-error\">No such user (ID $userId).</p>";
		}
	}
	
	if ($userName && getUserId($userName, $db)) {
		$result .= "<p class=\"ss-error\">User name already exists in our system.</p>";
	}
	
	$emailId = emailToUserId($userEmail, $db);
	if ($userEmail && $emailId && $userId != $emailId) {
		$result .= "<p class=\"ss-error\">Email already exists in our system.</p>";
	}
	
	if ($userName && (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $userName) || strlen($userName) > 20)) {
		$result .= "<p class=\"ss-error\">User name must be alphanumeric, start with a letter and have a maximum of 20 characters.</p>";
	}
	
	if ($userDisplayName && strlen($userDisplayName) > 30) {
		$result .= "<p class=\"ss-error\">Display name has a maximum of 30 characters.</p>";
	}
	
	// Check if the email is valid
	if ($userEmail && !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
		$result .= "<p class=\"ss-error\">The submitted e-mail is not valid.</p>";
	}
	
	if (isset($userPass)) {
		$passStatus = checkUserPassword($userId, $userPass, $db);
		if ($passStatus == FALSE) {
			$result .= "<p class=\"ss-error\">The provided password doesn't match the user.</p>";
		}
	}
	
	if ($newUserPass1 && strlen($newUserPass1) < 8) {
		$result .= "<p class=\"ss-error\">Password must have at least 8 characters.</p>";
	}
	
	if ($newUserPass2 && $newUserPass1 != $newUserPass2) {
		$result .= "<p class=\"ss-error\">Password and confirmation password don't match.</p>";
	}
	
	return $result;
}

// Input: user ID, password, DB handle
// Action: check if password matches the current user password.
function checkUserPassword($userId, $userPass, $db) {
	include_once (dirname(__FILE__)."/../third-party/hasher/phpass.php");
	
	$sql = "SELECT PASSWORD FROM USER WHERE USER_ID = '$userId'";
	$result = mysql_query($sql, $db);
	$row = mysql_fetch_array($result);
	$hashedPass = $row['PASSWORD'];
	
	$hasher = new PasswordHash(8, TRUE);
	
	if ($hasher->CheckPassword($userPass, $hashedPass) || $hashedPass == md5($userPass)) {
		return TRUE;
	}
	return FALSE;
}

// Input: user ID, user name, display name, user status, user privilege status, user email, user password, DB handle
// Action: edit user metadata
function editUser ($userId, $userName, $userDisplayName, $userStatus, $userEmail, $userPrivilege, $userPass, $db) {
	
	// escape stuff
	$userName = mysql_real_escape_string($userName);
	$userEmail = mysql_real_escape_string($userEmail);
	$userDisplayName = mysql_real_escape_string($userDisplayName);
	
	// update easy data
	$sql = "UPDATE USER SET USER_NAME='$userName', DISPLAY_NAME='$userDisplayName', USER_PRIVILEGE_ID='$userPrivilege', USER_STATUS_ID='$userStatus', EMAIL_ADDRESS='$userEmail' WHERE USER_ID=$userId";
	mysql_query($sql, $db);
	
	if (isset($userPass)) {
		editUserPass($userId, $userPass, $db);
	}
}

// Input: user ID, user status ID, DB handle
// Action: edit user status
function editUserStatus ($userId, $userStatusId, $db) {
	$userStatusId = mysql_real_escape_string($userStatusId);
	
	$sql = "UPDATE USER SET USER_STATUS_ID='$userStatusId' WHERE USER_ID='$userId'";
	mysql_query($sql, $db);
}

// Input: user ID, user display name, DB handle
// Action: edit user display name
function editDisplayName ($userId, $userDisplayName, $db) {
	$userDisplayName = mysql_real_escape_string($userDisplayName);
	
	$sql = "UPDATE USER SET DISPLAY_NAME='$userDisplayName' WHERE USER_ID='$userId'";
	mysql_query($sql, $db);
}

// Input: user ID, user password, DB handle
// Action: Hash password and insert it
function editUserPass($userId, $userPass, $db) {
	$hashedPass = hashPassword($userPass);
	
	$sql = "UPDATE USER SET PASSWORD='$hashedPass' WHERE USER_ID='$userId'";
	mysql_query($sql, $db);
	
	return TRUE;
}

// Input: user ID, user status ID, DB handle
// Action: edit user avatar name
function editUserAvatar ($imageName, $userId, $db) {
	$sql = "UPDATE USER SET USER_AVATAR_LOCATOR = '$imageName' WHERE USER_ID = '$userId'";
	mysql_query($sql, $db);
}

// Input: Password
// Output: Hashed password.
function hashPassword($pass) {
	include_once (dirname(__FILE__)."/../third-party/hasher/phpass.php");
	
	$hasher = new PasswordHash(8, TRUE);
	$hashedPass = $hasher->HashPassword($pass);
	
	return $hashedPass;
}

// Input: User URL, User Personal Biography
// Action: check user preferences metadata
// Return: error message or null
function checkUserPreferences($userUrl, $userBio) {
	$result = "";
	
	if ($userUrl && !filter_var($userUrl, FILTER_VALIDATE_URL)) {
		$result .= "<p class=\"ss-error\">Invalid URL submitted ($userUrl), URL must start with \"http://\", e.g., <span class=\"italics\">http://blogname.blogspot.com/</span></p>";
	}
	
	if (mb_strlen($userBio) > 20000) {
		$result .= "<p class=\"ss-error\">Biography is too long; a maximum of 20000 characters is allowed.</p>";
	}
	
	return $result;
}

// Input: user ID, user URL, Biography, Email EP notifications, Email announcements, DB handle
// Action: edit user preferences
function editUserPreferences ($userId, $url, $bio, $location, $emailEdPicks, $emailAnnouncement, $emailFollows, $db) {
	
	// escape stuff
	$url = mysql_real_escape_string($url);
	$bio = mysql_real_escape_string($bio);
	$location = mysql_real_escape_string($location);
	
	$emailEP = "0";
	$emailA = "0";
	if ($emailEdPicks == TRUE) {
		$emailEP = "1";
	}
	if ($emailAnnouncement == TRUE) {
		$emailA = "1";
	}
	if ($emailFollows == TRUE) {
		$emailF = "1";
	}
	
	// update easy data
	$sql = "REPLACE INTO USER_PREFERENCE (USER_ID, USER_URL, USER_BIOGRAPHY, USER_LOCATION, EMAIL_EDITOR_PICK, EMAIL_ANNOUNCEMENTS, EMAIL_FOLLOWS) VALUES ('$userId', '$url', '$bio', '$location', '$emailEP', '$emailA', '$emailF')";
	mysql_query($sql, $db);
}

// Input: Author ID, User ID, DB Handle
// Action: Update author user ID.
// TODO: Improve this function when search API with user data is functional.
function editAuthor ($authorId, $authorUserId, $db) {
	if (is_numeric($authorUserId)) {
		$sql = "UPDATE BLOG_AUTHOR SET USER_ID = '$authorUserId' WHERE BLOG_AUTHOR_ID = '$authorId'";
		mysql_query($sql, $db);
	}
	elseif (! $authorUserId) {
		$sql = "UPDATE BLOG_AUTHOR SET USER_ID = NULL WHERE BLOG_AUTHOR_ID = '$authorId'";
		mysql_query($sql, $db);
	}
	else {
		print "<p class=\"ss-error\">Edit author error: user ID must be numeric.</p>";
	}
}

// Input: user ID, blog ID, DB handle
// Return: true if user ID is an author of blog ID or an admin, false otherwise
function canEdit($userId, $blogId, $db) {
	$userPriv = getUserPrivilegeStatus($userId, $db);
	if ($userPriv > 0) { // moderator or admin
		return true;
	}

	$authorIds = getBlogAuthorIds($blogId, $db);
	if (is_array($authorIds)) {
		return (in_array ($userId, $authorIds));
	}
	else {
		return FALSE;
	}
}

// Input: blog ID, DB handle
// Return: name of this blog, or null
function getBlogName($blogId, $db) {
	$sql = "SELECT BLOG_NAME FROM BLOG WHERE BLOG_ID='$blogId'";
	$results = mysql_query($sql, $db);
	if (mysql_num_rows($results) == 0) {
		return null;
	}
	$row = mysql_fetch_array($results);
	return $row["BLOG_NAME"];
}

// Input: user ID, DB handle
// Return: all user preferences
function getSite($siteId, $db) {
	$sql = "SELECT * FROM BLOG WHERE BLOG_ID = '$siteId'";
	$result = mysql_query($sql, $db);
	
	if ($result == NULL || mysql_num_rows($result) == 0) {
		return NULL;
	}
	
	$site = array();
	$row = mysql_fetch_array($result);
	$site["siteId"] = $row["BLOG_ID"];
	$site["siteSummary"] = $row["BLOG_DESCRIPTION"];
	$site["siteName"] = $row["BLOG_NAME"];
	$site["siteUrl"] = $row["BLOG_URI"];
	$site["siteFeedUrl"] = $row["BLOG_SYNDICATION_URI"];
	$site["siteAddedDate"] = $row["ADDED_DATE_TIME"];
	$site["siteCrawledDate"] = $row["CRAWLED_DATE_TIME"];
	$site["siteStatus"] = $row["BLOG_STATUS_ID"];
	
	return $site;
}

// Input: user ID, DB handle
// Return: all user preferences
function getUser($userId, $db) {
	$sql = "SELECT * FROM USER user LEFT JOIN USER_PREFERENCE pref ON user.USER_ID = pref.USER_ID WHERE user.USER_ID = '$userId'";
	$result = mysql_query($sql, $db);
	
	if ($result == NULL || mysql_num_rows($result) == 0) {
		return NULL;
	}
	
	$userData = array();
	$row = mysql_fetch_array($result);
	$userData["userId"] = $row["USER_ID"];
	$userData["userName"] = $row["USER_NAME"];
	$userData["userPrivilege"] = $row["USER_PRIVILEGE_ID"];
	$userData["userStatus"] = $row["USER_STATUS_ID"];
	$userData["userEmail"] = $row["EMAIL_ADDRESS"];
	$userData["userDisplayName"] = $row["DISPLAY_NAME"];
	$userData["userRegDateTime"] = $row["REGISTRATION_DATE_TIME"];
	$userData["userUrl"] = $row["USER_URL"];
	$userData["userBio"] = $row["USER_BIOGRAPHY"];
	$userData["userLocation"] = $row["USER_LOCATION"];
	$userData["emailEditorsPicks"] = $row["EMAIL_EDITOR_PICK"];
	$userData["emailAnnouncements"] = $row["EMAIL_ANNOUNCEMENTS"];
	$userData["emailFollows"] = $row["EMAIL_FOLLOWS"];
	
	return $userData;
}

// Input: user ID, DB handle
// Return: name of this user, or null
function getUserName($userId, $db) {
	$sql = "SELECT USER_NAME FROM USER WHERE USER_ID = '$userId'";
	$results = mysql_query($sql, $db);
	
	if ($results == NULL || mysql_num_rows($results) == 0) {
		return NULL;
	}
	$row = mysql_fetch_array($results);
	return $row["USER_NAME"];
}

// Input: user ID, DB handle
// Return: name of this user, or null
function getDisplayName($userId, $db) {
	$sql = "SELECT DISPLAY_NAME FROM USER WHERE USER_ID=$userId";
	$results = mysql_query($sql, $db);
	if ($results == NULL || mysql_num_rows($results) == 0) {
		return NULL;
	}
	$row = mysql_fetch_array($results);
	return $row["DISPLAY_NAME"];
}

// Input: user ID, DB handle
// Return: User e-mail or NULL
function getUserEmail($userId, $db) {
	$sql = "SELECT EMAIL_ADDRESS FROM USER WHERE USER_ID = '$userId'";
	$results = mysql_query($sql, $db);
	if ($results == null || mysql_num_rows($results) == 0) {
		return null;
	}
	$row = mysql_fetch_array($results);
	return $row["EMAIL_ADDRESS"];
}

// Input: user ID, DB handle
// Return: User avatar or NULL
function getUserAvatar($userId, $db) {
	$sql = "SELECT USER_AVATAR_LOCATOR FROM USER WHERE USER_ID = '$userId'";
	$result = mysql_query($sql, $db);
	if ($result == NULL || mysql_num_rows($result) == 0) {
		return NULL;
	}
	$row = mysql_fetch_array($result);
	$avatarName = $row["USER_AVATAR_LOCATOR"];
	
	global $homeUrl;
	if ($avatarName) {
		$avatar["big"] = "$homeUrl/images/users/$userId/avatars/$avatarName";
		$avatar["small"] = "$homeUrl/images/users/$userId/avatars/small-$avatarName";
	} else {
		$avatar["big"] = "$homeUrl/images/icons/default-avatar.jpg";
		$avatar["small"] = "$homeUrl/images/icons/small-default-avatar.jpg";
	}
	
	return $avatar;
}

// Input: Email, DB handle
// Return: User ID or NULL
function emailToUserId($userEmail, $db) {
	$userEmail = mysql_real_escape_string($userEmail);
	
	$sql = "SELECT USER_ID FROM USER WHERE EMAIL_ADDRESS='$userEmail'";
	$result = mysql_query($sql, $db);
	if ($result == null || mysql_num_rows($result) == 0) {
		return null;
	}
	$row = mysql_fetch_array($result);
	return $row["USER_ID"];
}

// Input: blog ID, DB handle
// Return: description of this blog, or null
function getBlogDescription($blogId, $db) {
	$sql = "SELECT BLOG_DESCRIPTION FROM BLOG WHERE BLOG_ID=$blogId";
	$results = mysql_query($sql, $db);
	if ($results == null || mysql_num_rows($results) == 0) {
		return null;
	}
	$row = mysql_fetch_array($results);
	return $row["BLOG_DESCRIPTION"];
}

// Input: blog ID, DB handle
// Return: syndication URI of this blog, or null
function getBlogSyndicationUri($blogId, $db) {
	$sql = "SELECT BLOG_SYNDICATION_URI FROM BLOG WHERE BLOG_ID=$blogId";
	$results = mysql_query($sql, $db);
	if ($results == null || mysql_num_rows($results) == 0) {
		return null;
	}
	$row = mysql_fetch_array($results);
	return $row["BLOG_SYNDICATION_URI"];
}

// Input: blog ID, DB handle
// Return: URI of this blog, or null
function getBlogUri($blogId, $db) {
	$sql = "SELECT BLOG_URI FROM BLOG WHERE BLOG_ID=$blogId";
	$results = mysql_query($sql, $db);
	if ($results == null || mysql_num_rows($results) == 0) {
		return null;
	}
	$row = mysql_fetch_array($results);
	return $row["BLOG_URI"];
}

// Input: UserId, DB handle
// Return: user privilege status or null
function getUserPrivilegeStatus($userId, $db) {
	$sql = "SELECT USER_PRIVILEGE_ID FROM USER WHERE USER_ID=$userId";
	$results = mysql_query($sql, $db);
	if ($results == null || mysql_num_rows($results) == 0) {
		return null;
	}
	$row = mysql_fetch_array($results);
	return $row["USER_PRIVILEGE_ID"];
}

// Input: UserId, DB handle
// Return: user status or null
function getUserStatus($userId, $db) {
	$sql = "SELECT USER_STATUS_ID FROM USER WHERE USER_ID=$userId";
	$results = mysql_query($sql, $db);
	if ($results == null || mysql_num_rows($results) == 0) {
		return null;
	}
	$row = mysql_fetch_array($results);
	return $row["USER_STATUS_ID"];
}

// Input: Username, DB handle
// Action: If no such username exists yet, add it
// Return: ID of (new or existing) user
function addUser($userName, $userEmail, $pass, $db) {
	$userId = getUserId($userName, $db);
	if ($userId != null) {
		return $userId;
	}
	
	$userName = mysql_real_escape_string($userName);

	$privilege = 0; // "user"
	$status = 0; // "active"
	$sql = "INSERT INTO USER (USER_NAME, DISPLAY_NAME, USER_PRIVILEGE_ID, USER_STATUS_ID, EMAIL_ADDRESS, PASSWORD, REGISTRATION_DATE_TIME) VALUES ('$userName', '$userName', $privilege, $status, '$userEmail', '$pass', NOW())";
	mysql_query($sql, $db);
	
	$userId = mysql_insert_id();
	
	editUserPreferences($userId, NULL, NULL, NULL, 1, 1, 1, $db);
	
	return $userId;
}

// Input: Username, DB handle
// Return: ID of corresponding user, or null
function getUserId($userName, $db) {
	$userName = mysql_real_escape_string($userName);
	
	$sql = "SELECT USER_ID FROM USER WHERE USER_NAME='$userName'";
	$result = mysql_query($sql, $db);
	if ($result == NULL || mysql_num_rows($result) == 0) {
		return NULL;
	}
	
	$row = mysql_fetch_array($result);
	return $row["USER_ID"];
}

function getUserPass($userId, $db) {
	$sql = "SELECT PASSWORD FROM USER WHERE USER_ID='$userId'";
	$results = mysql_query($sql, $db);
	if ($results == null || mysql_num_rows($results) == 0) {
		return null;
	}
	
	$row = mysql_fetch_array($results);
	return $row["PASSWORD"];
}

// Input: author ID, DB handle
// Return: boolean representing whether or not this author ID is "claimed" (attached to a user via a user)
function isAuthorClaimed($authorId, $db) {
	// TODO perhaps be smarter about this -- is the associated user active?
	$sql = "SELECT USER_ID FROM BLOG_AUTHOR WHERE BLOG_AUTHOR_ID=$authorId";
	$results = mysql_query($sql, $db);
	if ($results == null || mysql_num_rows($results) == 0) {
		return false;
	}
	$row = mysql_fetch_array($results);
	$userId = $row['USER_ID'];
	return ($userId != null);
}


// Input: blog ID, DB handle
// Return: boolean representing whether or not this blog can be "claimed"
function isBlogClaimable($blogId, $db) {
	$sql = "select BLOG_AUTHOR_ID from BLOG_AUTHOR where BLOG_ID=$blogId and USER_ID IS NULL";
	$results = mysql_query($sql, $db);
	if ($results == null || mysql_num_rows($results) == 0) {
		return false;
	}
	return true;
}

// Input: author ID, user ID, DB handle
// Action: link this Author and this user
function linkAuthorToUser($authorId, $userId, $db) {
	$sql = "UPDATE BLOG_AUTHOR ba, USER u SET ba.USER_ID=$userId WHERE ba.BLOG_AUTHOR_ID=$authorId AND u.USER_ID = $userId";
	mysql_query($sql, $db);
}

// Get SimplePie object, with appropriate cache location set
// SimplePie is for parsing syndication feeds
function getSimplePie($uri, $cacheTime = 0) {
	include_once(dirname(__FILE__)."/../third-party/simplepie/simplepie.php");
	global $cachedir;

	$feed = new SimplePie;
	$feed->set_feed_url($uri);
	$feed->set_cache_location($cachedir);
	$feed->set_cache_duration($cacheTime);
	$feed->set_output_encoding('UTF-8');
	$feed->init();
	
	return $feed;
}

/* Notifications */

function addNotification($objectId, $objectTypeId, $userId, $notificationTypeId, $db) {
	$sql = "INSERT IGNORE INTO NOTIFICATION (OBJECT_ID, OBJECT_TYPE_ID, USER_ID, NOTIFICATION_STATUS_ID, NOTIFICATION_TYPE_ID, NOTIFICATION_DATE_TIME) VALUES ('$objectId', '$objectTypeId', '$userId', 0, '$notificationTypeId', NOW())";
	mysql_query($sql, $db);
}

function removeNotification($objectId, $objectTypeId, $userId, $notificationTypeId, $db) {
	$sql = "DELETE FROM NOTIFICATION WHERE OBJECT_ID = '$objectId' AND OBJECT_TYPE_ID = '$objectTypeId' AND NOTIFICATION_TYPE_ID = '$notificationTypeId' AND NOTIFICATION_STATUS_ID = '0'";
	mysql_query($sql, $db);
}

/* CitationSeeker */

// Input: ID of post, db handle
// Return: true if post has BLOG_POST_HAS_CITATION=1, false otherwise
function citedPost ($postId, $db) {
	$sql = "SELECT * FROM BLOG_POST WHERE BLOG_POST_HAS_CITATION=1 AND BLOG_POST_ID=$postId";
	$results = mysql_query($sql, $db);
	return (mysql_num_rows($results) != 0);
}

# Input: string which contains all or part of article title (user-supplied)
# Output: list of strings, each containing COinS-formatted citation which might match the supplied string
function titleToCitations($title) {
	
	/* CrossRef */
	
	global $crossRefUrl;
	
	$uri = $crossRefUrl . urlencode($title);

	$ch = curl_init();		// initialize curl handle
	curl_setopt($ch, CURLOPT_URL,$uri); // set url to post to
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
	curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 8s

	# Send query to CrossRef Metadata Search
	$result = curl_exec($ch);
	$cerror = curl_error($ch);
	if (($cerror == null & strlen($cerror) == 0)) {
		$doc = new DOMDocument();
		@$doc->loadHTML($result);
		$xml = simplexml_import_dom($doc);
		
		// Search for Z3988 class and return all its contents
		$resultLinks = $xml->xpath("//a[text()='[xml]']/@href");
		
		$citations = array();
		foreach ($resultLinks as $uri) {
			$articleData = NULL;
			$ch = curl_init();		// initialize curl handle
			curl_setopt($ch, CURLOPT_URL,$uri); // set url to post to
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
			curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 8s
			$result = curl_exec($ch);
			
			$doc = new DOMDocument();
			@$doc->loadHTML($result);
			$xml = simplexml_import_dom($doc);
			
			// Build article data for the citation generator.
			foreach ($xml->xpath("//person_name") as $author) {
				$articleData["authors"][] = array("rft.aufirst"=>$author->given_name, "rft.aulast"=>$author->surname);
			}
			$articleData["rft.jtitle"] = (string)array_shift($xml->xpath("//journal_metadata/full_title"));
			$articleData["rft.atitle"] = (string)array_shift($xml->xpath("//journal_article/titles/title"));
			$articleData["rft.issn"] = (string)array_shift($xml->xpath("//journal_metadata/issn"));
			$articleData["rft.date"] = (string)array_shift($xml->xpath("//journal_issue/publication_date/year"));
			$articleData["rft.volume"] = (string)array_shift($xml->xpath("//journal_issue/journal_volume/volume"));
			$articleData["rft.issue"] = (string)array_shift($xml->xpath("//journal_issue/issue"));
			$articleData["rft.spage"] = (string)array_shift($xml->xpath("//pages/first_page"));
			$articleData["rft.epage"] = (string)array_shift($xml->xpath("//pages/last_page"));
			$articleData["rft.artnum"] = (string)array_shift($xml->xpath("//doi_data/resource[last()]"));
			$articleData["id"] = (string)array_shift($xml->xpath("(//doi_data/doi)[last()]"));
			$articleData["id_type"] = "doi";
			
			$citations[] = generateCitation ($articleData);
		}
	}
	
	/* Alternative non-experimental API
	
	$uri = "http://crossref.org/sigg/sigg/FindWorks?version=1&access=API_KEY&format=json&op=OR&expression=" . urlencode($title);

	$ch = curl_init();		// initialize curl handle
	curl_setopt($ch, CURLOPT_URL,$uri); // set url to post to
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
	curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 8s

	# Send query to CrossRef Metadata Search
	$result = curl_exec($ch);
	$cerror = curl_error($ch);
	if (($cerror != null && strlen($cerror) > 0)) {
		return "$cerror";
 	}
	
	foreach (json_decode($result) as $article) {
		$citationMeta = str_replace("rtf", "rft", $article->coins);
		$articleData = decodeCitationTitle ($citationMeta);
		$citations[] = generateCitation($articleData);
 }*/
 
	/* PubMed */
	
	global $pubMedUrl;
	global $pubMedIdUrl;
	$ch = curl_init();		// initialize curl handle
	curl_setopt($ch, CURLOPT_URL,$pubMedUrl . urlencode($title));
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
	curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 8s
	$result = curl_exec($ch);
	
	$cerror = curl_error($ch);
	if (($cerror == null & strlen($cerror) == 0)) {
		$doc = new DOMDocument();
		@$doc->loadHTML($result);
		$xml = simplexml_import_dom($doc);
		
		$pmids = $xml->xpath("//esearchresult/idlist/id");
		
		foreach ($pmids as $pmid) {
			$url = $pubMedIdUrl . urlencode((string)$pmid);
			
			$ch = curl_init();		// initialize curl handle
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
			curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 8s
			$result = curl_exec($ch);
			
			$doc = new DOMDocument();
			@$doc->loadHTML($result);
			$xml = simplexml_import_dom($doc);
			
			$articles = $xml->xpath("//pubmedarticleset/pubmedarticle");
			
			foreach ($articles as $entry) {
				$articleData = array();
				$authors = $entry->medlinecitation->article->authorlist->author;
				if (!empty($authors)) {
					foreach ($authors as $author) {
						$articleData["authors"][] = array("rft.aufirst"=>$author->forename, "rft.aulast"=>$author->lastname);
					}
				}
				$articleData["rft.jtitle"] = $entry->medlinecitation->article->journal->title;
				$articleData["rft.atitle"] = $entry->medlinecitation->article->articletitle;
				$articleData["rft.date"] = $entry->medlinecitation->article->articledate->year;
				$articleData["rft.issn"] = $entry->medlinecitation->medlinejournalinfo->issnlinking;
				$articleData["id"] = array_shift($entry->pubmeddata->articleidlist->xpath("articleid[@idtype='pubmed']"));
				$articleData["id_type"] = "pmid";
				$citations[] = generateCitation ($articleData);
			}
		}
	}
 
	/* arXiv */
	
	global $arxivUrl;
	$ch = curl_init();		// initialize curl handle
	curl_setopt($ch, CURLOPT_URL, $arxivUrl.urlencode($title));
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
	curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 8s
	$result = curl_exec($ch);
	
	$cerror = curl_error($ch);
	if (($cerror == null & strlen($cerror) == 0)) {
		$doc = new DOMDocument();
		@$doc->loadHTML($result);
		$xml = simplexml_import_dom($doc);
		
		$entries = $xml->xpath("//entry");
		
		foreach ($entries as $entry) {
			$articleData = array();
			foreach ($entry->author as $author) {
				$articleData["authors"][] = array("rft.au"=>$author->name);
			}
			$articleData["rft.jtitle"] = $entry->journal_ref;
			$articleData["rft.atitle"] = $entry->title;
			$articleData["rft.date"] = array_shift(preg_split("/-/", $entry->published, 2));
			$articleData["id"] = str_replace("http://arxiv.org/abs/","",$entry->id);
			$articleData["id_type"] = "arxiv";
			$citations[] = generateCitation ($articleData);
		}
	}
	
	return $citations;
}

// Input: post Uri, post ID, DB handle
// Return: array with citations or null
function checkCitations ($postUri, $postId, $db) {
	$ch = curl_init(); // initialize curl handle
	curl_setopt($ch, CURLOPT_URL, $postUri); // set url
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return into a variable
	curl_setopt($ch, CURLOPT_HEADER, 0); // do not include the header
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
	curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 8s
	$html = curl_exec($ch); // Execute curl
	
	$cerror = curl_error($ch);
	if (($cerror != null & strlen($cerror) > 0)) {
		return $cerror;
	}
	
	curl_close($ch); // Close the connection
	
	$doc = new DOMDocument();
	@$doc->loadHTML($html);
	$xpath = new DOMXPath( $doc );
	$xml = simplexml_import_dom($doc);
	
	$citations = array();
	// Search for Z3988 class and return all its contents
	$getCitations = $xml->xpath('//*[@class=\'Z3988\']');
	// Parse citation
	foreach ($getCitations as $data) {
		$values = urldecode((string)$data->attributes()->title);
		preg_match("/(?<=bpr3.included=)./", $values, $rbInclude);
		preg_match("/(?<=ss.included=)./", $values, $ssInclude);
		if (($rbInclude[0] == 1 && $ssInclude[0] == NULL) || ($ssInclude[0] == 1)) {
			insertCitationMarker (postIdToBlogId ($postId, $db), $db);
			storeTopics ($postId, $values, $db);
			$citations[] = $data -> asXML();;
		}
	}
	
	return $citations;
}

// Input: Post ID, Topics Data, DB handle
// Action: Store topics from the citation
function storeTopics ($postId, $topicsData, $db) {
	preg_match("/(?<=bpr3.tags=)[^&;]+/", $topicsData, $topics);
	$topic = preg_split("/[,\/]/", array_shift($topics));
	foreach ($topic as $category) {
		$tag = trim($category);
		$topicId = addTopic($tag, $db);
		linkTopicToPost($postId, $topicId, 2, $db);
	}
}

// Input: Citation, Post ID, DB handle
// Action: Store citation
function storeCitation ($articleData, $postId, $db) {
	
	$articleId = storeArticle ($articleData, 0, $db);
	
	$generatedCitation = generateCitation ($articleData);
	$citation = mysql_real_escape_string($generatedCitation);
	
	// Post has citation
	$markCitation = "UPDATE BLOG_POST SET BLOG_POST_HAS_CITATION=1 WHERE BLOG_POST_ID=$postId";
	mysql_query($markCitation, $db);
	// Check that the citation isn't already stored
	$citationId = citationTextToCitationId ($citation, $db);
	if ($citationId == NULL) {
		// Insert Citation
		$insertCitation = "INSERT IGNORE INTO CITATION (ARTICLE_ID, CITATION_TEXT) VALUES ($articleId, '$citation')";
		mysql_query($insertCitation, $db);
		if (mysql_error()) {
			die ("InsertCitation: " . mysql_error() . "\n");
		}
		// Get citation ID
		$citationId = mysql_insert_id();
	}
	
	// Assign citation ID to post ID
	$citationToPost = "INSERT IGNORE INTO POST_CITATION (CITATION_ID, BLOG_POST_ID) VALUES ('$citationId', '$postId')";
	mysql_query($citationToPost, $db);
	if (mysql_error()) {
		die ("CitationToPost: " . mysql_error() . "\n");
	}
	
	return $generatedCitation;
}

// Input: post ID, citation ID, DB handle.
// Output: Parsed citations. 
function removeCitations($postId, $citationId, $db) {
	$sql = "DELETE FROM POST_CITATION WHERE BLOG_POST_ID = $postId";
	if ($citationId != NULL) {
		$sql .= " AND CITATION_ID = $citationId";
	}
	mysql_query($sql, $db);
	
	$sql = "UPDATE BLOG_POST SET BLOG_POST_HAS_CITATION = 0 WHERE BLOG_POST_ID=$postId";
	mysql_query($sql, $db);
}

// Input: Array of parsed article data, Boolean identifying the source, DB handle.
// Output: ID of the inserted article.
function storeArticle ($articleData, $source, $db) {
	global $rfrId;
	if (! $articleData["rfr_id"]) $articleData["rfr_id"] = "info:sid/$rfrId";
	if (! $articleData["id_type"]) $articleData["id_type"] = "other";
	
	$rftatitle = NULL;
	$rftjtitle = NULL;
	$rftissue = NULL;
	$rftvolume = NULL;
	$rftissn = NULL;
	$rftartnum = NULL;
	$rftdate = NULL;
	$rftspage = NULL;
	$rftepage = NULL;
	
	foreach ($articleData as $key => $item) {
		$key = str_replace(array(".", "_"), "", $key);
		if (! is_array($item)) {
			$$key = mysql_real_escape_string($item, $db);
		}
		else {
			$$key = $item;
		}
	}
	
	$sql = "SELECT ARTICLE_ID FROM ARTICLE WHERE ARTICLE_TITLE = '$rftatitle' AND ARTICLE_JOURNAL_TITLE = '$rftjtitle' AND ARTICLE_JOURNAL_ISSUE = '$rftissue' AND ARTICLE_JOURNAL_VOLUME = '$rftvolume' AND ARTICLE_ISSN = '$rftissn' AND ARTICLE_NUMBER = '$rftartnum' AND ARTICLE_PUBLICATION_DATE = '$rftdate' AND ARTICLE_START_PAGE = '$rftspage' AND ARTICLE_END_PAGE = '$rftepage' AND ARTICLE_FROM_ORIGINAL_SOURCE = '$source'";
	$sql = str_replace("= ''", "IS NULL", $sql);
	$result = mysql_query($sql, $db);
		
	$row = mysql_fetch_array($result);
	$articleId = $row['ARTICLE_ID'];
	
	if (! $articleId) {
		$sql = "INSERT IGNORE INTO ARTICLE (ARTICLE_TITLE, ARTICLE_JOURNAL_TITLE, ARTICLE_JOURNAL_ISSUE, ARTICLE_JOURNAL_VOLUME, ARTICLE_ISSN, ARTICLE_NUMBER, ARTICLE_PUBLICATION_DATE, ARTICLE_START_PAGE, ARTICLE_END_PAGE, ARTICLE_FROM_ORIGINAL_SOURCE) VALUES ('$rftatitle', '$rftjtitle', '$rftissue', '$rftvolume', '$rftissn', '$rftartnum', '$rftdate', '$rftspage', '$rftepage', '$source')";
		$sql = str_replace("''", "NULL", $sql);
		mysql_query($sql, $db);
		
		$articleId = mysql_insert_id();
	}
	
	if (isset($authors)) {
		foreach ($authors as $author) {
			$authorFullName = NULL;
			$authorFirstName = NULL;
			$authorLastName = NULL;
			
			if (isset($author["rft.au"])) {
				$authorFullName = mysql_real_escape_string($author["rft.au"]);
			}
			if (isset($author["rft.aufirst"])) {
				$authorFirstName = mysql_real_escape_string($author["rft.aufirst"]);
			}
			if (isset($author["rft.aulast"])) {
				$authorLastName = mysql_real_escape_string($author["rft.aulast"]);
			}
			
			$sql = "SELECT ARTICLE_AUTHOR_ID FROM ARTICLE_AUTHOR WHERE ARTICLE_AUTHOR_FULL_NAME = '$authorFullName' AND ARTICLE_AUTHOR_FIRST_NAME = '$authorFirstName' AND ARTICLE_AUTHOR_LAST_NAME = '$authorLastName'";
			$sql = str_replace("= ''", "IS NULL", $sql);
			$result = mysql_query($sql, $db);
			
			$row = mysql_fetch_array($result);
			$articleAuthorId = $row['ARTICLE_AUTHOR_ID'];
			
			if ($result == null || mysql_num_rows($result) == 0) {	
				$sql = "INSERT IGNORE INTO ARTICLE_AUTHOR (ARTICLE_AUTHOR_FULL_NAME, ARTICLE_AUTHOR_FIRST_NAME, ARTICLE_AUTHOR_LAST_NAME) VALUES ('$authorFullName', '$authorFirstName', '$authorLastName')";
				$sql = str_replace("''", "NULL", $sql);
				mysql_query($sql, $db);
				
				$articleAuthorId = mysql_insert_id();
			}
			$sql = "INSERT IGNORE INTO ARTICLE_AUTHOR_LINK (ARTICLE_ID, ARTICLE_AUTHOR_ID) VALUES ($articleId, $articleAuthorId)";
			mysql_query($sql, $db);
			
			$articleAuthorId = NULL;
		}
	}
	
	$sql = "INSERT IGNORE INTO ARTICLE_IDENTIFIER (ARTICLE_IDENTIFIER_TYPE, ARTICLE_IDENTIFIER_TEXT, ARTICLE_ID) VALUES ('$idtype', '$id', '$articleId')";
	mysql_query($sql, $db);
	
	return $articleId;
}

// Input: Blog ID, DB handle.
// Action: Insert mark to scan blogs for citations.
function insertCitationMarker ($blogId, $db) {
	$sql = "REPLACE INTO SCAN_POST (BLOG_ID, MARKER_DATE_TIME, MARKER_TYPE_ID) VALUES ($blogId, NOW(), 1)";
	mysql_query($sql, $db);
}

// Input: citation text in COinS format
// Output: associative array of citation metadata
function parseCitation ($citation) {
	$dom = new DOMDocument();
	@$dom->loadHTML($citation);
	$xml = simplexml_import_dom($dom);
	$xpath = $xml->xpath("//span[@class='Z3988']");
	if (empty($xpath)) return NULL;	// this is not COinS format

	$citationTitle = $xpath[0]->attributes()->title;

	return decodeCitationTitle($citationTitle);
}

// Input: Coins metadata
// Output: associative array of citation metadata
function decodeCitationTitle ($citationTitle) {
	// Split all the different information
	$result = preg_split("/&/", $citationTitle);
	
	$i = 0;
	$values = array();
	$authors = array();
	foreach ($result as $value) {
		// Split title and value
		$elements = preg_split("/=/", $value, 2);
		$attribute = $elements[0];
		// If there is more than one author, add to array
		if (($attribute == "rft.au" || $attribute == "rft.aufirst" || $attribute == "rft.aulast") && $i != 10) {
			if (isset($authors[$i][$attribute])) {
				$i++;
			}
			$authors[$i][$attribute] = urldecode($elements[1]);
		}
		elseif (isset($elements[1])) {
			$values[$attribute] = NULL;
			$values[$attribute] .= urldecode($elements[1]);
		}
	}
	$values["authors"] = $authors;
	
	// Get ID and ID type (DOI, PMID, arXiv...)
	if (isset($values["rft_id"])) {
		preg_match("/(?<=info:)[^\/]+/", $values["rft_id"], $matchType);
		preg_match("/(?<=\/).+/", $values["rft_id"], $matchID);
		$values["id_type"] = $matchType[0];
		$values["id"] = $matchID[0];
	}
	
	// Check if citation should be included
	preg_match("/(?<=bpr3.included=)./", $citationTitle, $rbInclude);
	preg_match("/(?<=ss.included=)./", $citationTitle, $ssInclude);
	if (!empty($rbInclude)) {
		$values["rbIncluded"] = $rbInclude[0];
	}
	if (!empty($ssInclude)) {
		$values["ssIncluded"] = $ssInclude[0];
	}

	return $values;
}

// Input: Array with citation data
// Output: COinS-format citation text for use in HTML
function generateCitation ($articleData) {
	global $rfrId;
	
	$builtAuthors = NULL;
	$date = NULL;
	$title = NULL;
	$journal = NULL;
	$rftvolume = NULL;
	$issue = NULL;
	$rftspage = NULL;
	$rftepage = NULL;
	$pages = NULL;
	$type = NULL;
	$url = NULL;
	
	if (empty($articleData["rfr_id"])) $articleData["rfr_id"] = "info:sid/$rfrId";
	if (empty($articleData["id_type"])) $articleData["id_type"] = "other";
	
	// List of keys which should be represented in the associative array that was passed in as $articleData
	$supportedKeys = array("rft.atitle", "rft.title", "rft.jtitle", "rft.stitle", "rft.date", "rft.volume", "rft.issue", "rft.spage", "rft.epage", "rft.pages", "rft.artnum", "rft.issn", "rft.eissn", "rft.eissn", "rft.aucorp", "rft.isbn", "rft.coden", "rft.sici", "rft.genre", "rft.chron", "rft.ssn", "rft.quarter", "rft.part", "rft.auinit", "rft.auinit1", "rft.auinitm", "rft.auinitsuffix", "rfr_id");
	
	$citation = "<span class=\"Z3988\" title=\"ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Ajournal";
	
	foreach ($articleData as $key => $item) {
		if (in_array($key, $supportedKeys)) {
			$citation .= "&amp;$key=".urlencode($item);
		}
		elseif ($key == "id") {
			$citation .= "&amp;rft_id=".urlencode("info:".$articleData["id_type"]."/".$articleData["id"]);
		}
		$keyName = str_replace(array("_","."), "", $key);
		$$keyName = $item;
	}
		
	if (isset($authors)) {
		$builtAuthors = "";
		$i = count($authors);
		foreach ($authors as $n => $author) {
			if (isset($author["rft.aufirst"]) || isset($author["rft.aulast"])) {
				$firstName = "";
				$lastName = "";
				if (isset($author["rft.aufirst"])) {
					$firstName = $author["rft.aufirst"];
				}
				if (isset($author["rft.aulast"])) {
					$lastName = $author["rft.aulast"];
				}
				$builtFirstName = "";
				$citation .= "&amp;rft.au=".urlencode("$lastName $firstName")."&amp;rft.aulast=".urlencode($lastName)."&amp;rft.aufirst=".urlencode($firstName);
				
				preg_match_all("/[^\.\,\s-]+/", $firstName, $matchResult);
				foreach ($matchResult[0] as $item) {
				 $builtFirstName .= strtoupper(mb_substr($item, 0, 1, 'UTF-8').".");
				}
				$builtAuthors .= "$lastName $builtFirstName";
				if ($n == ($i-2) || $n == 9) {
					$builtAuthors .= " & ";
				}
				elseif ($n != $i-1 && $n != 10) {
					$builtAuthors .= ", ";
				}
			}
			elseif (isset($author["rft.au"])) {
				$citation .= "&amp;rft.au=".urlencode($author["rft.au"]);
				$builtAuthors .= $author["rft.au"];
				if ($n == ($i-2) || $n == 9) {
					$builtAuthors .= " & ";
				}
				elseif ($n != $i-1 && $n != 10) {
					$builtAuthors .= ", ";
				}	
			}
			if ($n == 10) {
				break;
			}
		}
	}
	
	if (empty($ssInclude)) $ssInclude = 1;
	if (empty($rbInclude)) $rbInclude = 1;
	
	$citation .= "&amp;rfs_dat=ss.included=$ssInclude&amp;rfe_dat=bpr3.included=$rbInclude";
	
	if (!empty($rbTags) && $rbInclude == 1) {
		$citation .= ";bpr3.tags=".urlencode(implode(",",$rbTags));
	}
	$citation .= "\">";
	
	if (!empty($rftdate)) {
		$date = "($rftdate).";
	}
	if (!empty($rftatitle)) {
		$title = "$rftatitle,";
	}
	if (!empty($rftjtitle)) {
		$journal = "$rftjtitle,";
	}
	if (!empty($rftissue)) {
		$issue = "($rftissue)";
	}
	if (!empty($rftspage) || !empty($rftepage)) {
		$pages .= "$rftspage";
		if (!empty($rftspage) && !empty($rftepage)) {
			$pages .= "-";
		}
		$pages .= "$rftepage.";
	}
	
	if ($idtype == "doi") {
		$type = "DOI:";
		$url = "<a rel=\"author\" href=\"http://dx.doi.org/".urlencode($id)."\">$id</a>";
	}
	elseif ($idtype == "arxiv") {
		$type = "arXiv:";
		$url = "<a rel=\"author\" href=\"http://arxiv.org/abs/".htmlspecialchars($id)."\">$id</a>";
	}
	elseif ($idtype == "pmid") {
		$type = "PMID:";
		$url = "<a rel=\"author\" href=\"http://www.ncbi.nlm.nih.gov/pubmed/".urlencode($id)."\">$id</a>";
	}
	elseif (!empty($id) || !empty($rftartnum)) {
		$type = "Other:";
		if (empty($id)) $id = "Link";
		if (!empty($rftartnum)) $url = "<a rev=\"review\" href=\"$rftartnum\">$id</a>";
		else $url = $id;
	}
	
	$citation .= "$builtAuthors $date $title <span style=\"font-style:italic;\">$journal $rftvolume</span> $issue $pages $type $url</span>";
	
	return $citation;
}

/* Claim stuff */

// Input: blog ID, user ID, DB handle
// Returns: claim token associated with this blog ID and user ID, and currently pending
function getClaimToken($blogId, $userId, $db) {
	$sql = "SELECT CLAIM_TOKEN FROM CLAIM_BLOG WHERE BLOG_ID=$blogId AND USER_ID=$userId AND CLAIM_STATUS_ID=0";

	$results = mysql_query($sql, $db);

	if ($results == null || mysql_num_rows($results) == 0) {
		return;
	}

	$row = mysql_fetch_array($results);
	return $row['CLAIM_TOKEN'];
}

function generateClaimToken() {
	return uniqid("claimtoken-");
}

function doClaimBlog($blogId, $userName, $db) {

	$userId = getUserId($userName, $db);

	// If there is already a pending request, let them choose to verify that instead
	$claimToken = retrievePendingClaimToken($blogId, $userId, $db);

	// If there was no pending request, create one
	if ($claimToken == null) {
		$claimToken = generateClaimToken();
		storeClaimToken($claimToken, $blogId, $userId, $db);
	}

	displayBlogClaimToken($claimToken, $blogId, $db);
}

function doVerifyClaim($blogId, $userName, $db) {
	$userId = getUserId($userName, $db);
	$result = verifyClaim($blogId, $userId, getBlogUri($blogId, $db), getBlogSyndicationUri($blogId, $db), $db);

	if ($result === "no-claim") {
		doClaimBlog($blogId, $userName, $db);
		return;
	} else if ($result == "verified") {
		$claimToken = getClaimToken($blogId, $userId, $db);
		$success = markClaimTokenVerified($blogId, $userId, $claimToken, $db);
		if (! $success) {
			print "<p class=\"ss-error\">Failed to update database.</p>";
			return;
		}
		displayUserAuthorLinkForm($blogId, $userId, $userName, $db);

	} else {
		$claimToken = getClaimToken($blogId, $userId, $db);
		print "<p class=\"ss-error\">Your claim token ($claimToken) was not found in your blog's feed.</p>\n";
		displayBlogClaimToken($claimToken, $blogId, $db);
	}
}

// Input: blog ID, user ID, new blog URL, DB handle
// Return: true if the specified blog contains the claim token specified in the CLAIM_BLOG table (select by user ID and blog ID)
function verifyClaim($blogId, $userId, $blogUri, $blogSyndicationUri, $db) {

	$claimToken = retrievePendingClaimToken ($blogId, $userId, $db);
	if ($claimToken == null) {
		return "no-claim";
	}
	
	$feed = getSimplePie($blogSyndicationUri);
	if ($feed->error()) {
		print "ERROR: $blogUri (ID $blogId): " . $feed->error() . "\n";
	}
	else {
		// Verify that the token exists in the syndication feed
		foreach ($feed->get_items(0, 5) as $item) {
			$blogContent = $item->get_content();
			$pos = strpos($blogContent, $claimToken);
			if (strcmp("", $pos) != 0 && $pos >= 0) {
				return "verified";
			}
		}
	}

	return "unverified";

}

// Input: blog ID, user ID, DB handle
// Returns: pending claim token associated with this blog ID and user ID, or null
function retrievePendingClaimToken($blogId, $userId, $db) {
	$sql = "SELECT CLAIM_TOKEN FROM CLAIM_BLOG WHERE USER_ID=$userId and BLOG_ID=$blogId AND CLAIM_STATUS_ID=0";

	$results = mysql_query($sql, $db);
	if ($results == null || mysql_num_rows($results) == 0) {
		return null;
	}

	$row = mysql_fetch_array($results);
	return $row['CLAIM_TOKEN'];
}

// Input: blog ID, user ID, DB handle
// Returns: verified claim token associated with this blog ID and user ID, or null
function retrieveVerifiedClaimToken($blogId, $userId, $db) {
	$sql = "SELECT CLAIM_TOKEN FROM CLAIM_BLOG WHERE USER_ID='$userId' and BLOG_ID='$blogId' AND CLAIM_STATUS_ID = '1'";
	$result = mysql_query($sql, $db);
	if ($result == null || mysql_num_rows($result) == 0) {
		return null;
	}

	$row = mysql_fetch_array($result);
	return $row['CLAIM_TOKEN'];
}

// Input: blog ID, user ID, claim token, DB handle
// Action: update specified claim token object to note that it was verified
// Returns: true on success, false on failure
function markClaimTokenVerified($blogId, $userId, $claimToken, $db) {
	$sql = "UPDATE CLAIM_BLOG SET CLAIM_STATUS_ID=1 WHERE USER_ID=$userId and BLOG_ID=$blogId AND CLAIM_STATUS_ID=0 AND CLAIM_TOKEN='$claimToken'";

	mysql_query($sql, $db);

	return (!mysql_error());
}

// Input: blog ID, user ID, claim token, DB handle
// Action: update specified claim token object to note that it is no longer verified (eg someone has asked to edit it), and refresh token
// Returns: true on success, false on failure
function clearClaimToken($blogId, $userId, $claimToken, $db) {
	$claimToken = uniqid("sciseekclaimtoken-");

	$sql = "UPDATE CLAIM_BLOG SET (CLAIM_STATUS_ID, CLAIM_TOKEN) VALUES (0, '$claimToken' WHERE USER_ID=$userId and BLOG_ID=$blogId";

	mysql_query($sql, $db);

	return (!mysql_error());
}

/* Social Networks */

function getGoogleTokens($code, $returnUrl) {
	global $googleClientId, $googleClientSecret;
	
	$googleTokens = json_decode(getPage("https://accounts.google.com/o/oauth2/token", "code=$code&client_id=$googleClientId&client_secret=$googleClientSecret&redirect_uri=$returnUrl&grant_type=authorization_code"));
	
	return $googleTokens;
}

function getGoogleUser($oauthToken) {
	$googleUser = json_decode(getPage("https://www.googleapis.com/oauth2/v1/userinfo?access_token=".$oauthToken));
	
	return $googleUser;
}

function getGooglePlusUser($googleUserId) {
	global $googleApiKey;
	
	$googlePlusUser = json_decode(getPage("https://www.googleapis.com/plus/v1/people/".$googleUserId."?key=$googleApiKey"));
	
	return $googlePlusUser;
}

function twitterConnection($url, $method = "GET", $query = array(), $oauthToken = NULL, $oauthSecret = NULL, $returnUrl = NULL) {
	global $twitterConsumerKey, $twitterConsumerSecret, $homeUrl;
	date_default_timezone_set("UTC");
	
	$token_secret = $oauthSecret;
	$consumer_key = $twitterConsumerKey;
	$consumer_secret = $twitterConsumerSecret;
	
	$oauth = array(
			'oauth_consumer_key' => $consumer_key,
			'oauth_nonce' => (string)mt_rand(),
			'oauth_timestamp' => time(),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_version' => '1.0'
	);
	
	if ($oauthToken) {
		$oauth["oauth_token"] = $oauthToken;
	}
	
	if ($returnUrl) {
		$oauth["oauth_callback"] = $returnUrl;
	}
	
	$oauth = array_map("rawurlencode", $oauth);
	$query = array_map("rawurlencode", $query);
	
	$arr = array_merge($oauth, $query);
	
	asort($arr);
	ksort($arr);

	$querystring = urldecode(http_build_query($arr, '', '&'));
	
	$base_string = $method."&".rawurlencode($url)."&".rawurlencode($querystring);
	
	$key = rawurlencode($consumer_secret)."&".rawurlencode($token_secret);
	
	$signature = rawurlencode(base64_encode(hash_hmac('sha1', $base_string, $key, true)));
	
	if ($method == "GET") {
		$url .= "?".http_build_query($query);
	}
	
	$oauth['oauth_signature'] = $signature;
	ksort($oauth);
	
	$oauth = array_map("add_quotes", $oauth);
	
	$auth = "OAuth " . urldecode(http_build_query($oauth, '', ', '));
	
	$options = array( CURLOPT_HTTPHEADER => array("Authorization: $auth"),
	CURLOPT_HEADER => false,
	CURLOPT_URL => $url,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_SSL_VERIFYPEER => false);
	if ($method == "POST") {
		$options[CURLOPT_POST] = true;
		$options[CURLOPT_POSTFIELDS] = http_build_query($query);
	}
	
	// do our business
	$feed = curl_init();
	curl_setopt_array($feed, $options);
	$result = curl_exec($feed);
	curl_close($feed);
	
	//$twitter_data = json_decode($json);
	return $result;
}

function add_quotes($str) { return '"'.$str.'"'; }

function urlencodeRFC3986($string) {
   return str_replace('%7E', '~', rawurlencode($string));
}

function getTwitterToken($returnUrl = NULL) {
	$url = "https://api.twitter.com/oauth/request_token";
	
	$tries = 10;
	$oauthToken = NULL;
	$tokens = array();
	while ($oauthToken == NULL && $tries > 0) {
		parse_str(twitterConnection($url, "POST", array(), NULL, NULL, $returnUrl), $tokens);
		$oauthToken = $tokens["oauth_token"];

		$tries--;
	}

	$_SESSION["oauth_token"] = $oauthToken;
	$_SESSION["oauth_token_secret"] = $tokens["oauth_token_secret"];
	
	return $tokens;
}


// Input: URL to return to, Boolean to determine if Twitter should ask for confirmation again.
// Output: Twitter authorization URL
function getTwitterAuthURL ($returnUrl, $authorize = FALSE) {
	$tokens = getTwitterToken($returnUrl);
	if ($authorize == TRUE) {
		$authUrl = "https://api.twitter.com/oauth/authenticate?oauth_token=".$tokens["oauth_token"];
	} else {
		$authUrl = "https://api.twitter.com/oauth/authorize?oauth_token=".$tokens["oauth_token"];
	}

	return $authUrl;
}

// Input: User Auth Token, User Secret Auth Token
// Output: Connection to Twitter User Account
function getTwitterAuthTokens($oauthToken = NULL, $oauthSecret = NULL, $oauthVerifier = NULL) {;
	$url = "https://api.twitter.com/oauth/access_token";
	$parameters = array(
		"oauth_verifier" => $oauthVerifier
	);
	
	parse_str(twitterConnection($url, "POST", $parameters, $oauthToken, $oauthSecret), $tokens);
	
	return $tokens;
}

// Input: User Auth Token, User Secret Auth Token
// Output: Connection to Twitter User Account
function addToTwitterList($twitterUserId) {
	global $twitterListId;
	global $twitterListToken;
	global $twitterListTokenSecret;

	$url = "https://api.twitter.com/1.1/lists/members/create.json";
	$parameters = array("list_id" => $twitterListId, "user_id" => $twitterUserId);
	twitterConnection($url, "GET", $parameters, $twitterListToken, $twitterListTokenSecret);

	return FALSE;
}

// Input: Twitter User ID or Twitter handle
// Output: Get Twitter user profile details
function getTwitterUserDetails($twitterUserId = NULL, $twitterUserName = NULL, $oauthToken = NULL, $oauthSecret = NULL) {
	$url = "https://api.twitter.com/1.1/users/lookup.json";
	$parameters = array(
		"user_id" => $twitterUserId
	);
	
	$data = twitterConnection($url, "GET", $parameters, $oauthToken, $oauthSecret);
	$details = array_shift(json_decode($data));

	return $details;
}

// Input: Twitter User ID or Twitter handle
// Output: Get Twitter user profile details
function getTwitterUserTweets($twitterUserId, $oauthToken = NULL, $oauthSecret = NULL, $limit = 15) {
	$url = "https://api.twitter.com/1.1/statuses/user_timeline.json";
	$parameters = array(
		"user_id" => $twitterUserId,
		"count" => $limit
	);
	
	$tweets = json_decode(twitterConnection($url, "GET", $parameters, $oauthToken, $oauthSecret));

	return $tweets;
}

// Input: Twitter List ID
// Output: Tweets from twitter list
function getTwitterList($twitterListId, $count = 9) {
	global $twitterListToken;
	global $twitterListTokenSecret;

	$url = "https://api.twitter.com/1.1/lists/statuses.json";
	$parameters = array(
		"list_id" => $twitterListId,
		"count" => $count
	);
	$twitterListResults = json_decode(twitterConnection($url, "GET", $parameters, $twitterListToken, $twitterListTokenSecret));
	
	return $twitterListResults;
}

// Input: Tweet text
// Output: Tweet text with links
function tweetContent($tweet) {
	$tweet = preg_replace('`\b(?:(?:https?|ftp|file)://|www\.|ftp\.)[-A-Z0-9+&@#/%=~_|$?!:,.]*[A-Z0-9+&@#/%=~_|$]`i', '<a href="$0">$0</a>', $tweet);
	$tweet = preg_replace('/(^|\s)@([a-z0-9_]+)/i', '$1<a href="http://www.twitter.com/$2">@$2</a>', $tweet);
	$tweet = preg_replace('/#([\\d\\w]+)/', '<a href="http://twitter.com/#search?q=%23$1">$0</a>', $tweet);
	
	return $tweet;
}

/* Template/display functions */

// Input: unique blog token, blog ID, user ID, DB handle
// Action: store blog token, blog ID, and user ID in the CLAIM_BLOG table
// Returns: null on success, error message on error
function storeClaimToken($claimToken, $blogId, $userId, $db) {

	// Is there already a pending claim token? If so, set it to "overridden" (2)
	$sql = "UPDATE CLAIM_BLOG SET CLAIM_STATUS_ID=2 WHERE BLOG_ID = '$blogId' AND USER_ID = '$userId' AND CLAIM_STATUS_ID = '0'";
	mysql_query($sql, $db);
	if (mysql_error()) {
		die ("storeClaimToken: " . mysql_error() . " ($sql)\n");
	}

	// Insert into db
	$sql = "INSERT INTO CLAIM_BLOG (BLOG_ID, USER_ID, CLAIM_TOKEN, CLAIM_STATUS_ID, CLAIM_DATE_TIME) VALUES ($blogId, $userId, '$claimToken', 0, NOW())";
	mysql_query($sql, $db);

	if (mysql_error()) {
		die ("storeClaimToken: " . mysql_error() . " ($sql)\n");
	}
}

// Input: blog claim token, blog ID, display name of user, DB handle
// Action: Display message to user with blog claim token, explaining how to use it to claim the blog in question
function displayBlogClaimToken($claimToken, $blogId, $db) {
	$blogName = NULL;
	$blogUri = NULL;
	$blogSyndicationUri = NULL;
	$blogDescription = NULL;
	$blogAddedDate = NULL;
	$blogCrawledDate = NULL;
	$topic1 = NULL;
	$topic2 = NULL;
	$blogStatusId = NULL;
	$twitterHandle = NULL;
	$crawl = NULL;
	
	if (isset($_REQUEST["blogName"])) {
		$blogName = $_REQUEST["blogName"];
	}
	if (isset($_REQUEST["blogUri"])) {
		$blogUri = $_REQUEST["blogUri"];
	}
	if (isset($_REQUEST["blogSyndicationUri"])) {
		$blogSyndicationUri = $_REQUEST["blogSyndicationUri"];
	}
	if (isset($_REQUEST["blogDescription"])) {
		$blogDescription = $_REQUEST["blogDescription"];
	}
	if (isset($_REQUEST["addedDate"])) {
		$blogAddedDate = $_REQUEST["addedDate"];
	}
	if (isset($_REQUEST["crawledDate"])) {
		$blogCrawledDate = $_REQUEST["crawledDate"];
	}
	if (isset($_REQUEST["topic1"])) {
		$topic1 = $_REQUEST["topic1"];
	}
	if (isset($_REQUEST["topic2"])) {
		$topic2 = $_REQUEST["topic2"];
	}
	if (isset($_REQUEST["blogStatus"])) {
		$blogStatusId = $_REQUEST["blogStatus"];
	}
	if (isset($_REQUEST["twitterHandle"])) {
		$twitterHandle = $_REQUEST["twitterHandle"];
	}
	if (isset($_REQUEST["crawl"])) {
		$crawl = $_REQUEST["crawl"];
	}
	
	if (empty($blogId)) {
		$blogId = $_REQUEST["blogId"];
	}
	if (empty($blogName)) {
		$blogName = getBlogName($blogId, $db);
	}

	print "<h3>Add claim token to your site.</h3>
	<p>To claim this site ($blogName), we need to verify that you actually are an author of this blog. Please place the following HTML code in the <span class=\"ss-bold\">most recent</span> of your posts. It will be invisible to readers, and you can remove it once your site has been verified by our system.</p>\n
	<p><span class=\"ss-bold\">Claim token:</span> $claimToken</p>\n
	<p><span class=\"ss-bold\">HTML code to include:</span> &lt;p&gt;&lt;span style=\"display:none\"&gt;$claimToken&lt;/span&gt;&lt;/p&gt;\n
	<p>Once the token is displayed in a post of your site, press the button below.</p> 
	<form method='post' name='doVerifyForm'>\n
	<input type='hidden' name='step' value='verify' />\n
	<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />
	<input type=\"hidden\" name=\"blogName\" value=\"".htmlspecialchars($blogName, ENT_QUOTES)."\" />
	<input type=\"hidden\" name=\"blogUri\" value=\"".htmlspecialchars($blogUri, ENT_QUOTES)."\" />
	<input type=\"hidden\" name=\"blogSyndicationUri\" value=\"".htmlspecialchars($blogSyndicationUri, ENT_QUOTES)."\" />
	<input type=\"hidden\" name=\"blogDescription\" value=\"".htmlspecialchars($blogDescription, ENT_QUOTES)."\" />
	<input type=\"hidden\" name=\"twitterHandle\" value=\"".htmlspecialchars($twitterHandle, ENT_QUOTES)."\" />
	<input type=\"hidden\" name=\"topic1\" value=\"$topic1\" />
	<input type=\"hidden\" name=\"topic2\" value=\"$topic2\" />
	<input type=\"hidden\" name=\"blogStatus\" value=\"$blogStatusId\" />
	<input type=\"hidden\" name=\"crawl\" value=\"$crawl\" />
	<p><input class=\"ss-button\" name=\"submit\" type=\"submit\" value=\"Continue to the next step\" /></p>
	</form>";
}


function displayUserAuthorLinkForm($blogId, $userId, $userName, $db) {
	global $sitename;
	global $pages;

	if ($blogId == null) {
		print "ERROR: please specify blog ID (displayUserAuthorLinkForm)\n";
		return;
	}

	$blogName = getBlogName($blogId, $db);

	$authorList = getAuthorList ($blogId, TRUE, $db);
	$unknownAuthorId = getUnknownAuthorId($blogId, $db);

	// Only active users can claim blogs
	$userStatus = getUserStatus($userId, $db);
	if ($userStatus != 0) {
		print "<p class=\"ss-error\">You cannot claim this blog as your account is not currently active ($userStatus). You may <a href='".$pages["contact"]->getAddress()."'>contact us</a> to ask for more information.</p>\n";
		return;
	}

	if (mysql_num_rows($authorList) == 0 && $unknownAuthorId == null) {
		print "<p class=\"ss-error\">There was an error in parsing the feed of this blog. Please <a href='".$pages["contact"]->getAddress()."'>contact us</a> to ask for help in resolving this problem.</p>\n";
		return;
	}

	$blogStatus = getBlogStatusId($blogId, $db);
	if ($blogStatus == 2) { // rejected
		print "<p class=\"ss-error\">This blog has been rejected from the system by an editor. For more information, please <a href='".$pages["contact"]->getAddress()."'>contact us</a>.</p>";
		return;
	}

	if (isBlogClaimable($blogId, $db) == true) {
		print "<h3>Identify yourself as an author of $blogName</h3>\n";

		print "<form method=\"post\">\n";
		print "<input type=\"hidden\" name=\"step\" value=\"userAuthorLinkForm\" />\n";
		print "<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />\n";

		$authorList = getAuthorList($blogId, TRUE, $db);

		if (mysql_num_rows($authorList) > 0) {
			print "<p>This site seems to have the following author(s). Please indicate which one is you (OK to choose more than one).</p>\n";
			$firstAuthor = null;
			while ($row = mysql_fetch_array($authorList)) {
				$authorId = $row["BLOG_AUTHOR_ID"];
				$authorName = $row["BLOG_AUTHOR_ACCOUNT_NAME"];
				$claimed = isAuthorClaimed($authorId, $db);
				if ($firstAuthor == null && (! $claimed)) {
					$firstAuthor = $authorName;
				}
				if ($claimed == TRUE) {
					print "<p><input type=\"checkbox\" name=\"author$authorId\" disabled=\"true\"/> $authorName <span class=\"subtle-text\">(claimed)</span></p>\n";
				} else {
					print "<p><input type=\"checkbox\" name=\"author$authorId\" /> $authorName</p>\n";
				}
			}
		}
		else {
			print "<p class=\"ss-error\">We couldn't find any authors in your feed.</p>";
		}
		
		print "<input class=\"ss-button\" type=\"submit\" value=\"Submit\">";
		print "</form>\n";

	} else {
		print "<p class=\"ss-error\">This site has already been claimed. If you feel this is in error, please <a href='".$pages["contact"]->getAddress()."'>contact us</a>.</p>";
	}

}

function doLinkUserAndAuthor($userId, $userName, $db) {
	global $sitename;
	global $pages;
	$success = false;
	
	$blogId = $_REQUEST["blogId"];
	
	foreach ($_REQUEST as $name => $value) {
		$value = $value;
		if (substr($name, 0, 6) === "author" && $value === "on") {
			$authorId = substr($name, 6);
			linkAuthorToUser($authorId, $userId, $db);
			$authorName = getBlogAuthorName($authorId, $blogId, $db);
			$success = true;
		}
	}

	if (! $success) {
		$authorList = getAuthorList ($blogId, TRUE, $db);
		$unknownAuthorId = getUnknownAuthorId($blogId, $db);

		if (mysql_num_rows($authorList) == 0 && $unknownAuthorId == null) {
			print "<p class=\"ss-error\">There was an error in parsing the feed of this blog. Please <a href='".$pages["contact"]->getAddress()."'>contact us</a> to ask for help in resolving this problem.</p>\n";
			return;
		}

		if ($unknownAuthorId != null && ! isAuthorClaimed($unknownAuthorId, $db) && mysql_num_rows($authorList) == 0) {
			linkAuthorToUser($unknownAuthorId, $userId, $db);
			$success = true;
		} else {
			// either there are authors and no "unknown" authors
			// or there are authors AND an "unknown" author (weird!)
			// either way, assume this person wants to link to a KNOWN author
			print "<p class=\"ss-error\">Please choose an author from the list. If your name is not on the list, it may be because you have not posted recently. Try again after a recent post. If you believe your site has been claimed by someone else, please <a href='".$pages["contact"]->getAddress()."'>contact us.</a></p>\n";
			displayUserAuthorLinkForm($blogId, $userId, $userName, $db);
		}
	}

	if (isset($success)) {
		global $userBlogs;
		print "<h3>Claim completed</h3>
		Congratulations, $userName, you've claimed your blog. Click on '<a href=\"".$pages["my-sites"]->getAddress()."\">My Sites</a>' to edit your settings.<br />\n";
	}

}

/*
 * XML parser functions
 */

// Input: string containing XML document, XSLT stylesheet filename
// Output: string containing transformed XML
function transformXmlString ($xmlStr, $xslFile, $params=null) {
	$dom = new DOMDocument();
	$dom->loadXML($xmlStr);

	$xslt = new xslTProcessor();
	$xsl = new SimpleXMLElement(file_get_contents($xslFile));
	$xslt->importStylesheet($xsl);

	if ($params != null) {
		foreach ($params as $paramName => $paramValue) {
			$xslt->setParameter("", $paramName, $paramValue);
		}
	}

	return $xslt->transformToXML($dom);
}

// Input: handle to XSLT object, parameter name, parameter value
// Action: if parameter value is not null, set the parameter on the XSLT object
function setXsltParameter($xslt, $paramName, $paramValue) {
	if ($paramValue != null) {
		$xslt->setParameter("", $paramName, $paramValue);
	}
}

// Input: XML document containing search parameters
// (For more information, see Search API documentation in SubjectSeeker wiki)
// Out: name->value hash of search parameters
function parseSearchParams ($in) {

	global $currentTag;
	global $type;
	global $params;
	global $currentFilter;
	global $returnList;

	$returnList = array();

	// Parse the XML we got from the search query
	$parser = xml_parser_create();
	xml_set_element_handler($parser, startParamElemHandler, endParamElemHandler);
	xml_set_character_data_handler($parser, paramCharacterDataHandler);
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parse($parser, $in);

	// clean up - we're done
	xml_parser_free($parser);

	// HERE
	// TODO return $returnList, a list of SSFilters and eventually SSOperators
	return $params;

}

function startParamElemHandler($parser, $name, $attribs) {
	global $currentTag;
	global $currentFilter;

	$currentTag = $name;
	if ($name === "filter") {
		// TODO: note this will not work when we start having nested filters
		$currentFilter = new SSFilter;
	}
}

// type: [blog/post/topic]
// filter: name, value, modifier?, idtype?
// operator: operation+, filter+
function endParamElemHandler($parser, $name) {
	global $currentTag;
	global $params;
	global $paramName;
	global $paramValue;
	global $currentFilter;

	/*
	if (strcasecmp($name, "param") == 0) {
		if (! is_array($params[$paramName])) {
			$params[$paramName] = array();
		}

		array_push($params[$paramName], $paramValue);
	}
	*/

	if (strcasecmp($name, "name") == 0) {
		$currentFilter->name = $paramName;
	}

	if (strcasecmp($name, "value") == 0) {
		$currentFilter->value = $paramValue;
	}

	/*
	if (strcasecmp($name, "modifier") == 0) {
		$currentFilter->modifier = $paramModifier;
	}

	if (strcasecmp($name, "idtype") == 0) {
		$currentFilter->idtype = $paramIdType;
	}

	*/

	if (strcasecmp($name, "filter") == 0) {
		array_push ($returnList, $currentFilter);
	}

}

function paramCharacterDataHandler($parser, $cdata) {
	global $currentTag;
	global $type;
	global $paramName;
	global $paramValue;

	if (strcasecmp($currentTag, "type") == 0) {
		$type = $cdata;
	} else if (strcasecmp($currentTag, "name") == 0) {
		$paramName = $cdata;
	} else if (strcasecmp($currentTag, "value") == 0) {
		$paramValue = $cdata;
	}
	// TODO modifier, idtype
	// TODO generalize

}

/*
 * Functions written by other people
 */

// Source: http://davidwalsh.name/bitly-api-php
// Returns the shortened url
function get_bitly_short_url($url, $format='txt') {
	global $bitlyUser;
	global $bitlyKey;
	global $bitlyApiUrl;
	$connectURL = $bitlyApiUrl.$bitlyUser.'&apiKey='.$bitlyKey.'&uri='.urlencode($url).'&format='.$format;
	
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch,CURLOPT_URL,$connectURL);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	
	return $data;
}

function get_bitly_long_url($url, $format='txt') {
	global $bitlyUser;
	global $bitlyKey;
	global $bitlyApiUrl;
  $connectURL = 'http://api.bit.ly/v3/expand?login='.$bitlyUser.'&apiKey='.$bitlyKey.'&shortUrl='.urlencode($url).'&format='.$format;
	
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch,CURLOPT_URL,$connectURL);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	
  return $data;
}

/*
 *
 -------------------------------------------------------------
 * Version: 1.0
 * Date: June 19th, 2003
 * Purpose: Cut a string preserving any tag nesting and matching.
 * Author: Original Javascript Code: Benjamin Lupu <lupufr@aol.com>
 * Translation to PHP & Smarty: Edward Dale <scompt@scompt.com>
 *
 -------------------------------------------------------------
*/
function smartyTruncate($string, $length)
{
	if( !empty( $string ) && $length>0 ) {
		$isText = true;
		$ret = "";
		$i = 0;

		$currentChar = "";
		$lastSpacePosition = -1;
		$lastChar = "";

		$tagsArray = array();
		$currentTag = "";
		$tagLevel = 0;

		$noTagLength = strlen( strip_tags( $string ) );

		// Parser loop
		for( $j=0; $j<strlen( $string ); $j++ ) {

			$currentChar = substr( $string, $j, 1 );
			$ret .= $currentChar;

			// Lesser than event
			if( $currentChar == "<") $isText = false;

			// Character handler
			if( $isText ) {

				// Memorize last space position
				if( $currentChar == " " ) { $lastSpacePosition = $j; }
				else { $lastChar = $currentChar; }

				$i++;
			} else {
				$currentTag .= $currentChar;
			}

			// Greater than event
			if( $currentChar == ">" ) {
				$isText = true;

				// Opening tag handler
				if( ( strpos( $currentTag, "<" ) !== FALSE ) &&
						( strpos( $currentTag, "/>" ) === FALSE ) &&
						( strpos( $currentTag, "</") === FALSE ) ) {

					// Tag has attribute(s)
					if( strpos( $currentTag, " " ) !== FALSE ) {
						$currentTag = substr( $currentTag, 1, strpos( $currentTag, " " ) - 1 );
					} else {
						// Tag doesn't have attribute(s)
						$currentTag = substr( $currentTag, 1, -1 );
					}

					array_push( $tagsArray, $currentTag );

				} else if( strpos( $currentTag, "</" ) !== FALSE ) {

					array_pop( $tagsArray );
				}

				$currentTag = "";
			}

			if( $i >= $length) {
				break;
			}
		}

		// Cut HTML string at last space position
		if( $length < $noTagLength ) {
			if( $lastSpacePosition != -1 ) {
				$ret = substr( $string, 0, $lastSpacePosition );
			} else {
				$ret = substr( $string, $j );
			}
		}

		// Close broken XHTML elements
		while( sizeof( $tagsArray ) != 0 ) {
			$aTag = array_pop( $tagsArray );
			$ret .= "</" . $aTag . ">\n";
		}

	} else {
		$ret = "";
	}

	return( $ret );
}

/*
By Matt Mullenweg > http://photomatt.net
Inspired by Dan Benjamin > http://hiveware.com/imagerotator.php
Latest version always at:

http://photomatt.net/scripts/randomimage
*/
function rotateImageUrl($directory) {
	// Make this the relative path to the images, like "../img" or "random/images/".
	// If the images are in the same directory, leave it blank.
	$folder = $directory;
	
	// Space seperated list of extensions, you probably won't have to change this.
	$exts = 'jpg jpeg png gif';
	
	$files = array(); $i = -1; // Initialize some variables
	if ('' == $folder) $folder = './';
	
	$handle = opendir($folder);
	$exts = explode(' ', $exts);
	while (false !== ($file = readdir($handle))) {
		foreach($exts as $ext) { // for each extension check the extension
			if (preg_match('/\.'.$ext.'$/i', $file, $test)) { // faster than ereg, case insensitive
				$files[] = $file; // it's good
				++$i;
			}
		}
	}
	closedir($handle); // We're not using it anymore
	mt_srand((double)microtime()*1000000); // seed for PHP < 4.2
	$rand = mt_rand(0, $i); // $i was incremented as we went along
	
	return $files[$rand]; // Voila!
}

?>