<?php

include_once "ss-globals.php";
require_once(dirname(__FILE__).'/../wp-includes/class-simplepie.php');

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
  if (!$cid) { echo("ERROR: " . mysql_error() . "\n"); }
  mysql_select_db($dbName, $cid);

  $success = mysql_set_charset("utf8", $cid);
  if (!$success) { echo("ERROR: " . mysql_error() . "\n"); }

  return $cid;
}

// Given a handle to the DB, close the connection.
function ssDbClose($dbConnection) {
  mysql_close($dbConnection);
}

/*
 * Crawler
 */

// Input: Array with blog data, DB handle
// Action: Scans blog for new posts and adds them to the system.
function crawlBlogs($blog, $db) {
	$blogUri = $blog["syndicationuri"];
	$blogId = $blog["id"];
	$blogName = $blog["name"];

	$feed = getSimplePie($blogUri);
	$feed->set_cache_duration(0);
	$feed->init();
	if ($feed->error()) {
		$message .= "<div class=\"ss-div-2\"><span class=\"red-circle\"></span> ERROR: $blogUri (ID $blogId): " . $feed->error() . "</div>\n";
	}
	else {
		$message .= "<div class=\"ss-div-2\"><span class=\"green-circle\"></span> $blogName has been scanned for new posts.</div>";
	}
	
	foreach ($feed->get_items() as $item) {
		addSimplePieItem($item, $feed->get_language(), $blogId, $db);
		$item->__destruct(); // Do what PHP should be doing on its own.
		unset ($item);
	}
	markCrawled($blogId, $db);

	$feed->__destruct(); // Do what PHP should be doing on its own.
	unset($feed);
		
	return $message;
}

/*
 * Curl functions
 */

// Input: type of search; search parameters (hash array)
// Output: Curl for performing the search
function getSearchCurl ($type, $params) {
  global $searchUrl;
  $url = $searchUrl;
  $data = paramsToQuery($type, $params);

  $ch = curl_init();    // initialize curl handle
  curl_setopt($ch, CURLOPT_URL,$url); // set url to post to
  curl_setopt($ch, CURLOPT_FAILONERROR, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
  curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 4s
  curl_setopt($ch, CURLOPT_POST, 1); // set POST method
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  return $ch;
}

// Input: type of search; search parameters (hash array)
// Output: Curl for serializing matching feeds
function getSerializerCurl ($type, $ssParams, $httpParams) {
  global $serializerUrl;

  $url = $serializerUrl . paramArrayToString($httpParams);

  $data = paramsToQuery($type, $ssParams);

  $ch = curl_init();    // initialize curl handle
  curl_setopt($ch, CURLOPT_URL,$url); // set url to post to
  curl_setopt($ch, CURLOPT_FAILONERROR, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
  curl_setopt($ch, CURLOPT_TIMEOUT, 10); // times out after 10s
  curl_setopt($ch, CURLOPT_POST, 1); // set POST method
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

  return $ch;
}

// Input: URI of document to download
// Output: cUrl handle
function getDownloadCurl($uri) {
  $ch = curl_init();    // initialize curl handle
  curl_setopt($ch, CURLOPT_URL,$uri); // set url to post to
  curl_setopt($ch, CURLOPT_FAILONERROR, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
  curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 8s
  return $ch;
}

// Input: type of search, map of params (name => array)
// Return: string containing chunk of XML representing them
function paramsToQuery($type, $params) {
  $data = "<ssQuery><type>$type</type>";
  foreach ($params as $key => $value) {
    if (is_array($value)) {
      foreach ($value as $oneValue) {
        $data .= "<param><name>$key</name><value>$oneValue</value></param>";
      }
    } else {
      $data .= "<param><name>$key</name><value>$value</value></param>";
    }
  }
  $data .= "</ssQuery>";
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
    if (!$first) {
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
// Return: map of parameters (name -> array)
function parseHttpParams() {
  $i = 0;
  $params = array();
  $filter = stripslashes($_REQUEST["filter$i"]);
  while ($filter !== "" && $filter !== null) {
    $value = stripslashes($_REQUEST["value$i"]);
    if (! is_array($params[$filter])) {
      $params[$filter] = array();
    }
    array_push($params[$filter], $value);
    $i++;
    $filter = stripslashes($_REQUEST["filter$i"]);
  }

  return $params;

}

// Get current url
function getURL () {
	$pageURL = 'http';
	
	if ($_SERVER["HTTPS"] == "on") {
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

// Remove parameters from current url
function removeParams () {
	$url = getURL ();
	$parsed = parse_url($url);
	$baseUrl = $parsed["scheme"] . "://" . $parsed["host"] . $parsed["path"];
	return $baseUrl;
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
    $paramValue = stripslashes($paramValue);
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
 * DB query functions
 */

// Input: Arrange type, order, number of users, offset, DB handle
// Return: Users data
function getUsers ($arrange, $order, $pagesize, $offset, $db) {
	global $hashData;
	$column = $hashData["$arrange"];
	$direction = $hashData["$order"];
	
  $sql = "SELECT USER_ID, USER_NAME, USER_STATUS_ID, USER_PRIVILEGE_ID, EMAIL_ADDRESS FROM USER ORDER BY $column $direction LIMIT $pagesize OFFSET $offset";
  $users = array();
  $results = mysql_query($sql, $db);
  while ($row = mysql_fetch_array($results)) {
    $user["id"] = $row["USER_ID"];
    $user["name"] = $row["USER_NAME"];
		$user["status"] = $row["USER_STATUS_ID"];
    $user["privilege"] = $row["USER_PRIVILEGE_ID"];
    $user["email"] = $row["EMAIL_ADDRESS"];
    array_push($users, $user);
  }
  return $users;
}

// Input: Arrange type, order, number of posts, offset, DB handle
// Return: Posts data
function getPosts ($arrange, $order, $pagesize, $offset, $db) {
	global $hashData;
	$column = $hashData["$arrange"];
	$direction = $hashData["$order"];
	
	$sql = "SELECT * from BLOG_POST ORDER BY $column $direction LIMIT $pagesize OFFSET $offset";
	
	$posts = array();
	$results =  mysql_query($sql, $db);	
	while ($row = mysql_fetch_array($results)) {
  // Build post object to return
		$post["postId"] = $row["BLOG_POST_ID"];
		$post["blogId"] = $row["BLOG_ID"];
		$post["title"] = $row["BLOG_POST_TITLE"];
		$post["content"] = $row["BLOG_POST_SUMMARY"];
		$post["authorId"] = $row["BLOG_AUTHOR_ID"];
		$post["postDate"] = $row["BLOG_POST_DATE_TIME"];
		$post["addedDate"] = $row["BLOG_POST_INGEST_DATE_TIME"];
		$post["uri"] = $row["BLOG_POST_URI"];
		$post["language"] = $row["LANGUAGE_ID"];
		$post["status"] = $row["BLOG_POST_STATUS_ID"];
		$post["hasCitation"] = $row["BLOG_POST_HAS_CITATION"];
		array_push($posts, $post);
	}
	return $posts;
}

// Input: Username, DB handle
// Return: User ID or null, error message
function usernameToId ($username, $db) {

  $sql = "SELECT USER_ID FROM USER WHERE USER_NAME='$username'";
  $results = mysql_query($sql, $db);

  if (!$results) {
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
// Output: Number of approved blogs
function getBlogCount($db) {
	$sql = "SELECT COUNT(BLOG_ID) FROM BLOG WHERE BLOG_STATUS_ID = 0";
	$result = mysql_query($sql, $db);
	$count = mysql_result($result, 0);
	return $count; 
}

// Input: Post ID, type of count, DB handle
// Output: Number of recommendations or comments for this post
function getRecommendationsCount($postId, $type, $db) {
	$sql = "SELECT COUNT(PERSONA_ID) FROM RECOMMENDATION WHERE BLOG_POST_ID = $postId";
	if ($type == "comments") {
		$sql .= " AND REC_COMMENT != ''";
	}
	$result = mysql_query($sql, $db);
	$count = mysql_result($result, 0);
	return $count; 
}

// Input: DB handle
// Output: array of arrays; sub-arrays contain ["uri"] and ["id"]
function getSparseBlogs($db, $limit=1000) {
  // status 0 => active
  $sql = "SELECT BLOG_SYNDICATION_URI, BLOG_ID FROM BLOG WHERE BLOG_STATUS_ID=0 ORDER BY CRAWLED_DATE_TIME LIMIT $limit";
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
    $blog["blogdescription"] = $row["BLOG_DESCRIPTION"];
    $blog["uri"] = $row["BLOG_URI"];
    $blog["syndicationuri"] = $row["BLOG_SYNDICATION_URI"];
		$blog["status"] = $row["BLOG_STATUS_ID"];
		$blog["addedtime"] = $row["ADDED_DATE_TIME"];
		$blog["crawledtime"] = $row["CRAWLED_DATE_TIME"];
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

// Input: blog ID, DB handle
// Action: change status of blog to APPROVED (0)
function approveBlog($blogId, $db) {
  $sql = "UPDATE BLOG SET BLOG_STATUS_ID=0 WHERE BLOG_ID=$blogId";
  mysql_query($sql, $db);
}

// Input: blog ID, DB handle
// Action: change status of blog to REJECTED (2)
function rejectBlog($blogId, $db) {
  $sql = "UPDATE BLOG SET BLOG_STATUS_ID=2 WHERE BLOG_ID=$blogId";
  mysql_query($sql, $db);
}

// Input: blogID, DB handle
// Action: mark blog as recently crawled
function markCrawled ($blogId, $db) {
  $sql = "UPDATE BLOG SET CRAWLED_DATE_TIME=NOW() WHERE BLOG_ID=$blogId";
  mysql_query($sql, $db);
}

// Input: postID, personaID, DB handle
// Action: get post recommendation status for this user
function getRecommendationStatus ($postId, $personaId, $db) {
	$sql = "SELECT BLOG_POST_ID FROM RECOMMENDATION WHERE BLOG_POST_ID = $postId AND PERSONA_ID = $personaId";
	$results = mysql_query($sql, $db);
	
	if (mysql_fetch_array($results) != NULL) {
		return TRUE;
	}
	
	return FALSE;
}

// Input: DB handle
// Return: IDs (and optionally comment and related image) of posts recommended by editors
function getEditorsPicks($type, $db) {
	$sql = "SELECT rec.BLOG_POST_ID";
	if ($type == 'images') {
		$sql .= ", rec.REC_COMMENT, rec.REC_IMAGE";
	}
	$sql .= " FROM RECOMMENDATION rec,
PERSONA pers, USER user WHERE user.USER_ID = pers.USER_ID AND
rec.PERSONA_ID = pers.PERSONA_ID AND user.USER_PRIVILEGE_ID > 0";
	if ($type == 'images') {
		$sql .= " AND rec.REC_IMAGE != '' ORDER BY REC_DATE_TIME DESC LIMIT 4";
	}
	$results = mysql_query($sql, $db);
	
	$recommendations = array();
	while ($row = mysql_fetch_array($results)) {
		$recommendation["postId"] = $row["BLOG_POST_ID"];
		$recommendation["comment"] = $row["REC_COMMENT"];
		$recommendation["author"] = $row["USER_NAME"];
		$recommendation["image"] = $row["REC_IMAGE"];
    array_push ($recommendations, $recommendation);
  }
	
  return $recommendations;
}

// Input: Post ID, DB handle
// Action: Check if this post has been recommended by an editor.
function getEditorsPicksStatus($postId, $db) {
	$sql = "SELECT rec.PERSONA_ID FROM RECOMMENDATION rec, PERSONA pers, USER user WHERE rec.BLOG_POST_ID = $postId AND pers.PERSONA_ID =  rec.PERSONA_ID AND pers.USER_ID = user.USER_ID AND user.USER_PRIVILEGE_ID > 0";
	$results = mysql_query($sql, $db);
	
	$row = mysql_fetch_array($results);

  if($row["PERSONA_ID"]) {
		return TRUE;
	}
	
  return NULL;
}

// Input: blog ID, DB handle
// Return: array of email addresses of people associated with this blog
function getBlogContacts($blogId, $db) {
  $contacts = array();
  $sql = "select u.EMAIL_ADDRESS from BLOG_AUTHOR ba, PERSONA p, USER u where ba.BLOG_ID=$blogId and p.PERSONA_ID=ba.PERSONA_ID and u.USER_ID=p.USER_ID";
  $results = mysql_query($sql, $db);
  if ($results == null || mysql_num_rows($results) == 0) {
    return $contacts;
  }
  while ($row = mysql_fetch_array($results)) {
    array_push ($contacts, $row["EMAIL_ADDRESS"]);
  }
  return $contacts;
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
  $results =  mysql_query($sql, $db);

  if (mysql_error() != null) {
    print "ERROR: " . mysql_error() . "<br />";
  }

  return $results;
}

// Input: DB handle
// Return: user privilege list
function getUserPrivilegeList ($db) {
	$sql = "SELECT USER_PRIVILEGE_ID, USER_PRIVILEGE_DESCRIPTION FROM USER_PRIVILEGE ORDER BY USER_PRIVILEGE_DESCRIPTION";
  $results =  mysql_query($sql, $db);

  if (mysql_error() != null) {
    print "ERROR: " . mysql_error() . "<br />";
  }

  return $results;
}

// Input: DB handle
// Return: blog status list
function getBlogStatusList ($db) {
	$sql = "SELECT BLOG_STATUS_ID, BLOG_STATUS_DESCRIPTION FROM BLOG_STATUS ORDER BY BLOG_STATUS_DESCRIPTION";
  $results =  mysql_query($sql, $db);

  if (mysql_error() != null) {
    print "ERROR: " . mysql_error() . "<br />";
  }

  return $results;
}

// Input: DB handle
// Return: blog post status list
function getBlogPostStatusList ($db) {
	$sql = "SELECT BLOG_POST_STATUS_ID, BLOG_POST_STATUS_DESCRIPTION FROM BLOG_POST_STATUS ORDER BY BLOG_POST_STATUS_DESCRIPTION";
  $results =  mysql_query($sql, $db);

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

  //  return mysql_query($sql, $db);
  $results =  mysql_query($sql, $db);

  if (mysql_error() != null) {
    print "ERROR: " . mysql_error() . "<br />";
  }

  return $results;
}

// Input: user ID, DB handle
// Output: names of personas associated with this user, or null
function getPersonaNames ($userId, $db) {
  $sql = "select DISPLAY_NAME from PERSONA where USER_ID=$userId";
  $results = mysql_query($sql, $db);
  $personaNames = array();
  if ($results != null) {
    while ($row = mysql_fetch_array($results)) {
      array_push($personaNames, $row["DISPLAY_NAME"]);
    }
  }
  return $personaNames;
}

// Input: persona ID, DB handle
// Output: name of this persona ID
function getPersonaName ($personaId, $db) {
	$sql = "SELECT DISPLAY_NAME FROM PERSONA WHERE PERSONA_ID=$personaId";
  $result = mysql_query($sql, $db);
  
	$row = mysql_fetch_array($result);
  return $row["DISPLAY_NAME"];
}

// Input: user ID, DB handle
// Output: ids of blogs owned by this user
function getBlogIdsByUserId ($userId, $blogId, $db) {

  // PERSONA has USER_ID
  // BLOG_AUTHOR has PERSONA_ID
  // BLOG_AUTHOR has BLOG_ID and BLOG_AUTHOR_ACCOUNT_NAME

  $sql = "select ba.BLOG_ID, p.DISPLAY_NAME from PERSONA p, BLOG_AUTHOR ba, BLOG pa where p.USER_ID=$userId and ba.PERSONA_ID=p.PERSONA_ID and pa.BLOG_STATUS_ID=0 and pa.BLOG_ID=ba.BLOG_ID";
	if ($blogId != NULL) {
		$sql .= " and ba.BLOG_ID=$blogId";
	}
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

// Input: blog ID, DB handle
// Action: extract list of authors from DB; also, crawl blog URI for more authors to offer
// Return: map of author ID -> author name of authors associated with this blog
function getAuthorList ($blogId, $db) {

  $authorList;

  if ($blogId == null) {
    print "ERROR: please specify blog ID (getAuthorList)\n";
    return $authorList;
  }

  // List all author names/ids from DB
  $sql = "SELECT BLOG_AUTHOR_ID, BLOG_AUTHOR_ACCOUNT_NAME FROM BLOG_AUTHOR WHERE BLOG_ID=$blogId";
  $results = mysql_query($sql, $db);

  if ($results != null) {
    while ($row = mysql_fetch_array($results)) {
      $authorId = $row["BLOG_AUTHOR_ID"];
      $authorName = $row["BLOG_AUTHOR_ACCOUNT_NAME"];
      if ($authorName !== "Unknown") {
        $authorList[$authorId] = $authorName;
      }
    }
  }

  // List all author names/ids from feed
  $sql = "SELECT BLOG_SYNDICATION_URI, CRAWLED_DATE_TIME FROM BLOG WHERE BLOG_ID=$blogId";
  $results =  mysql_query($sql, $db);
  if ($results == null || mysql_num_rows($results) == 0) {
    // TODO error message to log
    // this should not have been empty
    return $authorList;
  }

  $row = mysql_fetch_array($results);
  $uri = $row["BLOG_SYNDICATION_URI"];

  $feed = getSimplePie($uri);
  foreach ($feed->get_items() as $item) {
    $author = $item->get_author();
    if ($author) {
      $authorName = $author->get_name();
      $authorId = addBlogAuthor($authorName, $blogId, $db);
      $authorList[$authorId] = $authorName;
    }
    unset($item);
  }

  unset ($feed);

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

// Input: Persona id, DB handle
// Return: user id to privilege id
function personaIdToPrivilegeId ($personaId, $db) {
	$sql = "SELECT a.USER_PRIVILEGE_ID FROM USER a, PERSONA b WHERE b.PERSONA_ID = $personaId AND b.USER_ID = a.USER_ID";
	$results = mysql_query($sql, $db);
	
	$privilegeId = mysql_fetch_array($results);

	return $privilegeId["USER_PRIVILEGE_ID"];
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

  $results =  mysql_query($sql, $db);

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

// Input: DB handle
// Return: array of blog IDs for all active blogs in the system
function getBlogIds ($db) {
  $sql = "SELECT BLOG_ID FROM BLOG WHERE BLOG_STATUS_ID=0";

  $results =  mysql_query($sql, $db);

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

// Input: array of topic IDs, DB handle
// Return: array of blog IDs, for blogs with primary/secondary topics corresponding to the specified IDs
function topicIdsToBlogIds ($topicIds, $db) {

  $firstTopic = array_shift ($topicIds);

  $sql = "SELECT b.BLOG_ID FROM PRIMARY_BLOG_TOPIC t1, BLOG b WHERE (t1.TOPIC_ID = $firstTopic";
  foreach ($topicIds as $topicId) {
    $sql .= " OR t1.TOPIC_ID = $topicId";
  }
  $sql .= ") AND b.BLOG_ID=t1.BLOG_ID and b.BLOG_STATUS_ID=0";

  $results =  mysql_query($sql, $db);

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

// Input: Citation, DB handle
// Output: Citation ID
function citationTextToCitationId ($citation, $db) {
  $sql = "SELECT CITATION_ID FROM CITATION WHERE CITATION_TEXT = '$citation'";
  $results = mysql_query($sql, $db);
	
  // Convert to array
	$row = mysql_fetch_array($results);
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
  	array_push($citations, $citation);
	}

  return $citations;
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

  $results =  mysql_query($sql, $db);

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
	
  $results =  mysql_query($sql, $db);

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
  $results =  mysql_query($sql, $db);
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

  $existing = getPost("postUri", $itemURI , $db);

  if ($existing) {
    return $existing["dbId"];
  }

  $blogAuthor = $item->get_author();
  $blogAuthorName = "Unknown";

  $authorList = getAuthorList ($blogId, $db);

  if (count ($authorList) == 1) {
    // if exactly one author, set default author name to it
    // (instead of "Unknown")
    foreach ($authorList as $oneAuthorId => $oneAuthorName) {
      $blogAuthorName = $oneAuthorName;
    }
  }

  if ($blogAuthor && strlen($blogAuthor->get_name()) > 0) {
    $blogAuthorName = $blogAuthor->get_name();
  }
  $blogAuthorId = addBlogAuthor($blogAuthorName, $blogId, $db);

  $timestamp = dateStringToSql($item->get_local_date());

  $languageId = "NULL";
  if ($language) {
    $languageId = languageToId($language, $db);
  }

  if ( is_null( $languageId  ) ) {
    $languageID = "NULL";
  }

  $summary = smartyTruncate($item->get_description(), 500);
  if (strlen ($summary) != strlen ($item->get_description())) {
    $summary .= " [...]";
  }

  $blogPostStatusId = 0; // active
  $sql = "INSERT INTO BLOG_POST (BLOG_ID, BLOG_AUTHOR_ID, LANGUAGE_ID, BLOG_POST_STATUS_ID, BLOG_POST_URI, BLOG_POST_DATE_TIME, BLOG_POST_INGEST_DATE_TIME, BLOG_POST_SUMMARY, BLOG_POST_TITLE) VALUES ($blogId, $blogAuthorId, $languageId, $blogPostStatusId, '". mysql_real_escape_string( $itemURI ) . "' , '" . $timestamp . "', NOW(), '" . mysql_real_escape_string($summary) . "' ,'" . mysql_real_escape_string($item->get_title()) . "')";
  mysql_query($sql, $db);

  // print "SQL: $sql\n";

  if (mysql_error()) {
    die ("addSimplePieItem: " . mysql_error() . " ($sql)\n");
  }
  $dbId = mysql_insert_id();

  $categories = $item->get_categories();
  if ($categories) {
    foreach ($categories as $category) {
      $tag = trim($category->get_label());
      if (strlen($tag) > 0) {
        $topicId = addTopic($tag, $db);
        linkTopicToPost($dbId, $topicId, 0, $db);
      }
    }
  }

  return $dbId;
}

// Input: uri to check
// Return: true if uri can be fetched and parsed, false otherwise
function uriFetchable ($uri) {
  $ch = curl_init();    // initialize curl handle
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
function linkTopicToPost($postId, $topicId, $source, $db) {
  $sql = "INSERT IGNORE INTO POST_TOPIC (BLOG_POST_ID, TOPIC_ID, TOPIC_SOURCE) VALUES ($postId, $topicId, $source)";
  mysql_query($sql, $db);
  if (mysql_error()) {
    die ("linkTopicToPost: " . mysql_error() . "\n");
  }
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

// Input: uri of post to search for, DB handle
// Return: corresponding post object, or null
function getPost ($arrange, $value, $db) {
	global $hashData;
	$column = $hashData["$arrange"];
	
  $sql = "SELECT * from BLOG_POST where $column = '$value'";
  $results =  mysql_query($sql, $db);
  if (! $results || mysql_num_rows($results) == 0) {
    return null;
  }
  $row = mysql_fetch_array($results);

  // Build post object to return
  $post["dbId"] = $row["BLOG_POST_ID"];
  $post["title"] = $row["BLOG_POST_TITLE"];
  $post["content"] = $row["BLOG_POST_SUMMARY"];
  $post["author"] = $row["BLOG_AUTHOR_ID"];
  $post["datetime"] = $row["BLOG_POST_DATE_TIME"];
  $post["uri"] = $row["BLOG_POST_URI"];
	$post["blogId"] = $row["BLOG_ID"];
  $post["language"] = $row["LANGUAGE_ID"];
	$post["hasCitation"] = $row["BLOG_POST_HAS_CITATION"];

  // build topic list
  $sql = "SELECT TOPIC_ID FROM TOPIC WHERE POST_ID=" . $post["dbId"];
  $results = mysql_query($sql, $db);
  if (!$results || mysql_num_rows($results)) {
    return $post;
  }

  $topics = array();
  $post["topics"] = $topics;

  while ($row = mysql_fetch_array($results)) {
    array_push($topics, $row["TOPIC_ID"]);
  }

  return $post;
}

// Input: Post ID, DB handle
// Result: Comments data
function getComments($postId, $db) {
	$sql = "SELECT PERSONA_ID, REC_DATE_TIME, REC_COMMENT FROM RECOMMENDATION WHERE BLOG_POST_ID=$postId AND REC_COMMENT != '' ORDER BY REC_DATE_TIME ASC";
	$results = mysql_query($sql, $db);
	
	$comments = array();
  while ($row = mysql_fetch_array($results)) {
    $comment["personaId"] = $row["PERSONA_ID"];
    $comment["date"] = $row["REC_DATE_TIME"];
		$comment["comment"] = $row["REC_COMMENT"];
    array_push($comments, $comment);
  }
	
	return $comments;
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
  $sql = "select p.USER_ID from PERSONA p, BLOG_AUTHOR ba where p.PERSONA_ID = ba.PERSONA_ID and ba.BLOG_ID=$blogId";

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

// Add a new Blog to the system.
// Input: Name of blog, URI of blog, syndication URI of blog, description of blog, primary topic #1, primary topic #2, DB handle
// Action: add blog to DB if it does not already exist
// Return: ID of (new or previously existing) blog
function addBlog($blogname, $bloguri, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $userId, $db) {

  global $siteadminemail;
  global $sitename;
  global $approveUrl;

  $retval;

  $bloguriFormats = alternateUris($bloguri);
  $blogsyndicationuriFormats = alternateUris($blogsyndicationuri);

  // check for duplicates by URI (URIs should be unique to each blog)
  $blogId = null;
  foreach ($bloguriFormats as $uri) {
    $blogId = blogUriToId($uri, $db);
    if ($blogId != null) {
      break;
    }
  }

  if ($blogId != null) {
    $retval["errormsg"] = "This blog is already in the system.";
    $retval["id"] = $blogId;
    return $retval;
  }

  // check for duplicates by syndication URI (syndication URIs should be unique to each blog)
  $blogId = null;
  foreach ($blogsyndicationuriFormats as $uri) {
    $blogId = blogSyndicationUriToId($uri, $db);
    if ($blogId != null) {
      break;
    }
  }

  if ($blogId != null) {
    $retval["errormsg"] = "The feed for this blog is already in the system.";
    $retval["id"] = $blogId;
    return $retval;
  }

  $userPriv = getUserPrivilegeStatus($userId, $db);
  $status = 1; // pending
  if ($userPriv > 0) { // moderator or admin
    $status = 0; // active
  }

  $blogname = mysql_real_escape_string($blogname);
  $blogdescription = mysql_real_escape_string($blogdescription);
  $sql = "INSERT INTO BLOG (BLOG_NAME, BLOG_STATUS_ID, BLOG_URI, BLOG_SYNDICATION_URI, BLOG_DESCRIPTION, ADDED_DATE_TIME) VALUES ('$blogname', $status, '$bloguri', '$blogsyndicationuri', '$blogdescription', CURDATE())";

  mysql_query($sql, $db);
  $blogId = mysql_insert_id();

  // Add topics if specified
  if ($topic1 != "-1") {
    associateTopic($topic1, $blogId, $db);
  }

  if ($topic2 != "-1") {
    associateTopic($topic2, $blogId, $db);
  }

# Send email to site admin with notification that a blog is waiting for approval
  $mailSent = mail ($siteadminemail, "[$sitename admin] Pending blog submission", "Pending blog submission at $approveUrl");

  if (! $mailSent) {
# TODO log this
  }

  $retval["id"] = $blogId;
  return $retval;
}

// Input: topic string, blog ID, DB handle
// Action: associate this topic with this blog ID
// TODO handle errors
function associateTopic($topic2, $blogId, $db) {
  $sql = "INSERT INTO PRIMARY_BLOG_TOPIC (TOPIC_ID, BLOG_ID) VALUES ($topic2, $blogId)";
  mysql_query($sql, $db);
}

// Input: blog ID, DB handle
// Output: list of topic IDs for this blog
function getBlogTopics($blogId, $db) {
  $sql = "SELECT TOPIC_ID FROM PRIMARY_BLOG_TOPIC WHERE BLOG_ID=$blogId";
  $results = mysql_query($sql, $db);
  $blogtopics = array();

  while ($row = mysql_fetch_array($results)) {
    array_push($blogtopics, $row["TOPIC_ID"]);
  }

  return $blogtopics;
}

// Input: blog ID, DB handle
// TODO handle errors
function removeTopics($blogId, $db) {
  $sql = "DELETE FROM PRIMARY_BLOG_TOPIC WHERE BLOG_ID=$blogId";
  mysql_query($sql, $db);
}

/*
 * Edit stuff
 */

function displayEditBlogsForm ($msg, $db) {
  $blogname = stripslashes($_REQUEST["blogname"]);
  $blogurl = stripslashes($_REQUEST["blogurl"]);
  $blogsyndicationuri = stripslashes($_REQUEST["blogsyndicationuri"]);
  $blogdescription = stripslashes($_REQUEST["blogdescription"]);

  global $current_user;
  get_currentuserinfo();
  $displayName = $current_user->user_login;

  print "Welcome, $displayName<br /><br />\n";

  if ($msg) {
    print "<p class='msg'>$msg</p><br />";
  }

  // If this is the first time this user has tried to interact with
  // the SS system, create a USER entry for them
  $userId = addUser($displayName, $email, $db);
	
  $blogIds = getBlogIdsByUserId($userId, $blogId, $db);
  if (sizeof($blogIds) == 0) {
    print "<p class='msg'>$displayName has no blogs.</p><br />";
    return;
  }

  $blogData = blogIdsToBlogData($blogIds, $db);

  while ($row = mysql_fetch_array($blogData)) {
    displayEditBlogForm($db, $row);
  }

  // Only active users can edit blogs
  $userStatus = getUserStatus($userId, $db);
  if ($userStatus != 0) {
    print "<p class=\"error\"><font color=\"red\">You cannot edit your blog as your account is not currently active. You may <a href='/contact-us/'>contact us</a> to ask for more information.</font></p>\n";
    return;
  }
}

function displayEditBlogForm($db, $data) {
  $blogId = $data["BLOG_ID"];
  $blogname = $data["BLOG_NAME"];
  $blogurl = $data["BLOG_URI"];
  $blogsyndicationuri = $data["BLOG_SYNDICATION_URI"];
  $blogdescription = $data["BLOG_DESCRIPTION"];
  $blogtopics = getBlogTopics($blogId, $db);
  //$topic1 = $_REQUEST["topic1"];
  //$topic2 = $_REQUEST["topic2"];

  print "<h2>Edit $blogname</h2>\n";

?> 
<form method="POST">
<input type="hidden" name="step" value="doEdit" />
<?php

   print "<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />\n";

   if ($errormsg !== null) {
     print "<p><font color='red'>Error: $errormsg</font></p>\n";
   }

  print "<p>*Required field</p>\n<p>\n";
  print "*Blog name: <input type=\"text\" name=\"blogname\" size=\"40\" value=\"$blogname\"/>\n</p>\n<p>\n*Blog URL: <input type=\"text\" name=\"blogurl\" size=\"40\" value=\"$blogurl\" /><br />(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)</p>";
  print "<p>*Blog syndication URL: <input type=\"text\" name=\"blogsyndicationuri\" size=\"40\" value=\"$blogsyndicationuri\" /> <br />(RSS or Atom feed. Must start with \"http://\", e.g., <em>http://feeds.feedburner.com/blogname/</em>.)</p>";
  print "<p>Blog description:<br /><textarea name=\"blogdescription\" rows=\"5\" cols=\"55\">$blogdescription</textarea></p>\n";

  print "<p>*Blog topic: <select name='topic1'>\n";
  print "<option value='-1'>None</option>\n";
  $topicList = getTopicList(true, $db);
  while ($row = mysql_fetch_array($topicList)) {
    print "<option value='" . $row["TOPIC_ID"] . "'";
    if ($row["TOPIC_ID"] == $blogtopics[0]) {
      print " selected";
    }
    print ">" . $row["TOPIC_NAME"] . "</option>\n";
  }
  print "</select>\n";

  print " <select name='topic2'>\n";
  print "<option value='-1'> None</option>\n";
  $topicList = getTopicList(true, $db);
  while ($row = mysql_fetch_array($topicList)) {
    print "<option value='" . $row["TOPIC_ID"] . "'";
    if ($row["TOPIC_ID"] == $blogtopics[1]) {
      print " selected";
    }
    print ">" . $row["TOPIC_NAME"] . "</option>\n";
  }
  print "</select></p>\n";
?>

<p>
<input class="ss-button" type="submit" value="Edit blog info" />
</p>
</form>
<p><hr /></p>
<?php

   }
   
function displayEditPendingBlogs ($db) {
	print "<form method=\"POST\">\n";
	print "<input type=\"hidden\" name=\"step\" value=\"edit\" />";
	$blogList = getPendingBlogs($db);
	foreach ($blogList as $blog) {
		$blogId = $blog["id"];
		$blogName = $blog["name"];
		$blogUri = $blog["uri"];
		$blogDescription = $blog["blogdescription"];
		$blogSyndicationUri = $blog["syndicationuri"];
		$blogtopics = getBlogTopics($blogId, $db);
		//$topic1 = $_REQUEST["topic1"];
		//$topic2 = $_REQUEST["topic2"];
		
		$contacts = getBlogContacts($blogId, $db);
		
		print "<input type=\"hidden\" name=\"blogId[]\" value=\"$blogId\" />\n";
		if ($errormsg !== null) {
			print "<p><font color='red'>Error: $errormsg</font></p>\n";
		}
		print "<p><span class=\"ss-bold\">$blogName</span></p>";
		if ($contacts) {
			print "<p>Author's Email";
			foreach ($contacts as $contact) {
				print " <a href=\"mailto:$contact\">$contact</a>";
			}
			print "</p>";
		}
		print "<p>*Required field</p>\n";
		print "<p>*Blog name: <input type=\"text\" name=\"blogname[]\" size=\"40\" value=\"$blogName\"/></p>\n";
		print "<p>*<a href=\"$blogUri\" style=\"none\" target=\"_blank\">Blog URL:</a> <input type=\"text\" name=\"blogurl[]\" size=\"40\" value=\"$blogUri\" /><br />(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)</p>";
		print "<p>*<a href=\"$blogSyndicationUri\" style=\"none\" target=\"_blank\">Blog syndication URL:</a> <input type=\"text\" name=\"blogsyndicationuri[]\" size=\"40\" value=\"$blogSyndicationUri\" /> <br />(RSS or Atom feed. Must start with \"http://\", e.g., <em>http://feeds.feedburner.com/blogname/</em>.)</p>";
		print "<p>Blog description:<br /><textarea name=\"blogdescription[]\" rows=\"5\" cols=\"55\">$blogDescription</textarea></p>\n";
		print "<p>*Blog topics: <select name='topic1[]'>\n";
		print "<option value='-1'>None</option>\n";
		$topicList = getTopicList(true, $db);
		while ($row = mysql_fetch_array($topicList)) {
			print "<option value='" . $row["TOPIC_ID"] . "'";
			if ($row["TOPIC_ID"] == $blogtopics[0]) {
				print " selected";
			}
			print ">" . $row["TOPIC_NAME"] . "</option>\n";
		}
		print "</select>&nbsp;<select name='topic2[]'>\n";
		print "<option value='-1'> None</option>\n";
		$topicList = getTopicList(true, $db);
		while ($row = mysql_fetch_array($topicList)) {
			print "<option value='" . $row["TOPIC_ID"] . "'";
			if ($row["TOPIC_ID"] == $blogtopics[1]) {
				print " selected";
            }
			print ">" . $row["TOPIC_NAME"] . "</option>\n";
		}
		print "</select></p>\n";
		print "<p><input type=\"radio\" name=\"$blogId-blog\" value=\"1\" /> Approve<br />";
		print "<input type=\"radio\" name=\"$blogId-blog\" value=\"2\" /> Reject<br /></p>";  
		}
	print "<input class=\"ss-button\" type=\"submit\" value=\"Submit\" />\n";
	print "</form>\n";
}

function doEditBlog ($db) {
  $blogId = stripslashes($_REQUEST["blogId"]);
  $blogName = stripslashes($_REQUEST["blogname"]);
  $blogUri = stripslashes($_REQUEST["blogurl"]);
  $blogSyndicationUri = stripslashes($_REQUEST["blogsyndicationuri"]);
  $blogDescription = stripslashes($_REQUEST["blogdescription"]);
  $topic1 = stripslashes($_REQUEST["topic1"]);
  $topic2 = stripslashes($_REQUEST["topic2"]);
  $userIsAuthor = stripslashes($_REQUEST["userIsAuthor"]);

  global $current_user;
  get_currentuserinfo();
  $displayName = $current_user->user_login;
  $email = $current_user->user_email;

  // If this is the first time this user has tried to interact with
  // the SS system, create a USER entry for them
  $userId = addUser($displayName, $email, $db);

  // If user is requesting a blogUri or blogsyndicationuri change, ensure that they own the new url
  $origBlogSyndicationUri = getBlogSyndicationUri($blogId, $db);
  $origBlogUri = getBlogUri($blogId, $db);

  // If blog URL or syndication URL have changed, we need to re-verify the claim to the blog (the author's ability to write to it)
  if ($origBlogSyndicationUri !== $blogSyndicationUri || $origBlogUri != $blogUri) {
    $claimToken = retrieveVerifiedClaimToken ($blogId, $userId, $db);
    clearClaimToken($blogId, $userId, $claimToken, $db);

    $claimToken = retrievePendingClaimToken ($blogId, $userId, $db);
    if ($claimToken == null) {
      $claimToken = generateClaimToken();
      storeClaimToken($claimToken, $blogId, $userId, $db);
    }

    $result = checkBlogData($blogId, $blogName, $origBlogUri, $origBlogSyndicationUri, $blogDescription, $topic1, $topic2, $userId, $displayname, $db);

    print "<p>To change the URL of your feed, you must re-claim your blog.</p>";
    displayBlogClaimToken($claimToken, $blogId, $displayName, $blogUri, $blogSyndicationUri, $db);
    return;
  }

  $result = checkBlogData($blogId, $blogName, $origBlogUri, $origBlogSyndicationUri, $blogDescription, $topic1, $topic2, $userId, $displayname, $db);

  if ($result == NULL) {
		editBlog ($blogId, $blogName, $blogUri, $blogSyndicationUri, $blogDescription, $topic1, $topic2, $db);
    displayEditBlogsForm("$blogName was updated.", $db);
    return;
  } else {
    displayEditBlogsForm("<p>ERROR: <ul class=\"ss-error\">$result</ul></p>", $db);
  }
}

function doVerifyEditClaim ($db) {
  $blogId = stripslashes($_REQUEST["blogId"]);
  $blogUri = stripslashes($_REQUEST["blogUri"]);
  $blogSyndicationUri = stripslashes($_REQUEST["blogSyndicationUri"]);
  $blogName = getBlogName($blogId, $db);

  global $current_user;
  get_currentuserinfo();
  $displayName = $current_user->user_login;
  $userId = getUser($displayName, $db);
  $result = verifyClaim($blogId, $userId, $blogUri, $blogSyndicationUri, $db);

  if ($result === "no-claim") {
      doEditBlog($db);
      return;
  } else if ($result == "verified") {
    $claimToken = getClaimToken($blogId, $userId, $db);
    $success = markClaimTokenVerified($blogId, $userId, $claimToken, $db);
    if (! $success) {
      print "Error, failed to update db";
      return;
    }

    $blogDescription = getBlogDescription($blogId, $db);
    $blogTopics = getBlogTopics($blogId, $db);
    $topic1 = null; $topic2 = null;
    if (sizeof($blogTopics) > 0) {
      $topic1 = $blogTopics[0];
    }
    if (sizeof ($blogTopcs) > 1) {
      $topic2 = $blogTopics[1];
    }

    $result = editBlog($blogId, $blogName, $blogUri, $blogSyndicationUri, $blogDescription, $topic1, $topic2, $userId, $blogDelete, $displayName, $db);

    displayEditBlogsForm("Blog $blogName edited.", $db);
    return;
  } else {
    $claimToken = getClaimToken($blogId, $userId, $db);
    print "<p>Your claim token ($claimToken) was not found on your blog and/or your syndication feed.</p>\n";
    displayBlogClaimToken($claimToken, $blogId, $displayName, $blogUri, $blogSyndicationUri, $db);
  }
}


// Input: blog ID, blog name, blog URI, blog syndication URI, blog description, first main topic, other main topic, user ID, user display name, DB handle
// Action: check blog metadata
// Return: error message or null
function checkBlogData($blogId, $blogname, $blogurl, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $userId, $displayname, $db) {

  // get old info about this blog
  $results = blogIdsToBlogData(array(0 => $blogId), $db);
  $oldBlogData = mysql_fetch_array($results);
  $oldBlogname = $oldBlogData["BLOG_NAME"];

  // if not logged in as an author or as admin, fail
  if (! canEdit($userId, $blogId, $db)) {
    $result .= "<li>$displayname does not have editing privileges for blog $oldBlogname.</li>";
  }

  // user exists? active (0)?
  $userStatus = getUserStatus($userId, $db);
  if ($userStatus == null) {
    $result .= "<li>No such user $displayname.</li>";
  }
  if ($userStatus != 0) {
    $result .= "<li>User $displayname is not active; could not update blog info.</li>";
  }

  // blog exists? need blog id!
  $blogStatus = getBlogStatusId($blogId, $db);
  if ($blogStatus == null) {
    $result .= "<li>No such blog $blogId.</li>";
  }
	
	$userPriv = getUserPrivilegeStatus($userId, $db);
  if ($blogStatus != 0) {
    if ($userPriv == 0) { // not moderator or admin
      $result .= "<li>The entry for blog $oldBlogname (ID $blogId) is not currently active, so it cannot be updated.</li>";
    }
  }
  
  // check that there is a name
  if ($blogname == null) {
	  $result .= "<li>You need to submit a name for the blog.</li>";
	  
  }

  // check that blog URL is fetchable
  if (! uriFetchable($blogurl)) {
    $result .= ("<li>Unable to fetch the contents of your blog at $blogurl. Did you remember to put \"http://\" before the URL when you entered it? If you did, make sure your blog page is actually working, or <a href='/contact-us/'>contact us</a> to ask for help in resolving this problem.</li>");
  }

  // check that syndication feed is parseable
  $feed = getSimplePie($blogsyndicationuri);
  if ($feed->get_type() == 0) {
    $result .= ("<li>Unable to parse feed at $blogsyndicationuri. Are you sure it is Atom or RSS?</li>");
  }
  
  // check that blog URL and blog syndication URL are not the same
  if ($blogurl == $blogsyndicationuri) {
  	$result .= ("<li>The blog URL (homepage) and the blog syndication URL (RSS or Atom feed) need to be different.</li>");
  }
  
  // Check that the user has selected at least one topic
  if ($topic1 == -1 && $topic2 == -1) {
  	$result .= ("<li>You need to choose at least one topic.</li>");
  }

  // escape stuff
  $blogname = mysql_real_escape_string($blogname);
  $blogdescription = mysql_real_escape_string($blogdescription);
  // TODO probably should be escaping the URIs as well

  return $result;
}

// Input: blog ID, blog name, blog URI, blog syndication URI, blog description, first main topic, other main topic, DB handle
// Action: edit blog metadata
function editBlog ($blogId, $blogname, $blogurl, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $db) {
	
		// update easy data
		$sql = "UPDATE BLOG SET BLOG_NAME='$blogname', BLOG_URI='$blogurl', BLOG_SYNDICATION_URI='$blogsyndicationuri', BLOG_DESCRIPTION='$blogdescription' WHERE BLOG_ID=$blogId";
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

// Input: post ID, post title, post summary, post URL, user ID, user display name, DB handle
// Action: check post metadata
// Return: error message or null
function checkPostData($postId, $postTitle, $postSummary, $postUrl, $userId, $displayname, $db) {

  // if not logged in as an author or as admin, fail
  if (! canEdit($userId, $blogId, $db)) {
    $result .= "<li>$displayname does not have editing privileges to administer posts.</li>";
  }

  // user exists? active (0)?
  $userStatus = getUserStatus($userId, $db);
  if ($userStatus == null) {
    $result .= "<li>No such user $displayname.</li>";
  }
  if ($userStatus != 0) {
    $result .= "<li>User $displayname is not active; could not update blog info.</li>";
  }

  // blog exists? need blog id!
  $postStatus = getPostStatusId($postId, $db);
  if ($postStatus == null) {
    $result .= "<li>No such post $postId.</li>";
  }
  
  // check that there is a name
  if ($postTitle == null) {
	  $result .= "<li>You need to submit a title for this post.</li>";
  }

  // check that blog URL is fetchable
  if (! uriFetchable($postUrl)) {
    $result .= ("<li>Unable to fetch the contents of this post at $postUrl. Did you remember to put \"http://\" before the URL when you entered it? If you did, make sure your blog page is actually working, or <a href='/contact-us/'>contact us</a> to ask for help in resolving this problem.</li>");
  }

  // escape stuff
  $postTitle = mysql_real_escape_string($postTitle);
  $postSummary = mysql_real_escape_string($postSummary);
  // TODO probably should be escaping the URIs as well

  return $result;
}

// Input: blog ID, blog name, blog URI, blog syndication URI, blog description, first main topic, other main topic, DB handle
// Action: edit blog metadata
function editPost ($postId, $postTitle, $postUrl, $postSummary, $postStatus, $userId, $displayName, $db) {

	$sql = "UPDATE BLOG_POST SET BLOG_POST_TITLE='$postTitle', BLOG_POST_URI='$postUrl', BLOG_POST_SUMMARY='$postSummary', BLOG_POST_STATUS_ID=$postStatus WHERE BLOG_POST_ID=$postId";
	mysql_query($sql, $db);

}

// Input: user ID, user name, user status, user privilege status, user email, administrator id, administrator privilege, administrator display name, WordPress DB handle, DB handle
// Action: check user metadata
// Return: error message or null
function checkUserData($userID, $userName, $userStatus, $userEmail, $userPrivilege, $userId, $userPriv, $displayname, $db) {

  // if not logged in as an author or as admin, fail
  if ($userPriv < 2) {
    $result .= "<li>$displayname does not have editing privileges to administrate users.</li>";
  }

  // user exists? active (0)?
  $checkUserStatus = getUserStatus($userId, $db);
  if ($checkUserStatus == null) {
    $result .= "<li>No such user $displayname.</li>";
  }
  if ($checkUserStatus != 0) {
    $result .= "<li>User $displayname is not active; could not update user info.</li>";
  }
  
  // check that there is a name
  if ($userName == null) {
	  $result .= "<li>You need to submit a name for the user.</li>";	  
  }
	
	// check if the email is valid
	if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
		$result .= "<li>The submited e-mail is not valid.</li>";
	}
  
  // Check that there is a user status
  if ($userStatus == null) {
		$result .= ("<li>You need to submit a user status.</li>");
  }
	
	// Check that there is a user privilige
  if ($userPrivilege == null) {
		$result .= ("<li>You need to submit a user privilege status.</li>");
  }
	
  return $result;
}

// Input: user ID, user name, user status, user privilege status, user email, old user name, WordPress DB handle, DB handle
// Action: edit user metadata
function editUser ($userID, $userName, $userStatus, $userEmail, $userPrivilege, $oldUserName, $wpdb, $db) {
	// update Wordpress name
	$wpdb->update( $wpdb->users, array('user_login' => $userName, 'user_nicename' => $userName, 'display_name' => $userName), array('user_login' => $oldUserName) );
	
	// update easy data
  $sql = "UPDATE USER SET USER_NAME='$userName', USER_PRIVILEGE_ID='$userPrivilege', USER_STATUS_ID='$userStatus', EMAIL_ADDRESS='$userEmail' WHERE USER_ID=$userID";
  mysql_query($sql, $db);
}

// Input: blog status, DB handle
// Action: edit blog status
function editBlogStatus ($blogId, $blogStatus, $db) {
  $sql = "UPDATE BLOG SET BLOG_STATUS_ID='$blogStatus' WHERE BLOG_ID=$blogId";
  mysql_query($sql, $db);
	
	if (mysql_error() != null) {
    print "ERROR: " . mysql_error() . "<br />";
    return;
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
  return (in_array ($userId, $authorIds));
}

// Input: blog ID, DB handle
// Return: name of this blog, or null
function getBlogName($blogId, $db) {
  $sql = "SELECT BLOG_NAME FROM BLOG WHERE BLOG_ID=$blogId";
  $results = mysql_query($sql, $db);
  if ($results == null || mysql_num_rows($results) == 0) {
    return null;
  }
  $row = mysql_fetch_array($results);
  return $row["BLOG_NAME"];
}

// Input: user ID, DB handle
// Return: name of this user, or null
function getUserName($userId, $db) {
  $sql = "SELECT USER_NAME FROM USER WHERE USER_ID=$userId";
  $results = mysql_query($sql, $db);
  if ($results == null || mysql_num_rows($results) == 0) {
    return null;
  }
  $row = mysql_fetch_array($results);
  return $row["USER_NAME"];
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

// Input: post ID, persona ID, DB handle
// Return: get recommendation related image from this post and user
function getPostImage($postId, $personaId, $db) {
	$sql = "SELECT REC_IMAGE FROM RECOMMENDATION WHERE BLOG_POST_ID = $postId";
	$results = mysql_query($sql, $db);
	
	$row = mysql_fetch_array($results);
  return $row["REC_IMAGE"];
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
function addUser($username, $email, $db) {
  $userId = getUser ($username, $db);
  if ($userId != null) {
    return $userId;
  }

  $privilege = 0; // "user"
  $status = 0; // "active"
  $sql = "INSERT INTO USER (USER_NAME, USER_PRIVILEGE_ID, USER_STATUS_ID, EMAIL_ADDRESS) VALUES ('$username', $privilege, $status, '$email')";
  mysql_query($sql, $db);
  return mysql_insert_id();
}

// Input: Username, DB handle
// Return: ID of corresponding user, or null
function getUser($username, $db) {
  $sql = "SELECT USER_ID FROM USER WHERE USER_NAME='$username'";
  $results = mysql_query($sql, $db);
  if ($results == null || mysql_num_rows($results) == 0) {
    return null;
  }
  $row = mysql_fetch_array($results);
  return $row["USER_ID"];
}

// Input: user ID, display name, DB handle
// Action: create new Persona for this User with the given name
// Return: ID of (new or previously existing) Persona
function addPersona($userId, $displayName, $db) {
  $personaId = getPersona($userId, $displayName, $db);
  if ($personaId != null) {
    return $personaId;
  }

  $sql = "INSERT INTO PERSONA (USER_ID, DISPLAY_NAME) VALUES ($userId, '" . mysql_real_escape_string($displayName) . "')";
  mysql_query($sql, $db);
  return mysql_insert_id();
}

// Input: user ID, display name, DB handle
// Return: return matching Persona, or null
function getPersona($userId, $displayName, $db) {
  $sql = "SELECT PERSONA_ID FROM PERSONA WHERE USER_ID=$userId AND DISPLAY_NAME='" . mysql_real_escape_string($displayName) . "'";

  $results = mysql_query($sql, $db);
  if ($results == null || mysql_num_rows($results) == 0) {
    return null;
  }
  $row = mysql_fetch_array($results);
  return $row["PERSONA_ID"];
}

// Input: author ID, DB handle
// Return: boolean representing whether or not this author ID is "claimed" (attached to a user via a persona)
function isAuthorClaimed($authorId, $db) {
  // TODO perhaps be smarter about this -- is the associated user active?
  $sql = "SELECT PERSONA_ID FROM BLOG_AUTHOR WHERE BLOG_AUTHOR_ID=$authorId";
  $results = mysql_query($sql, $db);
  if ($results == null || mysql_num_rows($results) == 0) {
    return false;
  }
  $row = mysql_fetch_array($results);
  $personaId = $row['PERSONA_ID'];
  return ($personaId != null);
}


// Input: blog ID, DB handle
// Return: boolean representing whether or not this blog can be "claimed"
function isBlogClaimable($blogId, $db) {
  $sql = "select BLOG_AUTHOR_ID from BLOG_AUTHOR where BLOG_ID=$blogId and PERSONA_ID IS NULL";
  $results = mysql_query($sql, $db);
  if ($results == null || mysql_num_rows($results) == 0) {
    return false;
  }
  return true;
}

// Input: author ID, persona ID, DB handle
// Action: link this Author and this Persona
function linkAuthorToPersona($authorId, $personaId, $db) {
  // set blog_author_account_name = persona display_name
  $sql = "UPDATE BLOG_AUTHOR ba, PERSONA p SET ba.PERSONA_ID=$personaId, ba.BLOG_AUTHOR_ACCOUNT_NAME=p.DISPLAY_NAME WHERE ba.BLOG_AUTHOR_ID=$authorId AND p.PERSONA_ID = $personaId";
  mysql_query($sql, $db);
}

// Get SimplePie object, with appropriate cache location set
// SimplePie is for parsing syndication feeds
function getSimplePie($uri) {
  global $cachedir;

  $feed = new SimplePie($uri, $cachedir);
  return $feed;
}

/* CitationSeeker */

// Input: ID of post, db handle
// Return: true if post has BLOG_POST_HAS_CITATION=1, false otherwise
function citedPost ($postId, $db) {
  $sql = "SELECT * FROM BLOG_POST WHERE BLOG_POST_HAS_CITATION=1 AND BLOG_POST_ID=$postId";
  $results = mysql_query($sql, $db);
  return (mysql_num_rows($results) != 0);
}

function retrieveCrossRefMetadata($uri) {
  $ch = curl_init();    // initialize curl handle
  curl_setopt($ch, CURLOPT_URL,$uri); // set url to post to
  curl_setopt($ch, CURLOPT_FAILONERROR, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
  curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 8s

# Send query to CrossRef Metadata Search
  $result = curl_exec($ch);
  $cerror = curl_error($ch);
if (($cerror != null & strlen($cerror) > 0)) {
  print "ERROR: $cerror\n";
 }

 return $result;
}

# Input: string which contains all or part of article title (user-supplied)
# Output: list of strings, each containing COinS-formatted citation which might match the supplied string
function titleToCitations($title, $metadata2coins) {
	global $crossRefUrl;
	
  $uri = $crossRefUrl . urlencode($title);

  $ch = curl_init();    // initialize curl handle
  curl_setopt($ch, CURLOPT_URL,$uri); // set url to post to
  curl_setopt($ch, CURLOPT_FAILONERROR, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
  curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 8s

	# Send query to CrossRef Metadata Search
  $result = curl_exec($ch);
  $cerror = curl_error($ch);
	if (($cerror != null & strlen($cerror) > 0)) {
		return "ERROR: $cerror\n";
	}
	
	$resultLinks = array();
	// extract the links to the metadata for matching articles
	foreach (explode ("\n", $result) as $line) {
		if (preg_match("/\[xml\]/", $line)) {
			$uri;
			if (preg_match("/<a href='(.*)'/", $line, $matches)) {
				array_push($resultLinks, $matches[1]);
			}
		}
	}

	$citations = array();
	foreach ($resultLinks as $uri) {
		$metadataXml = retrieveCrossRefMetadata ($uri);
		$metadataCoins = transformXmlString($metadataXml, $metadata2coins);
		array_push($citations, $metadataCoins);
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
	$xml = simplexml_import_dom($doc);
	
	// Search for Z3988 class and return all its contents
	$getCitations = $xml->xpath('//*[@class=\'Z3988\']');
	
	// Parse citation
	foreach ($getCitations as $data) {
		$values = urldecode((string)$data->attributes()->title);
		preg_match("/(?<=bpr3.included=)./", $values, $rbInclude);
		preg_match("/(?<=ss.included=)./", $values, $ssInclude);
		if (($rbInclude[0] == 1 && $ssInclude[0] == NULL) || ($ssInclude[0] == 1)) {
			storeTopics ($postId, $values, $db);
			$citation[] = $data -> asXML();
		}
	}
	
	return $citation;
}

// Input: Post ID, Topics Data, DB handle
// Action: Store topics from the citation
function storeTopics ($postId, $topicsData, $db) {
	preg_match("/(?<=bpr3.tags=)[^&;]+/", $topicsData, $topics);
	$topic = preg_split("/[,\/]/", $topics[0]);
	foreach ($topic as $category) {
		$tag = trim($category);
		$topicId = addTopic($tag, $db);
		linkTopicToPost($postId, $topicId, 2, $db);
	}
}

// Input: Citation, Post ID, DB handle
// Action: Store citation
function storeCitation ($citation, $postId, $db) {
	
	$citation = mysql_escape_string(utf8_encode($citation));
	
	// Post has citation
	$markCitation = "UPDATE BLOG_POST SET BLOG_POST_HAS_CITATION=1 WHERE BLOG_POST_ID=$postId";
	mysql_query($markCitation, $db);
	
	// Check that the citation isn't already stored
	$citationId = citationTextToCitationId ($citation, $db);
	if ($citationId == NULL) {
		// Insert Citation
		$insertCitation = "INSERT IGNORE INTO CITATION (CITATION_TEXT) VALUES ('$citation')";
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

// Input: citation, DB handle.
// Output: Parsed citations. 
function parseCitation ($citation, $db) {
	$dom = new DOMDocument();
	@$dom->loadHTML($citation);
	$xml = simplexml_import_dom($dom);
	$xpath = $xml->xpath("//span[@class='Z3988']");
	$title = $xpath[0]->attributes()->title;
	
	// Split all the different information
	$result = preg_split("/&/", $title);
	$i = 0;
	foreach ($result as $value) {
		// Split title and value
		$elements = preg_split("/=/", $value, 2);
		$attribute = $elements[0];
		// If there is more than one author, add to array
		if (($attribute == "rft.au" || $attribute == "rft.aufirst" || $attribute == "rft.aulast") && $i != 10) {
			$authors[$i][$attribute] = urldecode($elements[1]);
			if ($attribute == "rft.aufirst") {
				$i++;
			}
		}
		else {
			$values[$attribute] .= urldecode($elements[1]);
		}
	}
	$values["authors"] = $authors;
	// Get ID and ID type (DOI, PMID, arXiv...)
	preg_match_all("/(?<=info:)[^\/]+|(?<=\/).+/", $values["rft_id"], $id);
	$values["id_type"] = $id[0][0];
	$values["id"] = $id[0][1];

	return $values;
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
  return uniqid("sciseekclaimtoken-");
}

function doClaimBlog($blogId, $displayName, $email, $db) {

  $userId = getUser($displayName, $db);

  // If there is already a pending request, let them choose to verify that instead
  $claimToken = retrievePendingClaimToken($blogId, $userId, $db);

  // If there was no pending request, create one
  if ($claimToken == null) {
    $claimToken = generateClaimToken();
    storeClaimToken($claimToken, $blogId, $userId, $db);
  }

  displayBlogClaimToken($claimToken, $blogId, $displayName, null, null, $db);
}

function doVerifyClaim($blogId, $displayName, $db) {
  $userId = getUser($displayName, $db);
  $result = verifyClaim($blogId, $userId, getBlogUri($blogId, $db), getBlogSyndicationUri($blogId, $db), $db);

  if ($result === "no-claim") {
      doClaimBlog($blogId, $displayName, $email, $db);
      return;
  } else if ($result == "verified") {
    $claimToken = getClaimToken($blogId, $userId, $db);
    $success = markClaimTokenVerified($blogId, $userId, $claimToken, $db);
    if (! $success) {
      print "Error, failed to update db";
      return;
    }
    displayUserAuthorLinkForm($blogId, $userId, $displayName, $db);

  } else {
    $claimToken = getClaimToken($blogId, $userId, $db);
    print "<p>Your claim token ($claimToken) was not found on your blog, in a post or in a meta tag.</p>\n";
    displayBlogClaimToken($claimToken, $blogId, $displayName, null, null, $db);
  }
}

// Input: blog ID, user ID, new blog URL, DB handle
// Return: true if the specified blog contains the claim token specified in the CLAIM_BLOG table (select by user ID and blog ID)
function verifyClaim($blogId, $userId, $blogUri, $blogSyndicationUri, $db) {

  $claimToken = retrievePendingClaimToken ($blogId, $userId, $db);
  if ($claimToken == null) {
    return "no-claim";
  }

  // Verify that the token exists on the actual page
  // (this could be insecure if a blog displays recent comments on its main page, or if the URL given points to a specific post which displays comments)
  $ch = curl_init();    // initialize curl handle
  curl_setopt($ch, CURLOPT_URL, $blogUri); // set url to post to
  curl_setopt($ch, CURLOPT_FAILONERROR, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
  curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 4s
  $result = curl_exec($ch);
  $cerror = curl_error($ch);

  // Error fetching page -> unverified
  if (($cerror != null & strlen($cerror) > 0) || strlen($result) == 0) {
    return "unverified";
  }

  // Token not on page -> unverified
  if (strpos($result, $claimToken) == FALSE) {
    return "unverified";
  }

  // Verify that the token exists in the syndication feed
  $feed = getSimplePie($blogSyndicationUri);
  $feed->set_cache_duration(0);
  $feed->init();

  foreach ($feed->get_items() as $item) {
    $blogContent = $item->get_content();
    $pos = strpos($blogContent, $claimToken);
    if (strcmp("", $pos) != 0 && $pos >= 0) {
      return "verified";
    }
  }

  // DELETEME
  // We no longer check meta tags; annoying to ask user to do both, and not secure to rely entirely on meta tags (must check the syndication feed!)

  // // If it wasn't in feed content, also check meta tags
  // $blogUri = getBlogUri($blogId, $db);
  // $metas = get_meta_tags($blogUri);
  // foreach ($metas as $meta) {
  //   if ($meta === $claimToken) {
  //     return "verified";
  //   }
  // }

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
  $sql = "SELECT CLAIM_TOKEN FROM CLAIM_BLOG WHERE USER_ID=$userId and BLOG_ID=$blogId AND CLAIM_STATUS_ID=1";

  $results = mysql_query($sql, $db);
  if ($results == null || mysql_num_rows($results) == 0) {
    return null;
  }

  $row = mysql_fetch_array($results);
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


/* Template/display functions */

// Input: unique blog token, blog ID, user ID, DB handle
// Action: store blog token, blog ID, and user ID in the CLAIM_BLOG table
// Returns: null on success, error message on error
function storeClaimToken($claimToken, $blogId, $userId, $db) {

  // Is there already a pending claim token? If so, set it to "overridden" (2)
  $sql = "UPDATE CLAIM_BLOG SET CLAIM_STATUS_ID=2 WHERE BLOG_ID=$blogId AND USER_ID=$userId AND CLAIM_STATUS_ID=0";
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
function displayBlogClaimToken($claimToken, $blogId, $displayName, $blogUri, $blogSyndicationUri, $db) {
  $blogName = getBlogName($blogId, $db);

  if ($blogUri == null) {
    $blogUri = getBlogUri($blogId, $db);
  }

  if ($blogSyndicationUri == null) {
    $blogSyndicationUri = getBlogSyndicationUri($blogId, $db);
  }

  print "<p>To claim this blog ($blogName), we request that you demonstrate that you are able to write to it. To do this, please post the below automatically generated token on your blog. You can do this in a blog post, which you may delete after the claim process is complete. You may simply add the token at any point in a post. Alternately, we provide some sample HTML code which will make the token invisible to your blog readers.</p>\n";
  print "<p><b>Claim token:</b> $claimToken</p>\n";
  print "<p><b>Sample HTML to include:</b> &lt;p&gt;&lt;span style=\"display:none\"&gt;$claimToken&lt;/span&gt;&lt;/p&gt;\n"; 
  print "<p>Once the token is displayed on your blog, in a post or in a meta tag in the header, <a href='javaScript:document.doVerifyForm.submit()'>continue to the next step.</a>";
  print "<form method='POST' name='doVerifyForm'>\n<input type='hidden' name='step' value='verify' />\n";
  print "<input type='hidden' name='blogId' value='$blogId' />\n";
  print "<input type='hidden' name='blogUri' value='$blogUri' />\n";
  print "<input type='hidden' name='blogSyndicationUri' value='$blogSyndicationUri' />\n";
  print "</form>";
}


function displayUserAuthorLinkForm($blogId, $userId, $displayName, $db) {
  global $sitename;

  if ($blogId == null) {
    print "ERROR: please specify blog ID (displayUserAuthorLinkForm)\n";
    return;
  }

  $blogName = getBlogName($blogId, $db);

  $authorList = getAuthorList ($blogId, $db);
  $unknownAuthorId = getUnknownAuthorId($blogId, $db);

  // Only active users can claim blogs
  $userStatus = getUserStatus($userId, $db);
  if ($userStatus != 0) {
    print "<p class=\"error\"><font color=\"red\">You cannot claim this blog as your account is not currently active ($userStatus). You may <a href='/contact-us/'>contact us</a> to ask for more information.</font></p>\n";
    return;
  }

  if (count($authorList) == 0 && $unknownAuthorId == null) {
    print "<p><font color=\"red\">There was an error in parsing the feed of this blog. Please <a href='/contact-us/'>contact us</a> to ask for help in resolving this problem.</font></p>\n";
    return;
  }

  $blogStatus = getBlogStatusId($blogId, $db);
  if ($blogStatus == 2) { // rejected
    print "<p class=\"error\"><font color='red'>This blog has been rejected from the system by an editor. For more information, please <a href='/contact-us/'>contact us</a>.</p>";
    return;
  }

  if (isBlogClaimable($blogId, $db) == true) {
    print "<h2>Identify yourself as an author of $blogName</h2>\n";

    print "<form method=\"POST\">\n";
    print "<input type=\"hidden\" name=\"step\" value=\"userAuthorLinkForm\" />\n";
    print "<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />\n";
    print "<input type=\"hidden\" name=\"userId\" value=\"$userId\" />\n";

    $authorList = getAuthorList($blogId, $db);

    if (count($authorList) > 0) {
      print "<p>This blog seems to have the following author(s). Please indicate which one is you (OK to choose more than one).</p>\n";
      $firstAuthor = null;
      foreach ($authorList as $authorId => $authorName) {
        $claimed = isAuthorClaimed($authorId, $db);
        if ($firstAuthor == null && (! $claimed)) {
          $firstAuthor = $authorName;
        }
        if ($claimed) {
          print "<input type=\"checkbox\" name=\"author$authorId\" disabled=\"true\"/> $authorName (claimed)<br />\n";
        } else {
          print "<input type=\"checkbox\" name=\"author$authorId\" /> $authorName<br />\n";
        }
      }
    }

    $personaNames = getPersonaNames($userId, $db);

    $personaName = $personaNames[0];

    // TODO provide list, allow user to select one

    if ($personaName == null) {
      $personaName = $firstAuthor;
    }
    if ($personaName == null) {
      $personaName = $displayName;
    }

  print "<p>Please enter the name by which you would like to be known, for example when you post comments on $sitename: <input type=\"text\" name=\"personaName\" value=\"$personaName\" size=\"20\" /></p>\n";

  print "<input type=\"submit\" value=\"Submit\">";
  print "</form>\n";

  } else {
    print "<p><font color='red'>This blog has already been claimed. If you feel this is in error, please <a href='/contact-us/'>contact us</a>.</p>";
  }

  print "<p><hr /></p>\n";

}

function doLinkUserAndAuthor($displayName, $db) {
  global $sitename;
  $success = false;

  $userId = $_REQUEST["userId"];
  $blogId = $_REQUEST["blogId"];
  $personaName = stripslashes($_REQUEST["personaName"]);

  if ($personaName == null || $personaName === "") {
    print "ERROR: An author name is required before this blog can be added to $sitename.<br />\n";
    displayUserAuthorLinkForm($blogId, $userId, $displayName, $db);
    return;
  }

  $personaId = addPersona($userId, $personaName, $db);

  foreach ($_REQUEST as $name => $value) {
    $value = stripslashes($value);
    if (substr($name, 0, 6) === "author" && $value === "on") {
      $authorId = substr($name, 6);
      linkAuthorToPersona($authorId, $personaId, $db);
      $authorName = getBlogAuthorName($authorId, $blogId, $db);
      $success = true;
    }
  }

  if (! $success) {
    $authorList = getAuthorList ($blogId, $db);
    $unknownAuthorId = getUnknownAuthorId($blogId, $db);

    if (count($authorList) == 0 && $unknownAuthorId == null) {
      print "<p><font color=\"red\">There was an error in parsing the feed of this blog. Please <a href='/contact-us/'>contact us</a> to ask for help in resolving this problem.</font></p>\n";
      return;
    }

    if ($unknownAuthorId != null && ! isAuthorClaimed($unknownAuthorId, $db) && count($authorList) == 0) {
      linkAuthorToPersona($unknownAuthorId, $personaId, $db);
      $success = true;
    } else {
      // either there are authors and no "unknown" authors
      // or there are authors AND an "unknown" author (weird!)
      // either way, assume this person wants to link to a KNOWN author
      print "<p><font color='red'>Please choose an author from the list. If your name is not on the list, it may be because you have not posted recently. Try again after a recent post. If you believe your blog has been claimed by someone else, please <a href='/contact-us/'>contact us.</a></font></p>\n";
      displayUserAuthorLinkForm($blogId, $userId, $displayName, $db);
    }
  }

  if ($success) {
    print "Congratulations, $personaName, you've claimed your blog. Choose \"Edit Blog\" to edit your blog settings.<br />\n";
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

function parseSearchParams ($in) {

  global $currentTag;
  global $type;
  global $params;

  // Parse the XML we got from the search query
  $parser = xml_parser_create();
  xml_set_element_handler($parser, startParamElemHandler, endParamElemHandler);
  xml_set_character_data_handler($parser, paramCharacterDataHandler);
  xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
  xml_parse($parser, $in);

  // clean up - we're done
  xml_parser_free($parser);

  return $params;

}

function startParamElemHandler($parser, $name, $attribs) {
  global $currentTag;
  $currentTag = $name;
}

function endParamElemHandler($parser, $name) {
  global $currentTag;
  global $params;
  global $paramName;
  global $paramValue;

  if (strcasecmp($name, "param") == 0) {
    if (! is_array($params[$paramName])) {
      $params[$paramName] = array();
    }

    array_push($params[$paramName], $paramValue);
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

}



/*
 * Functions written by other people
 */


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

// Caching
// http://www.depiction.net/tutorials/php/cachingphp.php
class cache {
	// TODO save cache_time in ss-globals.php
	var $cache_time = 2000;//How much time will keep the cache files in seconds.
	var $cache_dir = '';
	var $caching = false;
	var $file = '';
	
	function cache()
	{
		global $cachedir;
		$this->cache_dir = $cachedir;
		// Constructor of the class
		$this->file = $this->cache_dir . urlencode( $_SERVER['REQUEST_URI'] );
		if ( file_exists ( $this->file ) && ( fileatime ( $this->file ) + $this->cache_time ) > time() )
			{
				//Grab the cache:
				$handle = fopen( $this->file , "r");
				do {
		$data = fread($handle, 8192);
		if (strlen($data) == 0) {
			break;
		}
		echo $data;
				} while (true);
				fclose($handle);
			}
		else
			{
				//create cache :
				$this->caching = true;
				ob_start();
			}
	}
	
	function close()
	{
		//You should have this at the end of each page
		if ( $this->caching )
			{
				//You were caching the contents so display them, and write the cache file
				$data = ob_get_clean();
				echo $data;
				$fp = fopen( $this->file , 'w' );
				fwrite ( $fp , $data );
				fclose ( $fp );
			}
	}
}

?>