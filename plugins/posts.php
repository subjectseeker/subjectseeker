<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function displayFeed($httpQuery = NULL, $allowOverride = TRUE, $minimal = FALSE, $open = FALSE) {
	$db = ssDbConnect();
	
	$cache = new cache("posts-$httpQuery", TRUE, TRUE);
	if ($cache->caching == TRUE) {
		$api = new API;
		$api->searchDb($httpQuery, $allowOverride, "post");
		$cacheVars["posts"] = $posts = $api->posts;
		$cacheVars["total"] = $total = $api->total;
		$cacheVars["errors"] = $errors = $api->errors;
		$cache->storeVars($cacheVars);
	} else {
		$cacheVars = $cache->varCache();
		$posts = $cacheVars["posts"];
		$total = $cacheVars["total"];
		$errors = $cacheVars["errors"];
	}
	
	if ($errors) {
		foreach ($errors as $error) {
			print "<p class=\"ss-error\">$error</p>";
		}
		
		return NULL;
	}
	
	if ($total != 0) {
		print "<div class=\"posts\">";
		displayPosts ($posts, $minimal, $open, $db);
		print "</div>";
		
		global $pages;
		if ($minimal == TRUE) {
			parse_str($httpQuery, $queryResults);
			unset($queryResults["n"]);
			unset($queryResults["offset"]);
			$linkQuery = http_build_query($queryResults);
			print "<div class=\"page-buttons\">
			<a class=\"ss-button\" href=\"".$pages["posts"]->address."/?".htmlspecialchars($linkQuery)."\">More</a>
			</div>";
		} else {
			$limit = NULL;
			if (isset($_REQUEST["n"])) {
				$limit = $_REQUEST["n"];
			}
			pageButtons ($pages["posts"]->getAddress(), $limit, $total);
		}
	} else {
		print "<p>No results found for your search parameters.</p>";
	}
}

?>
