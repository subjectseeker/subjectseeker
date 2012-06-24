<?php

/*

Copyright © 2010–2012 Christopher R. Maden and Jessica Perry Hekman.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

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
		$message = "<p class=\"ss-error\">ERROR: $blogUri (ID $blogId): " . $feed->error() . "</p>\n";
		return $message;
	}

	foreach ($feed->get_items(0, 50) as $item) {
		$postId = addSimplePieItem($item, $feed->get_language(), $blogId, $db);
		$item = NULL;
		if ($postId) {
			$postIds[] = $postId;
		}
	}
	markCrawled($blogId, $db);
	
	$newPostCount = count($postIds);
	
	$message = "<p class=\"ss-successful\">$blogName (ID $blogId) has been scanned; $newPostCount new posts found.</p>";

	$feed = NULL;

	return $message;
}

/*
 * Curl functions
 */

// Input: type of search; search parameters (hash array)
// Output: Curl for performing the search
function getSearchCurl ($data) {
  global $searchUrl;
  $url = $searchUrl;

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

/*
 * Search functions
 */

// Input: type of object to search for (blog/post/topic); list of query parameters; DB handle
// Output: XML document containing search results
// For more information on query parameters, see search API documentation in wiki
function generateSearchQuery ($queryList, $settings, $userPriv, &$errormsgs, $db) {

  global $numResults;
	global $maximumResults;
	// Set all the default values of the search
  $fromList = array();
  $whereList = array();
	$groupCheck = FALSE;
	$type = $settings["type"];
	
	if ($type == "") {
		array_push ($errormsgs, "No search type specified.");
		return;
	}
  elseif ($type === "topic") {
    $select = "SELECT topic.TOPIC_ID, topic.TOPIC_NAME, topic.TOPIC_TOP_LEVEL_INDICATOR";
    $fromList = generateTopicFrom($queryList);
    $whereList = generateTopicWhere ($queryList, $errormsgs);

    // bump up default limit for topics
    $numResults = 200;

  } else if ($type === "blog") {
    $select = "SELECT blog.*";
    $order = "ORDER BY blog.BLOG_NAME ASC";
		// TODO order by blog with most recent post
    // how to do this? update blog table every time we add a post?
		$group = "GROUP BY blog.BLOG_ID";
    $fromList = generateBlogFrom($queryList, $userPriv);
    $whereList = generateBlogWhere($queryList, $userPriv, $errormsgs);
		$order = generateBlogSort ($settings, $whereList, $fromList, $userPriv, $errormsgs);
		
		if ($settings["show-all"] == "true" && $userPriv > 0) {
			unset($whereList["blogStatusId"]);
		}

  } else if ($type === "post") {
    $fromList = generatePostFrom($queryList, $userPriv);
   	$whereList = generatePostWhere($queryList, $userPriv, $groupCheck, $minimumRec, $errormsgs);
		$order = generatePostSort ($settings, $whereList, $fromList, $groupCheck, $errormsgs);
		
		if ($settings["show-all"] != "true") array_push($whereList, "(post.BLOG_POST_DATE_TIME < NOW())");
		
		// If min-recommendations filter is active, count the recommendations
		if ($minimumRec) $count = "HAVING COUNT(rec.BLOG_POST_ID) >= $minimumRec";
		
		// If there could be duplicated results, group them by ID
		if ($groupCheck) $group = "GROUP BY post.BLOG_POST_ID";
		
		$select .= "SELECT post.BLOG_POST_ID, post.BLOG_POST_URI, post.BLOG_POST_DATE_TIME, post.BLOG_POST_SUMMARY, post.BLOG_POST_TITLE, post.BLOG_POST_HAS_CITATION, blog.BLOG_ID, blog.BLOG_NAME, blog.BLOG_URI, blog.BLOG_SYNDICATION_URI, author.BLOG_AUTHOR_ACCOUNT_NAME";
  } else {
    array_push ($errormsgs, "Unknown type: $type");
		return;
  }
	
	if (! empty($errormsgs)) return; 
	
	$limitNumber = $numResults;
  // Construct LIMIT part of query
  if ( is_numeric($settings["limit"]) and ($settings["limit"] > 0  and $settings["limit"] <= $maximumResults) ) {
  	$limitNumber = (string)(int)$settings["limit"];
  }
  $limit = "LIMIT $limitNumber";

  // Construct OFFSET part of query, default to 0.
  $offsetNumber = 0;
  if ( is_numeric($settings["offset"]) and ($settings["offset"] > 0 ) ) {
  	$offsetNumber = (string)(int)$settings["offset"];
  }
  $offset = "OFFSET $offsetNumber";

  // Construct FROM part of query
	$from = constructFrom($fromList);
	
  // Construct WHERE part of query
	$where = constructWhere($whereList);
	
  // construct SQL query
  $sql = "$select $from $where $group $count $order $limit $offset;";

  // for debugging:
  // print "<br />SQL $sql</br>";

  // execute SQL query
 	$resultData = mysql_query($sql, $db);
	
	return $resultData;
}

// Input: Array of SQL where statements
// Output: String for SQL query
function constructWhere ($whereList) {
	$whereList = array_unique($whereList);
	
	$where = "";
  foreach ($whereList as $oneWhere) {
    if ($where === "") {
      $where = "WHERE ($oneWhere)";
    } else {
      $where .= " AND ($oneWhere)";
    }
  }
  $where .= " ";
	
	return $where;
}

// Input: Array of SQL from statements
// Output: String for SQL query
function constructFrom ($fromList) {
	$from = "";
  foreach ($fromList as $oneFrom => $status) {
    if ($from === "") {
      $from = "FROM $oneFrom";
    } else {
      $from .= ", $oneFrom";
    }
  }
  $from .= " ";
	
	return $from;
}

// Input: list of search queries for a topic search
// Return: string useful in FROM clause in SQL search, based on input queries
function generateTopicFrom ($queryList) {
  $fromList["TOPIC topic"] = true;
  return $fromList;
}

// Input: list of search queries for a topic search
// Return: string useful in WHERE clause in SQL search, based on input queries
function generateTopicWhere ($queryList, &$errormsgs) {
	
	$whereList = array();

  foreach ($queryList as $query) {
		// Escape strings that could be included in the SQL query
		$searchValue = mysql_real_escape_string($query->value);
		$searchType = mysql_real_escape_string($query->modifier);
		
    if ($query->name === "toplevel") {
			if ($searchValue == "false") $toplevel = "0";
			elseif ($searchValue == "true" || $searchValue == NULL) $toplevel = "1";
			else array_push ($errormsgs, "Unrecognized value: $searchValue");
			
			array_push($whereList, array("topic.TOPIC_TOP_LEVEL_INDICATOR ="=>"$toplevel"));
			if ($searchType) { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
			
    } else {
      array_push ($errormsgs, "Unknown filter: " . $query->name);
      return "";
    }
  }
	
	return $whereList;
}

// Input: list of search queries for a blog search
// Return: string useful in FROM clause in SQL search, based on input queries
function generateBlogFrom ($queryList, $userPriv) {
	
	$fromList["BLOG blog"] = true;

  foreach ($queryList as $query) {

    if ($query->name === "topic") {
      $fromList["BLOG blog"] = true;
      $fromList["PRIMARY_BLOG_TOPIC pbt"] = true;
      $fromList["TOPIC t"] = true;
    } else if ($query->name === "has-citation") {
      $fromList["BLOG_POST post"] = true;
    }
  }
  return $fromList;
}

// Input: list of search queries for a blog search
// Return: string useful in WHERE clause in SQL search, based on input queries
function generateBlogWhere ($queryList, $userPriv, &$errormsgs) {
	
	// Name the index of this array item to be overriden when appropriate.
	// Eventually it would be good to name all indexes of the search conditions.
	$whereList = array("blogStatusId"=>"blog.BLOG_STATUS_ID = 0");
	
  foreach ($queryList as $query) {
		
		// Escape strings that could be included in the SQL query
		$searchValue = mysql_real_escape_string($query->value);
		$searchType = mysql_real_escape_string($query->modifier);
		
    if ($query->name === "topic") {
			if ($firstTopic === FALSE) $topicsQuery .= " OR ";
      $topicsQuery .= "t.TOPIC_NAME='$searchValue' AND blog.BLOG_ID=pbt.BLOG_ID AND pbt.TOPIC_ID=t.TOPIC_ID";
			$firstTopic = FALSE;
			if ($searchType) { array_push ($errormsgs, "Unrecognized modifier: $searchType");}

    } else if ($query->name === "citation") {
      array_push ($errormsgs, "Can't search for blogs by citation");

    } else if ($query->name === "has-citation") {
			if ($searchValue == "false") $hasCitation = "0";
			elseif ($searchValue == "true" || $searchValue == NULL) $hasCitation = "1";
			else array_push ($errormsgs, "Unrecognized value: $searchValue");
			
      array_push ($whereList, "post.BLOG_POST_HAS_CITATION=$hasCitation AND post.BLOG_ID=blog.BLOG_ID");
			
      if ($searchType === "doi" || $searchType === "pmid" || $searchType === "arxiv" || $searchType === "other") {
        array_push ($whereList, "post.BLOG_POST_ID = pc.BLOG_POST_ID AND pc.CITATION_ID = citation.CITATION_ID AND citation.ARTICLE_ID = artid.ARTICLE_ID AND artid.ARTICLE_IDENTIFIER_TYPE = '$searchType'");
      }
      elseif ($searchType != "all" && $searchType != NULL && $searchType != "") {
        array_push ($errormsgs, "Unrecognized modifier: $searchType");
      }
      
    } else if ($query->name == "identifier") {
      if (is_numeric($searchValue)) array_push ($whereList, "blog.BLOG_ID=$searchValue");
      else array_push ($errormsgs, "Identifier value must be numeric: $searchValue");
			
      if ($searchType) { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
      
    } else if ($query->name == "title") {
      if ($searchType === "all") { array_push ($whereList, "blog.BLOG_NAME = '$searchValue'"); }
      elseif ($searchType === "some" || $searchType == NULL) { array_push ($whereList, "blog.BLOG_NAME LIKE '%$searchValue%'"); }
      else { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
      
    } else if ($query->name == "summary") {
      if ($searchType === "all") { array_push ($whereList, "blog.BLOG_DESCRIPTION = '$searchValue'"); }
      elseif ($searchType === "some" || $searchType == NULL) { array_push ($whereList, "blog.BLOG_DESCRIPTION LIKE '%$searchValue%'"); }
      else { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
      
    } else if ($query->name == "url") {
      if ($searchType === "all") { array_push ($whereList, "blog.BLOG_URI = '$searchValue'"); }
      elseif ($searchType === "some" || $searchType == NULL) { array_push ($whereList, "blog.BLOG_URI LIKE '%$searchValue%'"); }
      else { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
      
    } elseif ($query->name == "status") {
			// Filter only for administration tools, not meant to be used by normal users since it displayes rejected sites.
			if ($userPriv > 0) {
				if (is_numeric($searchValue)) {
					$whereList["blogStatusId"] = "blog.BLOG_STATUS_ID = '$searchValue'";
				}
      	else array_push ($errormsgs, "Status value must be numeric: $searchValue");
			}
			else {
				array_push ($errormsgs, "You don't have the privileges to use filter: " . $query->name);
			}
		} else {
      array_push ($errormsgs, "Unrecognized filter: " . $query->name);
    }
  }
  if ($topicsQuery) {
		$topicsQuery = "t.TOPIC_TOP_LEVEL_INDICATOR = 1 AND ($topicsQuery)";
		array_push ($whereList, $topicsQuery);
	}

  return $whereList;
}

// Input: list of search queries for a blog search
// Return: string useful in SORT clause in SQL search, based on input queries
function generateBlogSort ($settings, &$whereList, &$fromList, &$errormsgs) {
	$sortBy = $settings["sort"];
	$orderBy = $settings["order"];
	
	if (!$sortBy) {
		$sortBy = "alphabetical";
	}
	if (!$orderBy) {
		$orderBy = "asc";
	}
	
	// Valid sort and order values, and their columns.
	$validSorts = array ("id" => "blog.BLOG_ID","status" => "blog.BLOG_STATUS_ID","alphabetical" => "blog.BLOG_NAME","added-date" => "blog.ADDED_DATE_TIME","crawled-date" => "blog.CRAWLED_DATE_TIME");
	$validOrders = array("desc" => "DESC","asc" => "ASC");
	
	if ($validSorts["$sortBy"] && $validOrders["$orderBy"]) {
		$order = "ORDER BY " . $validSorts["$sortBy"] . " " . $validOrders["$orderBy"];
	}
	else {
		array_push ($errormsgs, "Unknown order: $sortBy $orderBy");
	}
	
	return $order;
}

// Input: list of search queries for a post search
// Return: string useful in FROM clause in SQL search, based on input queries
function generatePostFrom ($queryList, $userPriv) {

	$fromList["BLOG_POST post"] = true;
  $fromList["BLOG blog"] = true;
  $fromList["BLOG_AUTHOR author"] = true;

  foreach ($queryList as $query) {

   	if ($query->name === "citation") {
			$searchType = mysql_escape_string($query->modifier);
			if (! $searchType) $searchType = "id-all";
			
			$fromList["CITATION citation"] = true;
			$fromList["POST_CITATION pc"] = true;
			if ($searchType == "id-all" || $searchType == "doi" || $searchType == "pmid" || $searchType == "arxiv") {
				$fromList["ARTICLE_IDENTIFIER artid"] = true;
			}
			if ($searchType == "article-title" || $searchType == "journal-title") {
				$fromList["ARTICLE art"] = true;
			}
			if ($searchType == "author") {
				$fromList["ARTICLE_AUTHOR artau"] = true;
				$fromList["ARTICLE_AUTHOR_LINK auart"] = true;
			}
		} else if ($query->name === "has-citation") {
			if ($query->modifier) {
				$fromList["CITATION citation"] = true;
				$fromList["POST_CITATION pc"] = true;
				$fromList["ARTICLE_IDENTIFIER artid"] = true;
			}
			
    } else if ($query->name === "topic") {
      $fromList["POST_TOPIC pt"] = true;
      $fromList["TOPIC t"] = true;
    } else if ($query->name === "blog") {
			if ($query->modifier == "topic") { 
				$fromList["PRIMARY_BLOG_TOPIC pbt"] = true;
				$fromList["TOPIC t"] = true;
			}
			
		} else if ($query->name === "recommender-status") {
			$fromList["RECOMMENDATION rec"] = true;
			$fromList["USER user"] = true;
			
		} else if ($query->name === "recommended-by") {
			$fromList["RECOMMENDATION rec"] = true;
			$fromList["USER user"] = true;
			
		} else if ($query->name === "min-recommendations") {
			$fromList["RECOMMENDATION rec"] = true;
			
		} else if ($query->name === "is-recommended") {
			if ($query->value !== "false") $fromList["RECOMMENDATION rec"] = true;
		}
  }

  return $fromList;

}

// Input: list of search queries for a post search
// Return: string useful in WHERE clause in SQL search, based on input queries
function generatePostWhere ($queryList, $userPriv, &$groupCheck, &$minimumRec, &$errormsgs) {

  $whereList = array("post.BLOG_POST_STATUS_ID = 0", "blog.BLOG_STATUS_ID = 0", "blog.BLOG_ID = post.BLOG_ID", "blog.BLOG_ID = author.BLOG_ID", "post.BLOG_AUTHOR_ID = author.BLOG_AUTHOR_ID");
	
	foreach ($queryList as $query) {
		
		// Escape strings that could be included in the SQL query
		$searchValue = mysql_real_escape_string($query->value);
		$searchType = mysql_real_escape_string($query->modifier);
		
		if ($query->name == "blog") {
			if ($searchType === "title-some") {  array_push ($whereList, "blog.BLOG_NAME LIKE '%$searchValue%'"); }
			elseif ($searchType === "title-all") {  array_push ($whereList, "blog.BLOG_NAME = '$searchValue'"); }
			elseif ($searchType === "identifier") {
				if (is_numeric($searchValue)) array_push ($whereList, "blog.BLOG_ID = '$searchValue'");
				else array_push ($errormsgs, "Identifier value must be numeric: $searchValue");
			}
			elseif ($searchType === "topic") {
				if ($blogTopics === TRUE) $blogTopicsQuery .= " OR ";
				$blogTopicsQuery .= "t.TOPIC_NAME='" . mysql_real_escape_string($searchValue) . "' AND blog.BLOG_ID=pbt.BLOG_ID AND pbt.TOPIC_ID=t.TOPIC_ID";
				$blogTopics = TRUE;
			}
			else { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
			
		} else if ($query->name === "identifier") {
			if (is_numeric($searchValue)) array_push ($whereList, "post.BLOG_POST_ID=$searchValue");
			else array_push ($errormsgs, "Identifier value must be numeric: $searchValue");
			
			if ($searchType) { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
		
		} else if ($query->name === "topic") {
			if ($topics == TRUE) $topicsQuery .= " OR ";
      $topicsQuery .= "t.TOPIC_NAME='$searchValue' AND t.TOPIC_ID=pt.TOPIC_ID AND post.BLOG_POST_ID=pt.BLOG_POST_ID";
			$topics = TRUE;
			if ($searchType) { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
			
    } else if ($query->name === "citation") {
			
			if ($searchType == "author") {
				array_push ($whereList, "post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","auart.ARTICLE_ID = citation.ARTICLE_ID","auart.ARTICLE_AUTHOR_ID = artau.ARTICLE_AUTHOR_ID","artau.ARTICLE_AUTHOR_FULL_NAME LIKE '%$searchValue%'");
			}
			elseif ($searchType == "article-title") {
				array_push ($whereList, "post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","art.ARTICLE_ID = citation.ARTICLE_ID","art.ARTICLE_TITLE LIKE '%$searchValue%'");
			}
			elseif ($searchType == "journal-title") {
				array_push ($whereList, "post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","art.ARTICLE_ID = citation.ARTICLE_ID","art.ARTICLE_JOURNAL_TITLE LIKE '%$searchValue%'");
			}
			elseif ($searchType == "id-all" || $searchType == "doi" || $searchType == "pmid" || $searchType == "arxiv" || $searchType == "other" || $searchType == NULL) {
				array_push ($whereList, "post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","artid.ARTICLE_IDENTIFIER_TEXT = '$searchValue'","citation.ARTICLE_ID = artid.ARTICLE_ID");
				if ($searchType != NULL) {
					array_push ($whereList, "artid.ARTICLE_IDENTIFIER_TYPE = '$searchType'");
				}
			}
			else { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
			$groupCheck = TRUE;
			
    } else if ($query->name === "has-citation") {
      if ($searchValue == "false") $hasCitation = "0";
			elseif ($searchValue == "true" || $searchValue == NULL) $hasCitation = "1";
			else array_push ($errormsgs, "Unrecognized value: $searchValue");
			
			array_push ($whereList, "post.BLOG_POST_HAS_CITATION=$hasCitation");
			
			if ($searchType === "doi" || $searchType === "pmid" || $searchType === "arxiv" || $searchType === "other") {
				$groupCheck = TRUE;
				array_push ($whereList, "post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","citation.ARTICLE_ID = artid.ARTICLE_ID","artid.ARTICLE_IDENTIFIER_TYPE = '$searchType'");
			}
			elseif ($searchType != "all" && $searchType != NULL && $searchType != "") {
				array_push ($errormsgs, "Unrecognized modifier: $searchType");
			}
			
    } else if ($query->name === "recommender-status") {
      if ($searchValue == "editor") $privilege = "1";
      elseif ($searchValue == "user" || $searchValue == NULL) $privilege = "0";
			else array_push ($errormsgs, "Unrecognized value: $searchValue");
			
			array_push ($whereList, "post.BLOG_POST_ID = rec.BLOG_POST_ID","user.USER_ID = rec.USER_ID","user.USER_PRIVILEGE_ID >= $privilege");
			$groupCheck = TRUE;
			if ($searchType) { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
			
		} else if ($query->name === "recommended-by") {
			array_push ($whereList, "post.BLOG_POST_ID = rec.BLOG_POST_ID","user.DISPLAY_NAME = '$searchValue'","rec.USER_ID = user.USER_ID");
			if ($searchType) { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
			
		} else if ($query->name == "title") {
			if ($searchType === "all") { array_push ($whereList, "post.BLOG_POST_TITLE = '$searchValue'"); }
			elseif ($searchType === "some" || $searchType == NULL) { array_push ($whereList, "post.BLOG_POST_TITLE LIKE '%$searchValue%'"); }
			else { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
			
		} else if ($query->name == "summary") {
			if ($searchType === "all") { array_push ($whereList, "post.BLOG_POST_SUMMARY = '$searchValue'"); }
			elseif ($searchType === "some" || $searchType == NULL) { array_push ($whereList, "post.BLOG_POST_SUMMARY LIKE '%$searchValue%'"); }
			else { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
			
		} else if ($query->name == "url") {
			if ($searchType === "all") { array_push ($whereList, "post.BLOG_POST_URI = 'searchValue'"); }
			elseif ($searchType === "some" || $searchType == NULL) { array_push ($whereList, "post.BLOG_POST_URI LIKE '%$searchValue%'"); }
			else { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
			
		} else if ($query->name === "min-recommendations") {
			if (is_numeric($searchValue)) array_push ($whereList, "post.BLOG_POST_ID = rec.BLOG_POST_ID");
			else array_push ($errormsgs, "Minimum recommendations value must be numeric: $searchValue");
			
			$groupCheck = TRUE;
			if ($searchType) { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
			
		} else if ($query->name === "is-recommended") {
			if ($searchValue == "false") array_push ($whereList, "NOT EXISTS (SELECT rec.BLOG_POST_ID FROM RECOMMENDATION rec WHERE post.BLOG_POST_ID = rec.BLOG_POST_ID)");
			elseif ($searchValue == "true" || $searchValue == NULL) {
				array_push ($whereList, "post.BLOG_POST_ID = rec.BLOG_POST_ID");
				$groupCheck = TRUE;
			}
			else array_push ($errormsgs, "Unrecognized value: $searchValue");
			
			if ($searchType) { array_push ($errormsgs, "Unrecognized modifier: $searchType");}
			
		} else {
    	array_push ($errormsgs, "Unrecognized filter: " . $query->name);
    }
  }
	if ($topicsQuery) {
		array_push ($whereList, $topicsQuery);
	}
	if ($blogTopicsQuery) {
		if ($topicsQuery) array_push ($errormsgs, "Search by blog level topics and post level topics at the same time is not supported.");
		$blogTopicsQuery = "t.TOPIC_TOP_LEVEL_INDICATOR = 1 AND ($blogTopicsQuery)";
		array_push ($whereList, $blogTopicsQuery);
	}
	
  return $whereList;
}

// Input: list of search queries for a post search
// Return: string useful in SORT clause in SQL search, based on input queries
function generatePostSort ($settings, &$whereList, &$fromList, &$groupCheck, &$errormsgs) {
	$sortBy = $settings["sort"];
	$orderBy = $settings["order"];
	if (!$sortBy) {
		$sortBy = "publication-date";
	}
	if (!$orderBy) {
		$orderBy = "desc";
	}
	
	// Valid sort and order values, and their columns.
	$validSorts = array ("id" => "post.BLOG_POST_ID","alphabetical" => "post.BLOG_POST_TITLE","publication-date" => "post.BLOG_POST_DATE_TIME","added-date" => "post.BLOG_POST_INGEST_DATE_TIME","recommendation-date" => "rec.REC_DATE_TIME","recommendation-count" => "COUNT(rec.REC_DATE_TIME)");
	$validOrders = array("desc" => "DESC","asc" => "ASC");
	
	if ($validSorts["$sortBy"] && $validOrders["$orderBy"]) {
		if ($sortBy == "recommendation-date" || $sortBy == "recommendation-count") {
			$groupCheck = TRUE;
			unset($fromList["RECOMMENDATION rec"]);
			$fromList["(SELECT * FROM RECOMMENDATION ORDER BY REC_DATE_TIME DESC) rec"] = true;
			array_push ($whereList, "post.BLOG_POST_ID = rec.BLOG_POST_ID");
		}
		$order = "ORDER BY " . $validSorts["$sortBy"] . " " . $validOrders["$orderBy"];
	}
	else {
		array_push ($errormsgs, "Unknown order: $sortBy $orderBy");
	}
	
	return $order;
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
// deprecated (TODO: delete this function)
function parseHttpParams() {
  $i = 0;
  $params = array();

  while (array_key_exists ("filter$i", $_REQUEST)) {
    $filter = $_REQUEST["filter$i"];
    $value = $_REQUEST["value$i"];
    if (! is_array($params[$filter])) {
      $params[$filter] = array();
    }
    array_push($params[$filter], $value);
    $i++;
    $filter = $_REQUEST["filter$i"];
  }

  return $params;
}

// Input: Optional http query.
// Output: list of SSFilter objects representing search query
function httpParamsToSearchQuery($parsedQuery = NULL) {
	if (! $parsedQuery) $parsedQuery = $_REQUEST;
	
  $i = 0;
  // TODO JPH use this list
  $params = array("filter", "value", "modifier");
  $searchObjs = array();

  while (array_key_exists ("filter$i", $parsedQuery)) {
    $ssFilter = new SSFilter();
    $ssFilter->name = $parsedQuery["filter$i"];

    if (array_key_exists("value$i", $parsedQuery)) {
      $ssFilter->value = $parsedQuery["value$i"];
    }

    if (array_key_exists("modifier$i", $parsedQuery)) {
      $ssFilter->modifier = $parsedQuery["modifier$i"];
    }
		
    array_push($searchObjs, $ssFilter);
    $i++;
  }
	
  return $searchObjs;
}

// Input: Optional http query.
// Output: array of additional data for the search
function httpParamsToExtraQuery($parsedQuery = NULL) {
	if (! $parsedQuery) $parsedQuery = $_REQUEST;
	
	$results["limit"] = $parsedQuery["n"];
	$results["offset"] = $parsedQuery["offset"];
	$results["type"] = $parsedQuery["type"];
	$results["show-all"] = $parsedQuery["show-all"];
	$results["sort"] = $parsedQuery["sort"];
	$results["order"] = $parsedQuery["order"];
	
	return $results;
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

// Update offset for pages.
function updateHttpQuery () {
	$pagesize = $_REQUEST["n"];
	$offset = $_REQUEST["offset"];
	$httpQuery = $_SERVER["QUERY_STRING"];
	parse_str($httpQuery, $queryResults);
	
	if (! $pagesize) $pagesize = 30;
	if (! $offset) $offset = 0;
	$nextOffset = $offset + $pagesize;
	$prevOffset = $offset - $pagesize;
	
	$queryResults["offset"] = $nextOffset;
	$result["nextPage"] = http_build_query($queryResults);
	
	$queryResults["offset"] = $prevOffset;
	$result["prevPage"] = http_build_query($queryResults);
	
	return $result;
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

// Input: Post ID, Type of count, user ID, Privilege ID, DB handle
// Output: Number of recommendations or comments for this post
function getRecommendationsCount($postId, $type, $userId, $userPrivilegeId, $db) {
	$whereList = array();
	
	$fromList["RECOMMENDATION rec"] = true;
	array_push ($whereList, "rec.BLOG_POST_ID = $postId");
	if ($userId) {
		$fromList["USER user"] = true;
		array_push ($whereList, "user.USER_ID = $userId", "user.USER_ID = rec.USER_ID");
	}
	if (is_numeric($userPrivilegeId)) {
		$fromList["USER user"] = true;
		if ($userPrivilegeId == 1) $privilegeQuery = "> 0";
		else $privilegeQuery = "= $userPrivilegeId";
		array_push ($whereList, "user.USER_ID = rec.USER_ID", "user.USER_PRIVILEGE_ID $privilegeQuery");
	}
	if ($type == "comments") {
		array_push ($whereList, "REC_COMMENT != ''");
	}
	
	// Construct FROM part of query
	$from = constructFrom($fromList);
	
  // Construct WHERE part of query
	$where = constructWhere($whereList);
	
	$sql = "SELECT COUNT(rec.USER_ID) $from $where";
	$result = mysql_query($sql, $db);
	$count = mysql_result($result, 0);
	return $count; 
}
// Input: DB handle
// Return: IDs (and optionally comment and related image) of posts recommended by editors
function getEditorsPicks($type, $db) {
	$sql = "SELECT rec.BLOG_POST_ID";
	if ($type == 'images') {
		$sql .= ", rec.REC_COMMENT, rec.REC_IMAGE";
	}
	$sql .= " FROM RECOMMENDATION rec, USER user WHERE rec.USER_ID = user.USER_ID AND user.USER_PRIVILEGE_ID > 0";
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

// Input: blog ID, DB handle
// Return: array of email addresses of people associated with this blog
function getBlogContacts($blogId, $db) {
  $contacts = array();
  $sql = "select u.EMAIL_ADDRESS from BLOG_AUTHOR ba, USER u where ba.BLOG_ID=$blogId and u.USER_ID=ba.USER_ID";
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
// Output: ids of blogs owned by this user
function getBlogIdsByUserId ($userId, $db) {

  // BLOG_AUTHOR has USER_ID
  // BLOG_AUTHOR has BLOG_ID and BLOG_AUTHOR_ACCOUNT_NAME

  $sql = "select ba.BLOG_ID, user.DISPLAY_NAME from USER user, BLOG_AUTHOR ba, BLOG pa where user.USER_ID=$userId and ba.USER_ID=user.USER_ID and pa.BLOG_STATUS_ID=0 and pa.BLOG_ID=ba.BLOG_ID";
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
		
	if ($blogId == null) {
    print "<span class=\"ss-error\">Please specify blog ID (getAuthorList)</span>\n";
    return;
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
      addBlogAuthor($authorName, $blogId, $db);
    }
    unset($item);
  }
  unset ($feed);

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
	$postTitle = mysql_real_escape_string($item->get_title());
	$postDate = $item->get_local_date();
	if ($postDate) {
  	$timestamp = dateStringToSql($postDate);
	}
	else {
		$timestamp = date("Y-m-d H:i:s");
	}
	
	$sql = "SELECT BLOG_POST_ID FROM BLOG_POST WHERE (BLOG_POST_TITLE = '$postTitle' AND BLOG_POST_DATE_TIME = '$timestamp') OR (BLOG_POST_URI = '$itemURI')";
	$result =  mysql_query($sql, $db);
	$row = mysql_fetch_array($result);
	$existing = $row["BLOG_POST_ID"];
	
  if ($existing) {
    return NULL;
  }

  $blogAuthor = $item->get_author();
  $blogAuthorName = "Unknown";

  $authorList = getAuthorList ($blogId, $db);

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

  $languageId = "NULL";
  if ($language) {
    $languageId = languageToId($language, $db);
  }

  if ( is_null( $languageId  ) ) {
    $languageID = "NULL";
  }

  $summary = smartyTruncate($item->get_description(), 500);
  if (strlen ($summary) != strlen ($item->get_description())) {
    $summary .= " […]";
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
	
	$item = NULL;
	$itemURI = NULL;
	$summary = NULL;

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

function getUserSocialAccount ($socialNetworkId, $userId, $db) {
	$sql = "SELECT * FROM USER_SOCIAL_ACCOUNT WHERE SOCIAL_NETWORK_ID = '$socialNetworkId' AND USER_ID = '$userId'";
	$result = mysql_query($sql, $db);
	
	$row = mysql_fetch_array($result);
	
	return $row;
}

function addUserSocialAccount ($socialNetworkId, $socialNetworkAccountName, $oauthToken, $oauthSecretToken, $userId, $db) {
	$socialNetworkAccountName = mysql_real_escape_string($socialNetworkAccountName);
	
	$sql = "REPLACE INTO USER_SOCIAL_ACCOUNT (SOCIAL_NETWORK_ID, SOCIAL_NETWORKING_ACCOUNT_NAME, OAUTH_TOKEN, OAUTH_SECRET_TOKEN, USER_ID) VALUES ('$socialNetworkId', '$socialNetworkAccountName', '$oauthToken', '$oauthSecretToken', '$userId')";
	$result = mysql_query($sql, $db);
}

function removeUserSocialAccount($socialNetworkId, $userId, $db) {
	$sql = "DELETE FROM USER_SOCIAL_ACCOUNT WHERE SOCIAL_NETWORK_ID = '$socialNetworkId' AND USER_ID = '$userId'";
	mysql_query($sql, $db);
}

// Input: Social network account name, Social Network ID, Blog ID, DB handle
// Action: link IDs of post and topic in DB
function addBlogSocialAccount ($socialNetworkAccountName, $socialNetworkId, $blogId, $db) {
	$socialNetworkAccountName = mysql_real_escape_string($socialNetworkAccountName);
	
	$sql = "REPLACE INTO BLOG_SOCIAL_ACCOUNT (SOCIAL_NETWORK_ID, SOCIAL_NETWORKING_ACCOUNT_NAME, BLOG_ID) VALUES ('$socialNetworkId', '$socialNetworkAccountName', '$blogId')";
	mysql_query($sql, $db);
}

// Input: Social Network ID, Blog ID, DB handle
// Action: link IDs of post and topic in DB
function getBlogSocialAccount ($socialNetworkId, $blogId, $db) {
	$sql = "SELECT * FROM BLOG_SOCIAL_ACCOUNT WHERE SOCIAL_NETWORK_ID = '$socialNetworkId' AND BLOG_ID = '$blogId'";
	$result = mysql_query($sql, $db);
	
	$row = mysql_fetch_array($result);
	
	return $row;
}

function removeBlogSocialAccount($socialNetworkId, $blogId, $db) {
	$sql = "DELETE FROM BLOG_SOCIAL_ACCOUNT WHERE SOCIAL_NETWORK_ID = '$socialNetworkId' AND BLOG_ID = '$blogId'";
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
	$sql = "SELECT USER_ID, REC_DATE_TIME, REC_COMMENT FROM RECOMMENDATION WHERE BLOG_POST_ID = '$postId' AND REC_COMMENT != '' ORDER BY REC_DATE_TIME ASC";
	$results = mysql_query($sql, $db);
	
	$comments = array();
  while ($row = mysql_fetch_array($results)) {
    $comment["userId"] = $row["USER_ID"];
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

  $userPriv = getUserPrivilegeStatus($userId, $db);
  if ($userPriv > 0) { // moderator or admin
    $status = 0; // active
  }
	else {
		$status = 1; // pending
		# Send email to site admin with notification that a blog is waiting for approval
  	$mailSent = mail ($siteApprovalEmail, "[$sitename admin] Pending blog submission", "Pending blog submission at $approveUrl");
		
		if (! $mailSent) {
			# TODO log this
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
function associateTopic($topic2, $blogId, $db) {
  $sql = "INSERT INTO PRIMARY_BLOG_TOPIC (TOPIC_ID, BLOG_ID) VALUES ($topic2, $blogId)";
  mysql_query($sql, $db);
}

// Input: blog ID, DB handle
// Output: list of topic IDs for this blog
function getBlogTopics($blogId, $db) {
  $sql = "SELECT TOPIC_ID FROM PRIMARY_BLOG_TOPIC WHERE BLOG_ID=$blogId ORDER BY TOPIC_ID ASC";
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
 * Common HTML code
 */
 
// Input: URL to update without parameters, Next page button text, previous page button text.
// Output: HTML code for page buttons.
function pageButtons ($baseUrl, $nextText = "Next Page »", $prevText = "« Previous Page") {
	$httpQuery = updateHttpQuery();
	
	$pagesize = $_REQUEST["n"];
	$offset = $_REQUEST["offset"];
	if (! $pagesize) {
		global $numResults;
		$pagesize = $numResults;
	}
	if (! $offset) $offset = 0;
	
	print "<div id=\"nextprev\">";
	
	print "<div class=\"alignright\"><h4><a title=\"Next $pagesize results\" href=\"$baseUrl/?".$httpQuery["nextPage"]."\"><b>$nextText</b></a></h4></div>";

	if ($offset - $pagesize >= 0) print "<div class=\"alignleft\"><h4><a title=\"Previous $pagesize results\" href=\"$baseUrl/?".$httpQuery["prevPage"]."\"><b>$prevText</b></a></h4></div>";
	
	print "</div>";
}

// Input: Array of blog data, user privilege ID, DB handle
// Output: HTML Edit blog form.
function editBlogForm ($blogData, $userPriv, $sliderSetting, $db) {
	$blogId = $blogData["BLOG_ID"];
	$blogName = $blogData["BLOG_NAME"];
	$blogUri = $blogData["BLOG_URI"];
	$blogDescription = $blogData["BLOG_DESCRIPTION"];
	$blogSyndicationUri = $blogData["BLOG_SYNDICATION_URI"];
	$blogAddedDate = $blogData["ADDED_DATE_TIME"];
	$blogCrawledDate = $blogData["CRAWLED_DATE_TIME"];
	$blogStatusId = $blogData["BLOG_STATUS_ID"];
	$blogSocialAccount = getBlogSocialAccount(1, $blogId, $db);
	$blogtopics = getBlogTopics($blogId, $db);
	$blogStatus = ucwords(blogStatusIdToName ($blogStatusId, $db));
	
	print "<div class=\"ss-entry-wrapper\">
	<div class=\"post-header\">$blogId | <a class=\"red-title\" href=\"$blogUri\" target=\"_blank\">$blogName</a> | $blogStatus | $blogAddedDate</div>
	<div class=\"ss-div-button\">";
	if ($sliderSetting == "open") {
		print "<div class=\"arrow-up\" title=\"Show Info\"></div>
		</div>
		<div class=\"ss-slide-wrapper\" style=\"display: block;\">";
	}
	else {
		print "<div class=\"arrow-down\" title=\"Show Info\"></div>
		</div>
		<div class=\"ss-slide-wrapper\">";
	}
	print "
	<br />
	<h3>General Information</h3>
	<form method=\"POST\">
	<input type=\"hidden\" name=\"step\" value=\"edit\" />
	<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />
	<p>Added Date: $blogAddedDate</p>
	<p>Last Crawl Date: $blogCrawledDate</p>
	<p>Blog Name: <input type=\"text\" name=\"blogName\" size=\"40\" value=\"".htmlspecialchars($blogName, ENT_QUOTES)."\"/></p>\n
	<p><a href=\"$blogUri\" target=\"_blank\">Blog URL:</a> <input type=\"text\" name=\"blogUri\" size=\"55\" value=\"".htmlspecialchars($blogUri, ENT_QUOTES)."\" /><br /><span class=\"subtle-text\">(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)</span></p>
	<p><a href=\"$blogSyndicationUri\" target=\"_blank\">Blog Syndication URL:</a> <input type=\"text\" name=\"blogSyndicationUri\" size=\"55\" value=\"".htmlspecialchars($blogSyndicationUri, ENT_QUOTES)."\" /><br /><span class=\"subtle-text\">(RSS or Atom feed. Must start with \"http://\", e.g., <em>http://feeds.feedburner.com/blogname/</em>.)</span></p>
	<p>Blog Description:<br /><textarea name=\"blogDescription\" rows=\"5\" cols=\"55\">$blogDescription</textarea></p>\n
	<p>Blog Topics: <select name='topic1'>\n
	<option value='-1'>None</option>\n";
	$topicList = getTopicList(true, $db);
	while ($row = mysql_fetch_array($topicList)) {
		print "<option value='" . $row["TOPIC_ID"] . "'";
		if ($row["TOPIC_ID"] == $blogtopics[0]) {
			print " selected";
		}
		print ">" . $row["TOPIC_NAME"] . "</option>\n";
	}
	print "</select>&nbsp;<select name='topic2'>\n
	<option value='-1'> None</option>\n";
	$topicList = getTopicList(true, $db);
	while ($row = mysql_fetch_array($topicList)) {
		print "<option value='" . $row["TOPIC_ID"] . "'";
		if ($row["TOPIC_ID"] == $blogtopics[1]) {
			print " selected";
		}
		print ">" . $row["TOPIC_NAME"] . "</option>\n";
	}
	print "</select></p>\n
	<p>Blog Status: <select name='blogStatus'>\n";
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
	print "</select></p>\n";
	if ($userPriv > 0) {
		$authorList = getAuthorList ($blogId, $db);
		if (mysql_num_rows($authorList) != 0) {
			print "<div class=\"toggle-button\">Administer Authors</div>
			<div id=\"padding-content\" class=\"ss-slide-wrapper\">";
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
	print "<h3>Social Networks</h3>
	<p>Blog Twitter handle <span class=\"subtle-text\">(Optional)</span>: <input type=\"text\" name=\"twitterHandle\" size=\"40\" value=\"".$blogSocialAccount["SOCIAL_NETWORKING_ACCOUNT_NAME"]."\"/></p>\n
	<input class=\"ss-button\" name=\"editBlog\" type=\"submit\" value=\"Edit blog\" /> <input class=\"ss-button\" name=\"crawlBlog\" type=\"submit\" value=\"Scan for new posts\" />\n
	</form>\n
	</div>
	</div>
	<hr />";
}

// Input: Step of the editing process, user ID, user Privilege, DB Handle
// Action: Check and edit submitted blog data.
function confirmEditBlog ($step, $userId, $userPriv, $db) {
	if ($step) {
		$blogId = $_REQUEST["blogId"];
		$blogName = $_REQUEST["blogName"];
		$blogUri = $_REQUEST["blogUri"];
		$blogSyndicationUri = $_REQUEST["blogSyndicationUri"];
		$blogDescription = $_REQUEST["blogDescription"];
		$topic1 = $_REQUEST["topic1"];
		$topic2 = $_REQUEST["topic2"];
		$authorId = $_REQUEST["authorId"];
		$authorUserId = $_REQUEST["authorUserId"];
		$blogStatusId = $_REQUEST["blogStatus"];
		$twitterHandle = $_REQUEST["twitterHandle"];
		$oldBlogName = getBlogName($blogId, $db);
		$errors = checkBlogData($blogId, $blogName, $blogUri, $blogSyndicationUri, $blogDescription, $blogStatusId, $topic1, $topic2, $twitterHandle, $userId, $db);
		
		// If user is requesting a blogUri or blogsyndicationuri change, ensure that they own the new url
		$origBlogSyndicationUri = getBlogSyndicationUri($blogId, $db);
		$origBlogUri = getBlogUri($blogId, $db);
	
		// If blog URL or syndication URL have changed, we need to re-verify the claim to the blog (the author's ability to write to it)
		if ($errors == NULL && $step == "edit" && $userPriv == 0 && ($origBlogSyndicationUri !== $blogSyndicationUri || $origBlogUri != $blogUri)) {
			$claimToken = retrieveVerifiedClaimToken ($blogId, $userId, $db);
			clearClaimToken($blogId, $userId, $claimToken, $db);
	
			$claimToken = retrievePendingClaimToken ($blogId, $userId, $db);
			if ($claimToken == null) {
				$claimToken = generateClaimToken();
				storeClaimToken($claimToken, $blogId, $userId, $db);
			}
			
			displayBlogClaimToken($claimToken, $blogId, $displayName, $db);
			return;
		}
		elseif ($_REQUEST["crawlBlog"]) {
			$blog = array("syndicationuri"=>$blogSyndicationUri, "id"=>$blogId, "name"=>$blogName);
			print crawlBlogs($blog, $db);
		}
		elseif ($step == "confirmed" || ($errors == NULL && $step == "edit")) {
			editBlog ($blogId, $blogName, $blogUri, $blogSyndicationUri, $blogDescription, $blogStatusId, $topic1, $topic2, $db);
			
			if ($twitterHandle) {
				addBlogSocialAccount($twitterHandle, 1, $blogId, $db);
			}
			else {
				removeBlogSocialAccount(1, $blogId, $db);
			}
			
			if ($authorId) {
				foreach ($authorId as $key => $authorId) {
					$authorUserId = $authorUserId[$key];
					editAuthor ($authorId, $authorUserId, $db);
				}
			}
			print "<p class=\"ss-successful\">$blogName (ID $blogId) has been updated.</p>"; 
		}
		elseif ($errors != NULL && $step == "edit") {
			print "<p>$oldBlogName (ID $blogId):</p>$errors";
			if ($userPriv > 0) {
				print "<form class=\"margin-bottom\" method=\"POST\">
				<input type=\"hidden\" name=\"step\" value=\"confirmed\" />
				<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />
				<input type=\"hidden\" name=\"blogName\" value=\"".htmlspecialchars($blogName, ENT_QUOTES)."\" />
				<input type=\"hidden\" name=\"blogUri\" value=\"".htmlspecialchars($blogUri, ENT_QUOTES)."\" />
				<input type=\"hidden\" name=\"blogSyndicationUri\" value=\"".htmlspecialchars($blogSyndicationUri, ENT_QUOTES)."\" />
				<input type=\"hidden\" name=\"blogDescription\" value=\"".htmlspecialchars($blogDescription, ENT_QUOTES)."\" />
				<input type=\"hidden\" name=\"twitterHandle\" value=\"".htmlspecialchars($twitterHandle, ENT_QUOTES)."\" />
				<input type=\"hidden\" name=\"authorId\" value=\"$authorId\" />
				<input type=\"hidden\" name=\"authorUserId\" value=\"$authorUserId\" />
				<input type=\"hidden\" name=\"topic1\" value=\"$topic1\" />
				<input type=\"hidden\" name=\"topic2\" value=\"$topic2\" />
				<input type=\"hidden\" name=\"blogStatus\" value=\"$blogStatusId\" />
				<input type=\"hidden\" name=\"crawl\" value=\"$crawl\" />
				<p>There has been an error, are you sure you want to apply these changes?</p>
				<input class=\"ss-button\" name=\"confirm\" type=\"submit\" value=\"Confirm\" />
				</form>";
			}
		} 
		else if ($step == "verify") {
			$result = verifyClaim($blogId, $userId, $blogUri, $blogSyndicationUri, $db);
		
			if ($result === "no-claim") {
				print "<p class=\"ss-error\">There has been a problem retrieving your claim token.</p>";
				return;
			} 
			else if ($result == "verified") {
				$claimToken = getClaimToken($blogId, $userId, $db);
				$success = markClaimTokenVerified($blogId, $userId, $claimToken, $db);
				if (! $success) {
					print "<p class=\"ss-error\">Failed to update database with your claim token.</p>";
					return;
				}
				else {
					editBlog ($blogId, $blogName, $blogUri, $blogSyndicationUri, $blogDescription, $blogStatusId, $topic1, $topic2, $db);
					
					if ($twitterHandle) {
						addBlogSocialAccount($twitterHandle, 1, $blogId, $db);
					}
					else {
						removeBlogSocialAccount(1, $blogId, $db);
					}
					
					if ($authorId) {
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
				$claimToken = getClaimToken($blogId, $userId, $db);
				print "<p class=\"ss-error\">Your claim token ($claimToken) was not found on your blog and/or your syndication feed.</p>\n";
				displayBlogClaimToken($claimToken, $blogId, $displayName, $db);
				return;
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
	
	if ($blogId) {
		// blog exists? need blog id!
		$blogStatus = getBlogStatusId($blogId, $db);
		if ($blogStatus == null) {
			return $result .= "<p class=\"ss-error\">No such blog $blogId.</p>";
		}
	}
	
	$oldBlogName = getBlogName($blogId, $db);
	
	if ($userId) {
		// if not logged in as an author or as admin, fail
		if (! canEdit($userId, $blogId, $db)) {
			$result .= "<p class=\"ss-error\">You don't have editing privileges for $oldBlogName.</p>";
		}
		
		$userPriv = getUserPrivilegeStatus($userId, $db);
		if ($userPriv == 0 && ($blogStatusId =! 0 || $blogStatusId =! 3)) {
			$result .= "<p class=\"ss-error\">You don't have editing privileges for set this status.</p>";
		}
	}
  
  // check that there is a name
  if ($blogname == null) {
	  $result .= "<p class=\"ss-error\">Name field is required.</p>";
  }
	
	if (! $blogsyndicationuri) {
		$result .= "<p class=\"ss-error\">Syndication URL field is required.</p>";
	}
	else {
		// check that syndication feed is parseable
		$feed = getSimplePie($blogsyndicationuri);
		if ($feed->get_type() == 0) {
			$result .= "<p class=\"ss-error\">Unable to parse feed at $blogsyndicationuri. Are you sure it is Atom or RSS?</p>";
		}
	}

	if (! $blogurl) {
		$result .= "<p class=\"ss-error\">URL field is required.</p>";
	}
  // check that blog URL is fetchable
  elseif (! uriFetchable($blogurl)) {
    $result .= "<p class=\"ss-error\">Unable to fetch the contents of your blog at $blogurl. Did you remember to put \"http://\" before the URL when you entered it? If you did, make sure your blog page is actually working, or <a href='/contact-us/'>contact us</a> to ask for help in resolving this problem.</p>";
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
function editBlog ($blogId, $blogName, $blogUrl, $blogSyndicationUrl, $blogDescription, $blogStatusId, $topic1, $topic2, $db) {
	
	// escape stuff
  $blogName = mysql_real_escape_string($blogName);
  $blogDescription = mysql_real_escape_string($blogDescription);
	$blogUrl = mysql_real_escape_string($blogUrl);
	$blogSyndicationUrl = mysql_real_escape_string($blogSyndicationUrl);
	
	// update easy data
	$sql = "UPDATE BLOG SET BLOG_NAME='$blogName', BLOG_URI='$blogUrl', BLOG_SYNDICATION_URI='$blogSyndicationUrl', BLOG_DESCRIPTION='$blogDescription', BLOG_STATUS_ID='$blogStatusId' WHERE BLOG_ID=$blogId";
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
function checkPostData($postId, $postTitle, $postSummary, $postUrl, $userId, $displayname, $postDate, $addedDate, $db) {

  // if not logged in as an author or as admin, fail
  if (! canEdit($userId, $blogId, $db)) {
    $result .= "<p class=\"ss-error\">$displayname does not have editing privileges to administer posts.</p>";
  }

  // user exists? active (0)?
  $userStatus = getUserStatus($userId, $db);
  if ($userStatus == null) {
    $result .= "<p class=\"ss-error\">No such user $displayname.</p>";
  }
  if ($userStatus != 0) {
    $result .= "<p class=\"ss-error\">User $displayname is not active; could not update blog info.</p>";
  }
	
  $postStatus = getPostStatusId($postId, $db);
  if ($postStatus == null) {
    $result .= "<p class=\"ss-error\">No such post $postId.</p>";
  }
  
  // check that there is a name
  if ($postTitle == null) {
	  $result .= "<p class=\"ss-error\">You need to submit a title for this post.</p>";
  }

  // check that blog URL is fetchable
  if (! uriFetchable($postUrl)) {
    $result .= ("<p class=\"ss-error\">Unable to fetch the contents of this post at $postUrl. Did you remember to put \"http://\" before the URL when you entered it? If you did, make sure your blog page is actually working, or <a href='/contact-us/'>contact us</a> to ask for help in resolving this problem.</p>");
  }
	
	if (! preg_match("/\d+-\d+-\d+ \d+:\d+:\d+/", $postDate)) {
		$result .= "<p class=\"ss-error\">Post publication date is not a valid timestamp.</p>";
	}
	
	if (! preg_match("/\d+-\d+-\d+ \d+:\d+:\d+/", $addedDate)) {
		$result .= "<p class=\"ss-error\">Added date is not a valid timestamp.</p>";
	}
  return $result;
}

// Input: blog ID, blog name, blog URI, blog syndication URI, blog description, first main topic, other main topic, DB handle
// Action: edit blog metadata
function editPost ($postId, $postTitle, $postUrl, $postSummary, $postStatusId, $userId, $displayName, $postDate, $addedDate, $db) {
	
	// escape stuff
  $postTitle = mysql_real_escape_string($postTitle);
  $postSummary = mysql_real_escape_string($postSummary);
	$postUrl = mysql_real_escape_string($postUrl);

	$sql = "UPDATE BLOG_POST SET BLOG_POST_TITLE='$postTitle', BLOG_POST_URI='$postUrl', BLOG_POST_SUMMARY='$postSummary', BLOG_POST_STATUS_ID=$postStatusId, BLOG_POST_DATE_TIME='$postDate', BLOG_POST_INGEST_DATE_TIME='$addedDate' WHERE BLOG_POST_ID=$postId";
	mysql_query($sql, $db);

}

// Input: user ID, user name, user status, user privilege status, user email, administrator id, administrator privilege, administrator display name, WordPress DB handle, DB handle
// Action: check user metadata
// Return: error message or null
function checkUserData($userID, $userName, $userStatus, $userEmail, $userPrivilege, $userId, $userPriv, $displayname, $db) {

  // if not logged in as an author or as admin, fail
  if ($userPriv < 2) {
    $result .= "<p class=\"ss-error\">$displayname does not have editing privileges to administrate users.</p>";
  }

  // user exists? active (0)?
  $checkUserStatus = getUserStatus($userId, $db);
  if ($checkUserStatus == null) {
    $result .= "<p class=\"ss-error\">No such user $displayname.</p>";
  }
  if ($checkUserStatus != 0) {
    $result .= "<p class=\"ss-error\">User $displayname is not active; could not update user info.</p>";
  }
  
  // check that there is a name
  if ($userName == null) {
	  $result .= "<p class=\"ss-error\">You need to submit a name for the user.</p>";	  
  }
	
	// check if the email is valid
	if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
		$result .= "<p class=\"ss-error\">The submited e-mail is not valid.</p>";
	}
  
  // Check that there is a user status
  if ($userStatus == null) {
		$result .= ("<p class=\"ss-error\">You need to submit a user status.</p>");
  }
	
	// Check that there is a user privilige
  if ($userPrivilege == null) {
		$result .= ("<p class=\"ss-error\">You need to submit a user privilege status.</p>");
  }
	
  return $result;
}

// Input: user ID, user name, user status, user privilege status, user email, old user name, WordPress DB handle, DB handle
// Action: edit user metadata
function editUser ($userID, $userName, $userStatus, $userEmail, $userPrivilege, $oldUserName, $wpdb, $db) {
	
	// escape stuff
  $userName = mysql_real_escape_string($userName);
	$userEmail = mysql_real_escape_string($userEmail);
	
	// update Wordpress name
	$wpdb->update( $wpdb->users, array('user_login' => $userName, 'user_nicename' => $userName, 'display_name' => $userName), array('user_login' => $oldUserName) );
	
	// update easy data
  $sql = "UPDATE USER SET USER_NAME='$userName', USER_PRIVILEGE_ID='$userPrivilege', USER_STATUS_ID='$userStatus', EMAIL_ADDRESS='$userEmail' WHERE USER_ID=$userID";
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

// Input: user ID, DB handle
// Return: name of this user, or null
function getDisplayName($userId, $db) {
  $sql = "SELECT DISPLAY_NAME FROM USER WHERE USER_ID=$userId";
  $results = mysql_query($sql, $db);
  if ($results == null || mysql_num_rows($results) == 0) {
    return null;
  }
  $row = mysql_fetch_array($results);
  return $row["DISPLAY_NAME"];
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
function titleToCitations($title) {
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
	
	$doc = new DOMDocument();
  @$doc->loadHTML($result);
	$xml = simplexml_import_dom($doc);
	
	// Search for Z3988 class and return all its contents
	$resultLinks = $xml->xpath("//a[text()='[xml]']/@href");

	$citations = array();
	foreach ($resultLinks as $uri) {
		$articleData = NULL;
		$ch = curl_init();    // initialize curl handle
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
	
	// Search for Z3988 class and return all its contents
	$getCitations = $xml->xpath('//*[@class=\'Z3988\']');
	// Parse citation
	foreach ($getCitations as $data) {
		$values = urldecode((string)$data->attributes()->title);
		preg_match("/(?<=bpr3.included=)./", $values, $rbInclude);
		preg_match("/(?<=ss.included=)./", $values, $ssInclude);
		if (($rbInclude[0] == 1 && $ssInclude[0] == NULL) || ($ssInclude[0] == 1)) {
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
	$topic = preg_split("/[,\/]/", $topics[0]);
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
	
	if (! $articleData["rfr_id"]) $articleData["rfr_id"] = "info:sid/scienceseeker.org";
	if (! $articleData["id_type"]) $articleData["id_type"] = "other";
	
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
	
	if ($authors) {
		foreach ($authors as $author) {
			$sql = "SELECT ARTICLE_AUTHOR_ID FROM ARTICLE_AUTHOR WHERE ARTICLE_AUTHOR_FULL_NAME = '".mysql_real_escape_string($author["rft.au"])."' AND ARTICLE_AUTHOR_FIRST_NAME = '".mysql_real_escape_string($author["rft.aufirst"])."' AND ARTICLE_AUTHOR_LAST_NAME = '".mysql_real_escape_string($author["rft.aulast"])."'";
			$sql = str_replace("= ''", "IS NULL", $sql);
			$result = mysql_query($sql, $db);
			
			$row = mysql_fetch_array($result);
			$articleAuthorId = $row['ARTICLE_AUTHOR_ID'];
			
			if ($result == null || mysql_num_rows($result) == 0) {	
				$authorFullName = mysql_real_escape_string($author["rft.au"]);
				$authorFirstName = mysql_real_escape_string($author["rft.aufirst"]);
				$authorLastName = mysql_real_escape_string($author["rft.aulast"]);
				
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
	if (empty($xpath)) return NULL;  // this is not COinS format

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
			if ($authors[$i][$attribute]) {
				$i++;
			}
			$authors[$i][$attribute] = urldecode($elements[1]);
		}
		else {
			$values[$attribute] .= urldecode($elements[1]);
		}
	}
	$values["authors"] = $authors;
	
	// Get ID and ID type (DOI, PMID, arXiv...)
	preg_match("/(?<=info:)[^\/]+/", $values["rft_id"], $matchType);
	preg_match("/(?<=\/).+/", $values["rft_id"], $matchID);
	$values["id_type"] = $matchType[0];
	$values["id"] = $matchID[0];
	
	// Check if citation should be included
	preg_match("/(?<=bpr3.included=)./", $title, $rbInclude);
	preg_match("/(?<=ss.included=)./", $title, $ssInclude);
	$values["rbIncluded"] = $rbInclude[0];
	$values["ssIncluded"] = $ssInclude[0];

	return $values;
}

// Input: Array with citation data
// Output: COinS-format citation text for use in HTML
function generateCitation ($articleData) {
	global $rfrId;
	
	if (! $articleData["rfr_id"]) $articleData["rfr_id"] = "info:sid/$rfrId";
	if (! $articleData["id_type"]) $articleData["id_type"] = "other";
	
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
		
	if ($authors) {
		foreach ($authors as $n => $author) {
			$i = count($authors);
			if ($author["rft.aufirst"] || $author["rft.aulast"]) {
				$firstName = $author["rft.aufirst"];
				$lastName = $author["rft.aulast"];
				$builtFirstName = "";
				$citation .= "&amp;rft.au=".urlencode("$lastName $firstName")."&amp;rft.aulast=".urlencode($lastName)."&amp;rft.aufirst=".urlencode($firstName);
				
				preg_match_all("/[^\.\,\s-]+/", $firstName, $matchResult);
				foreach ($matchResult[0] as $item) {
				 $builtFirstName .= strtoupper(mb_substr($item, 0, 1, 'UTF-8').".");
				}
				$builtAuthors .= "$lastName, $builtFirstName";
				if ($n == ($i-2)) {
					$builtAuthors .= " & ";
				}
				elseif ($n != $i-1) {
					$builtAuthors .= ", ";
				}
			}
			elseif ($author["rft.au"]) {
				$citation .= "&amp;rft.au=".urlencode($author["rft.au"]);
				$builtAuthors .= $author["rft.au"];
				if ($n == ($i-2)) {
					$builtAuthors .= " & ";
				}
				elseif ($n != $i-1) {
					$builtAuthors .= ", ";
				}	
			}
		}
	}
	
	if ($ssInclude === NULL) $ssInclude = 1;
	if ($rbInclude === NULL) $rbInclude = 1;
	
	$citation .= "&rfs_dat=ss.included=$ssInclude";
	$citation .= "&rfe_dat=";
	$citation .= "bpr3.included=$rbInclude";
	
	if ($rbTags && $rbInclude) {
		$citation .= ";bpr3.tags=".urlencode(implode(",",$rbTags));
	}
	$citation .= "\">";
	
	if ($rftdate) {
		$date = "($rftdate).";
	}
	if ($rftatitle) {
		$title = "$rftatitle,";
	}
	if ($rftjtitle) {
		$journal = "$rftjtitle,";
	}
	if ($rftissue) {
		$issue = "($rftissue)";
	}
	if ($rftspage) {
		$pages = "$rftspage.";
	}
	if ($rftspage || $rftepage) {
		$pages = "$rftspage";
		if ($rftepage) {
			$pages .= "-";
		}
		$pages .= "$rftepage.";
	}
	
	if ($idtype == "doi") {
		$type = "DOI:";
		$url = "<a rev=\"review\" href=\"http://dx.doi.org/".urlencode($id)."\">$id</a>";
	}
	elseif ($idtype == "arxiv") {
		$type = "arXiv:";
		$url = "<a rev=\"review\" href=\"http://arxiv.org/abs/".urlencode($id)."\">$id</a>";
	}
	elseif ($idtype == "pmid") {
		$type = "PMID:";
		$url = "<a rev=\"review\" href=\"http://www.ncbi.nlm.nih.gov/pubmed/".urlencode($id)."\">$id</a>";
	}
	elseif ($id || $rftartnum) {
		$type = "Other:";
		if (! $id) $id = "Link";
		if ($rftartnum) $url = "<a rev=\"review\" href=\"$rftartnum\">$id</a>";
		else $url = $id;
	}
	
	$citation .= "$builtAuthors $date $title <span style=\"font-style:italic;\">$journal $rftvolume</span> $issue $pages $type $url</span>";
	
	return $citation;
}

/* Claim stuff */

// DEPRECATED
// DELETE THIS FUNCTION
function doVerifyEditClaim ($db) {
  $blogId = $_REQUEST["blogId"];
  $blogUri = $_REQUEST["blogUri"];
  $blogSyndicationUri = $_REQUEST["blogSyndicationUri"];
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
      print "Error, failed to update database.";
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

    displayEditBlogsForm("Blog $blogName edited.", $db);
    return;
  } else {
    $claimToken = getClaimToken($blogId, $userId, $db);
    print "<p>Your claim token ($claimToken) was not found on your blog and/or your syndication feed.</p>\n";
    displayBlogClaimToken($claimToken, $blogId, $displayName, $db);
  }
}

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

  displayBlogClaimToken($claimToken, $blogId, $displayName, $db);
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
			// If not in this entry of the syndication feed, go to the post and check the HTML code
			else {
				$ch = curl_init();    // initialize curl handle
				curl_setopt($ch, CURLOPT_URL, $item->get_permalink()); // set url to post to
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
				
				if (strpos($result, $claimToken) == TRUE) {
					return "verified";
				}
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
function displayBlogClaimToken($claimToken, $blogId, $displayName, $db) {
	$blogName = $_REQUEST["blogName"];
	$blogUri = $_REQUEST["blogUri"];
	$blogSyndicationUri = $_REQUEST["blogSyndicationUri"];
	$blogDescription = $_REQUEST["blogDescription"];
	$blogAddedDate = $_REQUEST["addedDate"];
	$blogCrawledDate = $_REQUEST["crawledDate"];
	$topic1 = $_REQUEST["topic1"];
	$topic2 = $_REQUEST["topic2"];
	$blogStatusId = $_REQUEST["blogStatus"];
	$twitterHandle = $_REQUEST["twitterHandle"];
	$crawl = $_REQUEST["crawl"];
	
	if (! $blogId) {
		$blogId = $_REQUEST["blogId"];
	}
	if (! $blogName) {
		$blogName = getBlogName($blogId, $db);
	}

  print "<p>To claim this blog ($blogName), we need to verify that you actually are an author of this blog. Please place the following HTML code in the <span class=\"ss-bold\">most recent</span> post on your blog. It will be invisible to readers, and you can remove it once your blog has been verified by our system.</p>\n
	<p><span class=\"ss-bold\">Claim token:</span> $claimToken</p>\n
	<p><span class=\"ss-bold\">HTML code to include:</span> &lt;p&gt;&lt;span style=\"display:none\"&gt;$claimToken&lt;/span&gt;&lt;/p&gt;\n
	<p>Once the token is displayed in a post of your site, press the button below</p> 
	<form method='POST' name='doVerifyForm'>\n
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
	<input class=\"ss-button\" name=\"submit\" type=\"submit\" value=\"Continue to the next step\" />
	</form>";
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
    print "<p class=\"ss-error\">You cannot claim this blog as your account is not currently active ($userStatus). You may <a href='/contact-us/'>contact us</a> to ask for more information.</p>\n";
    return;
  }

  if (mysql_num_rows($authorList) == 0 && $unknownAuthorId == null) {
    print "<p class=\"ss-error\">There was an error in parsing the feed of this blog. Please <a href='/contact-us/'>contact us</a> to ask for help in resolving this problem.</p>\n";
    return;
  }

  $blogStatus = getBlogStatusId($blogId, $db);
  if ($blogStatus == 2) { // rejected
    print "<p class=\"ss-error\">This blog has been rejected from the system by an editor. For more information, please <a href='/contact-us/'>contact us</a>.</p>";
    return;
  }

  if (isBlogClaimable($blogId, $db) == true) {
    print "<h2>Identify yourself as an author of $blogName</h2>\n";

    print "<form method=\"POST\">\n";
    print "<input type=\"hidden\" name=\"step\" value=\"userAuthorLinkForm\" />\n";
    print "<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />\n";

    $authorList = getAuthorList($blogId, $db);

    if (mysql_num_rows($authorList) > 0) {
      print "<p>This blog seems to have the following author(s). Please indicate which one is you (OK to choose more than one).</p>\n";
      $firstAuthor = null;
      while ($row = mysql_fetch_array($authorList)) {
				$authorId = $row["BLOG_AUTHOR_ID"];
				$authorName = $row["BLOG_AUTHOR_ACCOUNT_NAME"];
        $claimed = isAuthorClaimed($authorId, $db);
        if ($firstAuthor == null && (! $claimed)) {
          $firstAuthor = $authorName;
        }
        if ($claimed) {
          print "<p><input type=\"checkbox\" name=\"author$authorId\" disabled=\"true\"/> $authorName <span class=\"subtle-text\">(claimed)</span></p>\n";
        } else {
          print "<p><input type=\"checkbox\" name=\"author$authorId\" /> $authorName</p>\n";
        }
      }
    }
		else {
			print "<p>We couldn't find any authors in your feed.</p>";
		}
		
		print "<input class=\"ss-button\" type=\"submit\" value=\"Submit\">";
		print "</form>\n";

  } else {
    print "<p class=\"ss-error\">This blog has already been claimed. If you feel this is in error, please <a href='/contact-us/'>contact us</a>.</p>";
  }

  print "<p><hr /></p>\n";

}

function doLinkUserAndAuthor($userId, $displayName, $db) {
  global $sitename;
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
    $authorList = getAuthorList ($blogId, $db);
    $unknownAuthorId = getUnknownAuthorId($blogId, $db);

    if (mysql_num_rows($authorList) == 0 && $unknownAuthorId == null) {
      print "<p class=\"ss-error\">There was an error in parsing the feed of this blog. Please <a href='/contact-us/'>contact us</a> to ask for help in resolving this problem.</p>\n";
      return;
    }

    if ($unknownAuthorId != null && ! isAuthorClaimed($unknownAuthorId, $db) && mysql_num_rows($authorList) == 0) {
      linkAuthorToUser($unknownAuthorId, $userId, $db);
      $success = true;
    } else {
      // either there are authors and no "unknown" authors
      // or there are authors AND an "unknown" author (weird!)
      // either way, assume this person wants to link to a KNOWN author
      print "<p class=\"ss-error\">Please choose an author from the list. If your name is not on the list, it may be because you have not posted recently. Try again after a recent post. If you believe your blog has been claimed by someone else, please <a href='/contact-us/'>contact us.</a></p>\n";
      displayUserAuthorLinkForm($blogId, $userId, $displayName, $db);
    }
  }

  if ($success) {
		global $userBlogs;
    print "Congratulations, $displayName, you've claimed your blog. Click on '<a href=\"$userBlogs\">Your Blogs</a>' to edit your blog settings.<br />\n";
  }

}

/*
 * XML parser functions
 */

// SSFilter object contains search query parameters
// See SS Query API documentation on SS wiki for more information
class SSFilter
{
  // Name of filter: topic, title, summary, url, citation, has-citation, recommended-by, recommender-status, min-recommendations
  public $name;

  // Value of filter
  public $value;

  // Modifier for filter (only applicable to title, summary, url filters)
  public $modifier;

  // Type of ID to use (only applicable to citation filter)
  public $idtype;
}



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
function get_bitly_short_url($url,$login,$appkey,$format='txt') {
	global $bitlyUser;
	global $bitlyKey;
	global $bitlyApiUrl;
  $connectURL = $bitlyApiUrl.$login.'&apiKey='.$appkey.'&uri='.urlencode($url).'&format='.$format;
	
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
