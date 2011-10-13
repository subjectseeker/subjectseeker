#!/usr/local/bin/php

<?php

include_once "ss-globals.php";
include_once "ss-util.php";

$limit = 200; // TODO put in ss-globals

$db = ssDbConnect();

$blogs = getSparseBlogs($db, $limit);
foreach ($blogs as $blog) {
  $blogUri = $blog["uri"];
  $blogId = $blog["id"];

  print "RSS: $blogUri\n";
  $feed = getSimplePie($blogUri);
  if ($feed->error()) {
    print "ERROR: $blogUri (ID $blogId): " . $feed->error() . "\n";
  }

  foreach ($feed->get_items() as $item) {
    addSimplePieItem($item, $feed->get_language(), $blogId, $db);
    $item->__destruct(); // Do what PHP should be doing on its own.
    unset ($item);
  }
  markCrawled($blogId, $db);

  $feed->__destruct(); // Do what PHP should be doing on its own.
  unset($feed);
}

// clean up - we're done
ssDbClose($db);
?>
