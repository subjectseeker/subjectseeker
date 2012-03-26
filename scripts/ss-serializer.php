<?php

include_once "ss-util.php";

//$cache = new cache();

// Set the response header to indicate that this is Atom XML.
header( "Content-Type: application/atom+xml" );

// Parameter n determines the number of posts to syndicate; 100 is the
// default.
// Parameter offset determines the number of the post with which to
// start syndication; 0 is the default.

include_once "ss-util.php";
global $params;

// Put us in Eastern Time; eventually, this should be set by a
// parameter of some sort.
date_default_timezone_set( "America/New_York" );

// When are we?
$now = date( "c" );

// Where are we?
$myHost = getHost();
$myURI = $myHost . $_SERVER[ "SCRIPT_NAME" ];

// What are we looking for?
$params = discoverSearchParams();

// Default number of blog posts to 30.
// TODO: add to ss-globals.php
$numPosts = 30;
if ( isset( $_REQUEST[ "n" ] ) and is_numeric( $_REQUEST[ "n" ] ) and
     ( $_REQUEST[ "n" ] > 0  and $_REQUEST[ "n" ] <= 500) ) {
  $numPosts = (string)(int)$_REQUEST[ "n" ];
}
// Default offset to 0.
$postOffset = 0;
if ( isset( $_REQUEST[ "offset" ] ) and
     is_numeric( $_REQUEST[ "offset" ] ) and
     ( $_REQUEST[ "offset" ] > 0 ) ) {
  $postOffset = (string)(int)$_REQUEST[ "offset" ];
}

// Connect to our database.
$db = ssDbConnect();

print "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<feed xmlns=\"http://www.w3.org/2005/Atom\" xml:lang=\"en\"
      xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\">
  <title type=\"text\">ScienceSeeker</title>
  <subtitle type=\"text\">$numPosts Recent Posts</subtitle>
  <link href=\"$myURI\" rel=\"self\"
    type=\"application/atom+xml\" />
  <link href=\"$homeUrl\" rel=\"alternate\" type=\"text/html\" />
  <id>$myURI</id>
  <updated>$now</updated>
  <rights>No copyright asserted over individual posts; see original
    posts for copyright and/or licensing.</rights>
  <generator>ScienceSeeker Atom serializer</generator>
";

foreach ( getRecentPosts( $params, $numPosts, $postOffset, $db )
	 as $post ) {
  print outputAsAtom( getPostData( $post, $myHost, $db ) );
}

print "</feed>
";

// DELETEME ssDbClose( $db );

// Parse search parameters which were passed in by POST
// and also check any GET parameters.
function discoverSearchParams() {
  $input = file_get_contents('php://input');
  if ($input === "") {
    return parseHttpParams();
  // parse PHP param
  } else {
    return parseSearchParams($input);
  }
}

// Construct the initial part of the URI used to run this script,
// including protocol, host name, and port.  Does not include a
// trailing slash.
function getHost () {
  // How did we get here?
  $myHost = "http";
  if ( isset( $_SERVER[ "HTTPS" ] ) ) {
    $myHost = "https";
  }

  // Where are we?
  $myHost .= "://" . $_SERVER[ "HTTP_HOST" ];

  // More precisely, where are we?
  if ( ( ( $myHost === "http" ) &&
	 ( $_SERVER[ "SERVER_PORT" ] !== "80" ) ) ||
       ( ( $myHost === "https" ) &&
	 ( $_SERVER[ "SERVER_PORT" ] !== "443" ) ) ) {
    $myHost .= ":" . $_SERVER[ "SERVER_PORT" ];
  }

  return $myHost;
}

// Given a post ID in the database, construct an associative array of
// all the interesting information about that post.
function getPostData( $postID, $myHost, $dbHandle ) {
  // Most post information in one statement.
  $postSQL = "SELECT A.BLOG_AUTHOR_ACCOUNT_NAME, B.BLOG_NAME, 
		B.BLOG_URI, B.BLOG_SYNDICATION_URI, P.BLOG_POST_ID, 
		P.BLOG_POST_URI, P.BLOG_POST_DATE_TIME, P.BLOG_POST_SUMMARY, 
		P.BLOG_POST_TITLE, P.BLOG_POST_HAS_CITATION 
		FROM BLOG_AUTHOR AS A, BLOG AS B, BLOG_POST 
		AS P WHERE P.BLOG_POST_ID = $postID AND A.BLOG_AUTHOR_ID = 
		P.BLOG_AUTHOR_ID AND B.BLOG_ID = P.BLOG_ID;";

  $result = mysql_query( $postSQL, $dbHandle );
  if ( mysql_error() ) {
    die ( "getPostData: " . mysql_error() );
  }
  $row = mysql_fetch_array( $result );

  $tzCache = date_default_timezone_get();
  date_default_timezone_set( "UTC" );
  $utcDate = strtotime( $row[ "BLOG_POST_DATE_TIME" ] );
  date_default_timezone_set( $tzCache );

  $postData = array();
  $postData[ "author" ] =
    sanitize( $row[ "BLOG_AUTHOR_ACCOUNT_NAME" ] );
  $postData["blog_name"] = sanitize( $row[ "BLOG_NAME" ] );
  $postData["blog_uri"] = sanitize( $row[ "BLOG_URI" ] );
  $postData["date"] = date( "c", $utcDate );
  $postData["feed_uri"] = sanitize( $row[ "BLOG_SYNDICATION_URI" ] );
  $postData["id"] = $myHost . "/post/" . $row[ "BLOG_POST_ID" ];
  $postData["summary"] = sanitize(strip_tags($row[ "BLOG_POST_SUMMARY" ], '<br>'));
  $postData["title"] = sanitize( $row[ "BLOG_POST_TITLE" ] );
  $postData["uri"] = sanitize( $row[ "BLOG_POST_URI" ] );
  $postData["hasCitation"] = sanitize( $row[ "BLOG_POST_HAS_CITATION" ] );
	
	// Get citations
	if ($postData["hasCitation"] == 1) {
		$postData["citations"] = postIdToCitation($postID, $dbHandle);
	}
		// Get user data
	$postData["personaId"] = $_REQUEST["personaId"];
	
	// Check if user has recommended this post
	if ($postData["personaId"]) {
		$postData["recStatus"] = getRecommendationStatus($postID, $_REQUEST["personaId"], $dbHandle);
		$postData["userPriv"] = $_REQUEST["userPriv"];
	}
	
	$postData["epStatus"] = getEditorsPicksStatus($postID, $dbHandle);
	// Get number of recommendations for this post
	$postData["recCount"] = getRecommendationsCount($postID, NULL, $dbHandle);
	// Get number of comments for this post
	$postData["commentCount"] = getRecommendationsCount($postID, "comments", $dbHandle);

  // Language is optional.
  $langSQL = "SELECT L.LANGUAGE_IETF_CODE FROM LANGUAGE AS L, " .
    "BLOG_POST AS P WHERE P.BLOG_POST_ID = $postID AND " .
    "L.LANGUAGE_ID = P.LANGUAGE_ID;";

  $result = mysql_query( $langSQL, $dbHandle );
  if ( mysql_error() ) {
    die ( "getPostData: " . mysql_error() );
  }
  $row = mysql_fetch_array( $result );
  if ( $row ) {
    $postData[ "lang" ] = $row[ "LANGUAGE_IETF_CODE" ];
  }
  else {
    $postData[ "lang" ] = "en";
  }

  // Post topics are polyvalued, so another query.
  $catSQL = "SELECT T.TOPIC_NAME FROM TOPIC AS T, POST_TOPIC AS PT " .
    "WHERE PT.BLOG_POST_ID = $postID AND T.TOPIC_ID = PT.TOPIC_ID;";
  $results = mysql_query( $catSQL, $dbHandle );
  if ( mysql_error() ) {
    die ( "getPostData: " . mysql_error() );
  }

  $postData[ "categories" ] = array(); // post topics
  while ( $row = mysql_fetch_array( $results ) ) {
    array_push( $postData[ "categories" ], $row[ "TOPIC_NAME" ] );
  }

  // The blog category may not be present.
  $blogCatSQL = "SELECT T.TOPIC_NAME FROM TOPIC AS T, " .
    "PRIMARY_BLOG_TOPIC AS BT, BLOG_POST AS P WHERE P.BLOG_POST_ID " .
    "= $postID AND BT.BLOG_ID = P.BLOG_ID AND T.TOPIC_ID = " .
    "BT.TOPIC_ID;";
  $result = mysql_query( $blogCatSQL, $dbHandle );
  if ( mysql_error() ) {
    die ( "getPostData: " . mysql_error() );
  }

  $postData[ "blog_categories" ] = array(); // blog-level topics
  while ( $row = mysql_fetch_array( $result ) ) {
    array_push( $postData[ "blog_categories" ], $row[ "TOPIC_NAME" ] );
  }

  return $postData;
}

// Get the IDs of the most recent posts in the database, filtered by $params
function getRecentPosts( $params, $numPosts, $offset, $dbHandle ) {
  if ( !isset( $numPosts ) ) {
    $numPosts = 100;
  }
  // TODO: eventually there will be other params to filter on
  $topicList = $params["topic"];
  $blogIds = array();
  if ($topicList != null && is_array($topicList) && count($topicList) > 0) {
    $topicIds = topicNamesToIds($params["topic"], $dbHandle);
    $blogIds = topicIdsToBlogIds($topicIds, $dbHandle);
    if (count ($blogIds) == 0) {
      return array();
    }
  }
	
	if ($params["modifier"]) {
		foreach($params["modifier"] as $modifier) {
			$$modifier = TRUE;
		}
	}
	
  $postSQL = "SELECT BLOG_POST_ID FROM BLOG_POST WHERE BLOG_POST_STATUS_ID = 0 AND BLOG_POST_DATE_TIME <= GETDATE() ";

	if ($citation) {
		$postSQL .= " AND BLOG_POST_HAS_CITATION = 1 ";
	}
	if ($editorsPicks) {
		foreach (getEditorsPicks(NULL, $dbHandle) as $item) {
			$editorsPosts[] = $item["postId"];
		}
		$firstPost = array_shift($editorsPosts);
    $postSQL .= "AND ((BLOG_POST_ID = $firstPost) ";
    foreach ($editorsPosts as $id) {
      $postSQL .= "OR (BLOG_POST_ID = $id) ";
    }
		$postSQL .= ") ";
	}
  if (count ($blogIds) > 0) {
    $firstBlogId = array_shift($blogIds);
    $postSQL .= "AND ((BLOG_ID = $firstBlogId) ";
    foreach ($blogIds as $blogId) {
      $postSQL .= "OR (BLOG_ID = $blogId) ";
    }
		$postSQL .= ") ";
  }
  $postSQL .= "ORDER BY -BLOG_POST_DATE_TIME " .
    "LIMIT $numPosts OFFSET $offset;";

  $results = mysql_query( $postSQL, $dbHandle );
  if ( mysql_error() ) {
    die ( "getRecentPosts: " . mysql_error() );
  }

  $postIDs = array();
  while ( $row = mysql_fetch_array( $results ) ) {
    array_push( $postIDs, $row[ "BLOG_POST_ID" ] );
  }

  return $postIDs;
}

// For a post data structure, output an atom:entry serialization.
// Presumes Atom namespace declaration as default namespace.
function outputAsAtom( $post ) {
  $atomString = "  <entry xml:lang=\"" . $post[ "lang" ] . "\">
";
  $atomString .= "    <userpersona>" . $post["personaId"] . "</userpersona>\n";
	
	$atomString .= "    <userpriv>" . $post["userPriv"] . "</userpriv>\n";
	
  $atomString .= "    <title type=\"html\">" . $post[ "title" ] .
    "</title>
";
  $atomString .= "    <id>" . $post[ "id" ] . "</id>
";
  $atomString .= "    <link href=\"" . $post[ "uri" ] . "\"
      rel=\"alternate\" />
";
  $atomString .= "    <updated>" . $post[ "date" ] . "</updated>
";
  $atomString .= "    <author>
      <name>" . $post[ "author" ] . "</name>
    </author>
";
  $atomString .= "    <summary type=\"html\">" . $post[ "summary" ];
	
	// Add citation to summary if available
	if ($post["citations"]) {
		$atomString .= sanitize("<div class='ss-div-2'><div class='citation-wrapper'>");
		foreach ($post["citations"] as $citation) {
			$atomString .= sanitize("<p>".utf8_decode($citation["text"])."</p>");
		}
		$atomString .= sanitize('</div></div>');
	}
	
  $atomString .= "</summary>\n";

  // TODO insert RDF for citations here
  if ($post["hasCitation"] ) {
    $atomString .= "    <rdf:Description rdf:ID=\"citations\">CITATION</rdf:Description>\n";
  }
	
	if ($post["epStatus"]) {
		$atomString .= "    <rdf:Description rdf:ID=\"editorRecommended\">RECOMMENDED</rdf:Description>\n";
	}
	
	if ($post["recStatus"]) {
		$atomString .= "    <recstatus>Recommended</recstatus>\n";
	}
	
	$atomString .= "    <recommendations>" . $post["recCount"] . "</recommendations>\n";
	
	$atomString .= "    <commentcount>" . $post["commentCount"] . "</commentcount>\n";

  foreach ( $post[ "categories" ] as $category ) {
    $atomString .= "    <category term=\"$category\" />
";
  }

  $atomString .= "    <source>
";
  $atomString .= "      <title type=\"text\">" . $post[ "blog_name" ] .
    "</title>
";
  $atomString .= "      <link href=\"" . $post[ "feed_uri" ] .
    "\" rel=\"self\" />
";
  $atomString .= "      <link href=\"" . $post[ "blog_uri" ] .
    "\" rel=\"alternate\"
        type=\"text/html\" />
";
  if ( $post[ "blog_categories" ] ) {
    foreach ($post["blog_categories"] as $category) {
      $atomString .= "      <category term=\"$category\" />
";
    }
  }
  $atomString .= "    </source>
  </entry>
";

  return $atomString;
}

//$cache->close();

?>
