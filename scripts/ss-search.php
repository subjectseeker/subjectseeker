<?php

include_once "ss-globals.php";
include_once "ss-util.php";

global $dbName;
// TODO pass this back somehow, don't use a global
global $type;

// Parse search parameters which were passed in by POST
$params = discoverSearchParams();
/*
print "PARAMS:<br />";
foreach ($params as $name => $value) {
  print "$name = $value<br />";
  } */

// Display

echo "<?xml version=\"1.0\" ?>\n";
print "<subjectseeker>\n";

$searchResults = searchDB();
printResults ($searchResults);

print "</subjectseeker>\n";


/* Functions */

function discoverSearchParams() {
  $input = file_get_contents('php://input');
  if ($input === "") {
    return parseHttpParams();
  // parse PHP param
  } else {
    return parseSearchParams($input);
  }
}

function searchDB() {

  // The type can currently be "topic" or "blog". We may choose at some point
  // to add more types (for example, maybe citations?).
  global $type;

  $cid = ssDbConnect();

  if (strcasecmp($type, "topic") == 0) {
    $searchResults = searchTopics($cid);
  }

  if (strcasecmp($type, "blog") == 0) {
    $searchResults = searchBlogs($cid);
  }
	
	if (strcasecmp($type, "post") == 0) {
    $searchResults = searchPosts($cid);
  }

  ssDbClose($cid);

  return $searchResults;

}

// Input: string of text to search, filters, arrangement of results, DESC or ASC, offset
// Return: as
function findPosts ($searchQuery, $filters, $arrange, $order, $pagesize, $offset, $cid) {
	
	$tables = "BLOG_POST post, BLOG b, BLOG_AUTHOR bauthor";
	
	if ($filters) {
		$a = 1;
		$sqlFilters .= " (";
		
		foreach ($filters as $filterName => $filterValue) {
			$sqlFilters .= " (";
			
			if ($filterName == "topic") {
				$tables .= " , PRIMARY_BLOG_TOPIC btopic";
				
				$b = 1;
				foreach ($filterValue as $topicId) {
					$sqlFilters .= " post.BLOG_ID = btopic.BLOG_ID AND btopic.TOPIC_ID = $topicId";
					if (count($filterValue) != $b) {
						$sqlFilters .= " AND";
						$b++;
					}
				}
			}
			
			if ($filterName == "modifier") {
				$c = 1;
				foreach ($filterValue as $filter) {
					if ($filter == "citation") {
						$sqlFilters .= " post.BLOG_POST_HAS_CITATION = 1";
					}
					if ($filter == "editorsPicks") {
						$tables .= " , RECOMMENDATION rec, PERSONA pers, USER user";
						$sqlFilters .= "post.BLOG_POST_ID = rec.BLOG_POST_ID AND user.USER_ID = pers.USER_ID AND rec.PERSONA_ID = pers.PERSONA_ID AND user.USER_PRIVILEGE_ID > 0";
					}
					if (count($filterValue) != $c) {
						$sqlFilters .= " AND";
						$c++;
					}
				}
			}
		
			if ($filterName == "searchBy") {
				$d = 1;
				foreach ($filterValue as $filter) {
					if ($filter == "title") {
						$column = "post.BLOG_POST_TITLE";
					}
					if ($filter == "summary") {
						$column = "post.BLOG_POST_SUMMARY";
					}
					if ($filter == "url") {
						$column = "post.BLOG_POST_URI";
					}
					$sqlFilters .= " $column LIKE '%".mysql_escape_string($searchQuery)."%'";
					if (count($filterValue) != $d) {
						$sqlFilters .= " OR";
						$d++;
					}
				}
			}
			$sqlFilters .= " )";
			if (count($filters) != $a) {
				$sqlFilters .= " AND";
				$a++;
			}
		}
		$sqlFilters .= " ) AND ";
	}
	
	$sql = "SELECT DISTINCT post.*, b.BLOG_NAME, bauthor.BLOG_AUTHOR_ACCOUNT_NAME FROM $tables WHERE $sqlFilters b.BLOG_ID = post.BLOG_ID AND bauthor.BLOG_ID = post.BLOG_ID AND bauthor.BLOG_AUTHOR_ID = post.BLOG_AUTHOR_ID ORDER BY post.$arrange $order LIMIT $pagesize OFFSET $offset";
	
	$posts = array();
	
	$results =  mysql_query(mysql_escape_string($sql), $cid);
	
	while ($row = mysql_fetch_array($results)) {
  // Build post object to return
		$post["postId"] = $row["BLOG_POST_ID"];
		$post["blogId"] = $row["BLOG_ID"];
		$post["blogName"] = $row["BLOG_NAME"];
		$post["title"] = $row["BLOG_POST_TITLE"];
		$post["summary"] = $row["BLOG_POST_SUMMARY"];
		$post["authorId"] = $row["BLOG_AUTHOR_ID"];
		$post["authorName"] = $row["BLOG_AUTHOR_ACCOUNT_NAME"];
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


// Input: database handle ($cid)
// Output: list of all topics; if the global $params specifies toplevel=1, then only return toplevel topics; otherwise, return them all (probably unwise to do that at this point -- it would be a huge number!)
function searchPosts($cid) {
  global $params;
	$filters = NULL;
	$topicNames = array();

  if ($params != null) {
    foreach ($params as $name => $value) {
      if (strcasecmp($name, "topic") == 0) {
        if (is_array($value)) {
          foreach ($value as $oneValue) {
            array_push ($topicNames, $oneValue);
          }
        } else {
          array_push ($topicNames, $value);
        }
				$filters["topic"] = topicNamesToIds($topicNames, $cid);
      }
			if (strcasecmp($name, "search") == 0) {
				if (is_array($value)) {
					foreach ($value as $oneValue) {
						$searchQuery = $oneValue;
					}
				}
				else {
					$searchQuery = $value;
				}
			}
			if (strcasecmp($name, "searchBy") == 0) {
				if (is_array($value)) {
					foreach ($value as $oneValue) {
						$filters[$name][] = $oneValue;
					}
				} else {
					$filters[$name][] = $value;
				}
			}
			if (strcasecmp($name, "modifier") == 0) {
				if (is_array($value)) {
					foreach ($value as $oneValue) {
						$filters[$name][] = $oneValue;
					}
				} else {
					$filters[$name][] = $value;
				}
			}
    }
  }

  return findPosts($searchQuery, $filters, "BLOG_POST_DATE_TIME", "DESC", 100, 0, $cid);

}

// Input: database handle ($cid)
// Output: list of all topics; if the global $params specifies toplevel=1, then only return toplevel topics; otherwise, return them all (probably unwise to do that at this point -- it would be a huge number!)
function searchTopics($cid) {
  global $params;
  global $dbName;

  $toplevel = 0;

  foreach ($params as $name => $value) {
    if ($name === "toplevel") {
      $toplevel = $value;
    }
  }

  return getTopicList($toplevel, $cid);

}

// Input: database handle ($cid)
// Output: list of blogs, including some data for each blog, which match the global $params specified
function searchBlogs($cid) {
  global $params;
  global $dbName;

  $topicNames = array();
  $blogIds = array();
  $filtered = false;

  // Go through the global $params and find which topics we are
  // interested in. Note that we may have something like topic=Biology or
  // we may have topic=Biology&tioopic=Chemistry, so we have to handle
  // either a single string topic, or a list/array of topics
  if ($params != null) {
    foreach ($params as $name => $value) {
      if (strcasecmp($name, "topic") == 0) {
        $filtered = true;
        if (is_array($value)) {
          foreach ($value as $oneValue) {
            array_push ($topicNames, $oneValue);
          }
        } else {
          array_push ($topicNames, $value);
        }
      }
    }
  }

  // Here, we might at some point look for some other things to filter
  // blogs on. Right now we are only filtering on "topic" parameter. We
  // might have other filters later, like author, citation status...


  // if $filtered is true, it means that we found some topics (or something else) that we want to filter blog retrieval on.
  if (! $filtered) {
    $blogIds = getBlogIds($cid);
  } else {
    // Here, we know we want to filter blogs on something.
    // Right now we just assume we are filtering on topic.
    // If we are filtering on something else, we'll need to do some more
    // work here. This is the place where things might get out of hand if we
    // end up having a lot of if/else (filter by this, not that, but also
    // this...) so if we start filtering on a lot of things, we will
    // need to restructure this code.
    $topicIds = topicNamesToIds($topicNames, $cid);
    $blogIds = topicIdsToBlogIds($topicIds, $cid);
  }

  if (count($blogIds) == 0) {
    return null;
  }

  // We have gotten a list of all the IDs of all the blogs we are interested
  // in -- that was the hard part. Now just use a utility method to
  // populate an array with more useful information per blog and return that.
  return blogIdsToBlogData($blogIds, $cid);
}

function printResults($searchResults) {
  global $type;

  if ($searchResults == null) {
    print "<message>No results found for your search parameters.</message>\n";
    return;
  }

  if (strcasecmp($type, "topic") == 0) {

    print "  <topics>\n";
    while ($row = mysql_fetch_array($searchResults)) {
      printTopic($row);
    }
    print " </topics>\n";
  }
	
	if (strcasecmp($type, "post") == 0) {

    print "  <posts>\n";
		foreach ($searchResults as $post) {
    	printPost($post);
		}
    print " </posts>\n";
  }

  if (strcasecmp($type, "blog") == 0) {

    print "  <blogs>\n";
    while ($row = mysql_fetch_array($searchResults)) {
      printBlog($row);
    }
    print " </blogs>\n";

    // Here we might eventually want to return more things. Remember, right
    // now we can only search for "blogs" or "topics." If we end up searching
    // for citations, we might want to return a list of citation results here.
  }

}

function printTopic($row) {
  $topicName = $row["TOPIC_NAME"];
  $toplevel = $row["TOPIC_TOP_LEVEL_INDICATOR"];
  if ($toplevel == 1) {
    $toplevel = "true";
  } else {
    $toplevel = "false";
  }
  echo ("   <topic toplevel=\"$toplevel\">$topicName</topic>\n");
}

// Input: mysql row with info about a post
// Action: print some XML with that blog's info
function printPost($row) {
  $postTitle = sanitize( $row["title"] );
  $postId = $row["postId"];
  $postUri = sanitize( $row["uri"] );
  $postSummary = sanitize( $row["summary"] );
	$blogName = sanitize( $row["blogName"] );
	$authorName = sanitize( $row["authorName"] );

  print "   <post>\n
	<id>$postId</id>\n
	<blog>$blogName</blog>\n
	<title>$postTitle</title>\n
	<author>$authorName</author>\n
	<uri>$postUri</uri>\n
	<summary>$postSummary</summary>\n   </post>\n";
}

// Input: mysql row with info about a Blog
// Action: print some XML with that blog's info
function printBlog($row) {
  $blogName = sanitize( $row["BLOG_NAME"] );
  $blogId = $row["BLOG_ID"];
  $blogUri = sanitize( $row["BLOG_URI"] );
  $blogSyndicationUri = sanitize( $row["BLOG_SYNDICATION_URI"] );
  $blogDescription = sanitize( $row["BLOG_DESCRIPTION"] );

  print "   <blog><name>$blogName</name><id>$blogId</id><uri>$blogUri</uri><syndicationuri>$blogSyndicationUri</syndicationuri>";
  if ($blogDescription != null && $blogDescription !== "") {
    print "<description>$blogDescription</description>";
  }
  print "</blog>\n";
}

// And again we might want a separate printCitation function here
// (or printAuthor or whatever else we are allowing the user to search for)

?>