#!/usr/bin/php

<?php

include_once "../ss-globals.php";
include_once "../ss-util.php";

print "Search API test (searchAPI.php)...\n";

// connect to DB
$db = ssDbConnect();

global $searchUrl;

// Search with no parameters specified
print "No parameters:.....";
$result = doAPISearch("");
if (! preg_match("/No search type specified/", $result)) {
  print "Failure\n";
} else {
  print "Success\n";
}

// Search with unknown type
print "Unknown type:.....";
$result = doAPISearch("?type=foobar");
if (! preg_match("/Unknown type: foobar/", $result)) {
  print "Failure\n";
} else {
  print "Success\n";
}

// Search with unknown filter
print "Unknown filter:.....";
$result = doAPISearch("?type=blog&filter0=foobar");
if (! preg_match("/Unrecognized filter: foobar/", $result)) {
  print "Failure\n";
} else {
  print "Success\n";
}

// Search with unknown value
print "Unknown value:.....";
$result = doAPISearch("?type=topic&filter0=toplevel&value0=foobar");
if (! preg_match("/Unrecognized value for filter: /", $result)) {
  print "Failure\n";
} else {
  print "Success\n";
}

// Posts in particular blog, but incorrect identifier
print "Bad identifier:.....";
$result = doAPISearch("?type=post&filter0=blog&value0=foo&modifier0=identifier");
print "\n$result\n";
if (isPostResult($result)) {
  // actually we should get a proper error message here
  print "Failure\n";
} else {
  print "Success\n";
}


// Top level topics (check for the last known top level topic and hope that
// catches a problem with earlier ones -- too hard to check for
// every single one as they will change!
print "Top level topics:.....";
$result = doAPISearch("?type=topic&filter0=toplevel&value0=true");
if (! preg_match("/topic toplevel=\"true\".*Veterinary Medicine/", $result)) {
  print "Failure\n";
} else {
  print "Success\n";
}

// Topic = Biology (blog)
print "Specify blog topic:.....";
$result = doAPISearch("?type=blog&filter0=topic&value0=Biology");
// TODO: test for topics in blogs once that's enabled
if (isBlogResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}

// Topic = biology (post)
print "Specify post topic:.....";
$result = doAPISearch("?type=post&filter0=topic&value0=biology");
if (isPostResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}

// Blog ID
print "Specify blog ID:.....";
$result = doAPISearch("?type=blog&filter0=identifier&value0=10");
//print "\n$result\n";
if (isBlogResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}

// Blog post title
print "Specify blog post title:.....";
$result = doAPISearch("?type=post&filter0=title&value0=the&modifier0=some");
//print "\n$result\n";
if (isPostResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}

// Blog title
print "Specify blog title:.....";
$result = doAPISearch("?type=blog&filter0=title&value0=the&modifier0=some");
//print "\n$result\n";
if (isBlogResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}

// Blog summary
print "Specify blog summary:.....";
$result = doAPISearch("?type=blog&filter0=summary&value0=about&modifier0=some");
//print "\n$result\n";
if (isBlogResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}

// Blog post summary
print "Specify blog post summary:.....";
$result = doAPISearch("?type=post&filter0=summary&value0=the&modifier0=some");
//print "\n$result\n";
if (isPostResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}

// Blog URL
print "Specify blog URL:.....";
$result = doAPISearch("?type=blog&filter0=url&value0=dogzombie&modifier0=some");
//print "\n$result\n";
if (isBlogResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}

// Blog post URL
print "Specify blog post URL:.....";
$result = doAPISearch("?type=post&filter0=url&value0=dogzombie&modifier0=some");
//print "\n$result\n";
if (isPostResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}

// Posts in particular blog
print "Specify blog to search inside:.....";
$result = doAPISearch("?type=post&filter0=blog&value0=1&modifier0=identifier");
//print "\n$result\n";
if (isPostResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}

// DOI
print "Specify DOI:.....";
$result = doAPISearch("?type=post&filter0=citation&modifier0=doi&value0=10.1162/jocn.2006.18.11.1947");
//print "\n$result\n";
if (isPostResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}

// Any citation
print "Specify has-citation:.....";
$result = doAPISearch("?type=post&filter0=has-citation");
//print "\n$result\n";
if (isPostResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}

// Is recommended
print "Specify is-recommended:.....";
$result = doAPISearch("?type=post&filter0=is-recommended");
//print "\n$result\n";
if (isPostResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}


// Is recommended by an editor
print "Specify is recommended by editor:.....";
$result = doAPISearch("?type=post&filter0=recommender-status&value0=editor");
//print "\n$result\n";
if (isPostResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}

// At least 1 recommendation
print "Specify at least 1 recommendation:....";
$result = doAPISearch("?type=post&filter0=min-recommendations&value0=1");
//print "\n$result\n";
if (isPostResult($result)) {
  print "Success\n";
} else {
  print "Failure\n";
}



// Parse the result of the search
//$doc = new DOMDocument();
//@$doc->loadHTML($result);
//$xml = simplexml_import_dom($doc);
//$resultLinks = $xml->xpath("//a[text()='[xml]']/@href");

//print "\n$result\n";

// clean up - we're done
ssDbClose($db);

// Input: parameters to pass to search function (string)
// Output: XML document (string) resulting from search with specified params
function doAPISearch($params) {
  global $searchUrl;

  $ch = curl_init();    // initialize curl handle
  curl_setopt($ch, CURLOPT_URL,$searchUrl . $params);
  curl_setopt($ch, CURLOPT_FAILONERROR, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
  curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 4s
  curl_setopt($ch, CURLOPT_POST, 0); // set GET method
  // curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

  // Retrieve result
  $result = curl_exec($ch);
  // TODO return error if error
  // $cerror = curl_error($ch);

  return $result;
}

// Input: search results as string
// Output: true if results appear to be Atom, false otherwise
function isPostResult($result) {
  return (preg_match("/entry xml:lang=\"en\"/", $result)
          && preg_match("/<title/", $result)
          && preg_match("/<ss:community/", $result));
}


// Input: search results as string
// Output: true if results appear to be list of blogs
function isBlogResult($result) {
  return preg_match("/<blog><name>/", $result);
}

?>
