#!/usr/local/bin/php

<?php

/*
Copyright © 2010–2012 Christopher R. Maden, Jessica Perry Hekman and Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

include_once "../globals.php";
include_once "../scripts/util.php";
global $crawlerLock;

$fp = fopen($crawlerLock, "r+");

if (flock($fp, LOCK_EX|LOCK_NB)) { 
	
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
	
		foreach ($feed->get_items(0, 50) as $item) {
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
	
	flock($fp, LOCK_UN);

} else {
	echo "Script already running.\n";
}

fclose($fp);
?>
