<?php

// Put us in Eastern Time; eventually, this should be set by a
// parameter of some sort.
date_default_timezone_set( "America/New_York" );
header('Access-Control-Allow-Origin: *');

include_once (dirname(__FILE__)."/../globals.php");
include_once (dirname(__FILE__)."/util.php");

$db = ssDbConnect();
$queryList = httpParamsToSearchQuery();
$settings = httpParamsToExtraQuery();
// TODO: parse an XML input file as well
// $params = parseSearchParams(file_get_contents('php://input'));

if (strstr($_SERVER["REQUEST_URI"], "feed")) {
	header( "Content-Type: application/atom+xml; charset=utf-8" );
	$settings["type"] = "post";
	$settings["citation-in-summary"] = "true";
}
else {
	header('Content-Type: text/xml; charset=utf-8');
}

echo dbPublicSearch($queryList, $settings, $db);

ssDbClose($db);

// Input: type of object to search for (blog/post/topic); list of query parameters; DB handle
// Output: XML document containing search results
// For more information on query parameters, see search API documentation in wiki
function dbPublicSearch($queryList, $settings, $db) {
	$type = $settings["type"];
	
	$searchResults = generateSearchQuery($queryList, $settings, 0, $db);
	
	if ($searchResults["result"] == NULL) {
		$searchResults["result"] = array();
	}

  if ($type === "post") {
		$citationInSummary = 1;
		$sourceInTitle = 0;
		if (isset($settings["citation-in-summary"])) $citationInSummary = $settings["citation-in-summary"];
		if (isset($settings["source-in-title"])) $sourceInTitle = $settings["source-in-title"];
		return (formatSearchPostResults($searchResults["result"], $citationInSummary, $sourceInTitle, $searchResults["errors"], $db));
  }

  $xml = "<?xml version=\"1.0\" ?>\n";
  $xml .=  "<subjectseeker>\n";

  if (count($searchResults["errors"]) > 0) {
    foreach ($searchResults["errors"] as $error) {
      $xml .=  "<error>$error</error>\n";
    }
    $xml .=  "</subjectseeker>\n";
    return $xml;
  }

  if (strcasecmp($type, "topic") == 0) {

    $xml .=  "  <topics>\n";
		if (! empty($searchResults["result"])) {
			while ($row = mysql_fetch_array($searchResults["result"])) {
				$xml .= formatTopic($row);
			}
		}
    $xml .=  "  </topics>\n";
  }

  if (strcasecmp($type, "blog") == 0) {

    $xml .=  "  <blogs>\n";
		if (! empty($searchResults["result"])) {
			while ($row = mysql_fetch_array($searchResults["result"])) {
				$xml .= formatBlog($row);
			}
		}
    $xml .=  " </blogs>\n";

    // Here we might eventually want to return more things. Remember, right
    // now we can only search for "blogs" or "topics." If we end up searching
    // for citations, we might want to return a list of citation results here.

  }

  $xml .=  "</subjectseeker>\n";
  return $xml;
}

// Input: post search results from DB, error message array
// Return: Atom feed
function formatSearchPostResults($resultData, $citationsInSummary, $sourceInTitle, $errormsgs, $db) {
	global $sitename;
	
  // When are we?
  $now = date( "c" );
	
	// Where are we?
	$url = parse_url(getURL ());
	$myHost = $url["scheme"] . "://" . $url["host"];
	$myURI = $myHost . $_SERVER[ "SCRIPT_NAME" ];

  $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<feed xmlns=\"http://www.w3.org/2005/Atom\" xml:lang=\"en\"
      xmlns:ss=\"http://scienceseeker.org/ns/1\">
  <title type=\"text\">$sitename</title>
  <subtitle type=\"text\">Recent Posts</subtitle>
  <link href=\"$myURI\" rel=\"self\"
    type=\"application/atom+xml\" />
  <link href=\"$myHost\" rel=\"alternate\" type=\"text/html\" />
  <id>$myURI</id>
  <updated>$now</updated>
  <rights>No copyright asserted over individual posts; see original
    posts for copyright and/or licensing.</rights>
  <generator>ScienceSeeker Atom serializer</generator>\n";

  if (count($errormsgs) > 0) {
    foreach ($errormsgs as $error) {
      $xml .=  "<error>$error</error>\n";
    }
    $xml .=  "</feed>\n";
    return $xml;
  }
	if ($resultData) {
		while ($row = mysql_fetch_array ($resultData)) {
			// Timezone stuff
			$tzCache = date_default_timezone_get();
			date_default_timezone_set( "UTC" );
			$utcDate = strtotime( $row[ "BLOG_POST_DATE_TIME" ] );
			date_default_timezone_set( $tzCache );
			
			// Put all the SQL data into $postData for easy access
			$postData = array();
			$postData[ "author" ] = sanitize( $row[ "BLOG_AUTHOR_ACCOUNT_NAME" ] );
			$postData["blog_name"] = sanitize( $row[ "BLOG_NAME" ] );
			$postData["blog_uri"] = sanitize( $row[ "BLOG_URI" ] );
			$postData["date"] = date( "c", $utcDate );
			$postData["feed_uri"] = sanitize( $row[ "BLOG_SYNDICATION_URI" ] );
			$postData["id"] = $myHost . "/post/" . $row[ "BLOG_POST_ID" ];
			$postData["summary"] = sanitize(strip_tags($row[ "BLOG_POST_SUMMARY" ], '<br>'));
			$postData["title"] = sanitize( $row[ "BLOG_POST_TITLE" ] );
			$postData["uri"] = sanitize( $row[ "BLOG_POST_URI" ] );
			$postData["hasCitation"] = sanitize( $row[ "BLOG_POST_HAS_CITATION" ] );
			
			$postID = $row["BLOG_POST_ID"];
			
			// Get citations
			if ($postData["hasCitation"] == 1) {
				$postData["citations"] = postIdToCitation($postID, $db);
			}
			
			// Get number of recommendations and comments
			$postData["editorRecCount"] = getRecommendationsCount($postID, NULL, NULL, 1, $db);
			$postData["userRecCount"] = getRecommendationsCount($postID, NULL, NULL, 0, $db);
			$postData["commentCount"] = getRecommendationsCount($postID, "comments", NULL, NULL, $db);
	
			// Language is optional.
			$langSQL = "SELECT L.LANGUAGE_IETF_CODE FROM LANGUAGE AS L, BLOG_POST AS P WHERE P.BLOG_POST_ID = $postID AND L.LANGUAGE_ID = P.LANGUAGE_ID";
			
			$result = mysql_query( $langSQL, $db );
			if ( mysql_error() ) {
				die ( "getPostData: " . mysql_error() );
			}
			$row = mysql_fetch_array( $result );
			if ( $row ) {
				$postData["lang"] = $row[ "LANGUAGE_IETF_CODE" ];
			}
			else {
				$postData["lang"] = "en";
			}
			
			// Post topics are polyvalued, so another query.
			$catSQL = "SELECT T.TOPIC_NAME FROM TOPIC AS T, POST_TOPIC AS PT WHERE PT.BLOG_POST_ID = $postID AND T.TOPIC_ID = PT.TOPIC_ID;";
			$results = mysql_query( $catSQL, $db );
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
			$result = mysql_query( $blogCatSQL, $db );
			if ( mysql_error() ) {
				die ( "getPostData: " . mysql_error() );
			}
	
			$postData[ "blog_categories" ] = array(); // blog-level topics
			while ( $row = mysql_fetch_array( $result ) ) {
				array_push( $postData[ "blog_categories" ], $row[ "TOPIC_NAME" ] );
			}
	
			// Now start putting it all into Atom
			$xml .= "  <entry xml:lang=\"" . $postData[ "lang" ] . "\">\n";
			
			$xml .= "    <title type=\"html\">";
			
			if ($sourceInTitle == TRUE) {
				$xml .= "[".$postData[ "blog_name" ]."] ";
			}
			
			$xml .= $postData["title"] ."</title>\n";
				
			$xml .= "    <id>" . $postData[ "id" ] . "</id>\n";
			
			$xml .= "    <link href=\"" . $postData[ "uri" ] . "\" rel=\"alternate\" />\n";
				
			$xml .= "    <updated>" . $postData[ "date" ] . "</updated>\n";
			
			$xml .= "    <author>\n";
			
			$xml .= "      <name>" . $postData[ "author" ] . "</name>\n";
			
			$xml .= "    </author>\n";
					
			$xml .= "    <summary type=\"html\">" . $postData[ "summary" ];
			
			if ($citationsInSummary == TRUE && isset($postData["citations"])) {
				$xml .= htmlspecialchars("<br />");
				foreach ($postData["citations"] as $citation) {
					$xml .= htmlspecialchars("<br />".$citation["text"]);
				}
			}
			
			$xml .= "</summary>\n";
	
			if ($postData["hasCitation"] ) {
				$xml .= "    <ss:citations>\n";
				// Add citations if any
				if ($postData["citations"]) {
					foreach ($postData["citations"] as $citation) {
						$articleIdentifiers = articleIdToArticleIdentifier ($citation["articleId"], $db);
						$xml .= "      <ss:citation>\n        <ss:citationId type=\"scienceseeker\">".$citation["id"]."</ss:citationId>\n";
						
						foreach ($articleIdentifiers as $articleIdentifier) {
							$xml .= "        <ss:citationId type=\"".$articleIdentifier["idType"]."\">".$articleIdentifier["text"]."</ss:citationId>\n";
						}
										
						$xml .= "        <ss:citationText>".htmlspecialchars($citation["text"])."</ss:citationText>\n      </ss:citation>\n";
					}
				}
				$xml .= "    </ss:citations>\n";
			}
			
			$xml .= "    <ss:community>\n";
	
			$xml .= "      <ss:recommendations userlevel=\"user\" count=\"" . $postData["userRecCount"] . "\"/>\n";
			
			$xml .= "      <ss:recommendations userlevel=\"editor\" count=\"" . $postData["editorRecCount"] . "\"/>\n";
	
			$xml .= "      <ss:comments count=\"".$postData["commentCount"]."\" />\n";
			
			$xml .= "    </ss:community>\n";
	
			foreach ( $postData[ "categories" ] as $category ) {
				$xml .= "    <category term=\"$category\" />\n";
			}
			
			$xml .= "    <source>\n";
			
			$xml .= "      <title type=\"text\">" . $postData[ "blog_name" ] ."</title>\n";
			
			$xml .= "      <link href=\"" . $postData[ "feed_uri" ] . "\" rel=\"self\" />\n";
			
			$xml .= "      <link href=\"" . $postData[ "blog_uri" ] ."\" rel=\"alternate\" type=\"text/html\" />\n";
			
			if ( $postData[ "blog_categories" ] ) {
				foreach ($postData["blog_categories"] as $category) {
					$xml .= "      <category term=\"$category\" />\n";
				}
			}
			$xml .= "    </source>\n";
			$xml .= "  </entry>\n";
		}
	}
	$xml .= "</feed>\n";
	return $xml;
}

// Input: mysql row with info about a Topic
// Return: some XML with that topic's info.
function formatTopic($row) {
  $topicName = $row["TOPIC_NAME"];
  $toplevel = $row["TOPIC_TOP_LEVEL_INDICATOR"];
  if ($toplevel == 1) {
    $toplevel = "true";
  } else {
    $toplevel = "false";
  }
  return ("   <topic toplevel=\"$toplevel\">$topicName</topic>\n");
}


// Input: mysql row with info about a Blog
// Return: some XML with that blog's info.
// TODO: include blog topics? list of blog authors? date of latest post?
function formatBlog($row) {
  $blogName = sanitize( $row["BLOG_NAME"] );
  $blogId = $row["BLOG_ID"];
  $blogUri = sanitize( $row["BLOG_URI"] );
  $blogSyndicationUri = sanitize( $row["BLOG_SYNDICATION_URI"] );
  $blogDescription = sanitize( $row["BLOG_DESCRIPTION"] );

  $xml =  "   <blog><name>$blogName</name><id>$blogId</id><uri>$blogUri</uri><syndicationuri>$blogSyndicationUri</syndicationuri>";
  if ($blogDescription != null && $blogDescription !== "") {
    $xml .=  "<description>$blogDescription</description>";
  }
  $xml .=  "</blog>\n";
  return $xml;
}

?>