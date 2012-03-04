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

  ssDbClose($cid);

  return $searchResults;

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