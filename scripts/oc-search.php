<?php

include_once "ss-globals.php";
include_once "ss-util.php";

global $dbName;
// TODO pass this back somehow, don't use a global
global $type;

// Parse search parameters which were passed in by POST
$params = parseSearchParams(file_get_contents('php://input'));

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

function searchDB() {

  global $type;

  $cid = ssDbConnect();

  if (strcasecmp($type, "topic") == 0) {
    $searchResults = searchTopics($cid);
  }

  if (strcasecmp($type, "blog") == 0) {
    $searchResults = searchBlogs($cid);
  }

  ssDbClose($cid);

  return $searchResults;

}

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

function searchBlogs($cid) {
  global $params;
  global $dbName;

  $topicNames = array();
  $blogIds = array();
  $filtered = false;

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

  if (! $filtered) {
    $blogIds = getBlogIds($cid);
  } else {
    $topicIds = topicNamesToIds($topicNames, $cid);
    $blogIds = topicIdsToBlogIds($topicIds, $cid);
  }

  if (count($blogIds) == 0) {
    return null;
  }

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

  if (strcasecmp($type, "blog") == 0) {

    print "  <blogs>\n";
    while ($row = mysql_fetch_array($searchResults)) {
      printBlog($row);
    }
    print " </blogs>\n";

    // TODO Authors
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

?>