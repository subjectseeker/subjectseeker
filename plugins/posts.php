<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function displayFeed($httpQuery = NULL, $allowOverride = TRUE, $minimal = FALSE, $open = FALSE) {
	$db = ssDbConnect();
	$queryList = httpParamsToSearchQuery($httpQuery, $allowOverride);
	$settings = httpParamsToExtraQuery($httpQuery, $allowOverride);
	$settings["type"] = "post";
	$postsData = generateSearchQuery ($queryList, $settings, 0, $db);
	
	if (!empty($postsData["errors"])) {
		foreach ($postsData["errors"] as $error) {
			print "<p class=\"ss-error\">$error</p>";
		}
	}
	else {
		// Get current URL for Social Network sync page.
		$currentUrl = getURL();
		
		if (!empty($postsData["result"]) && mysql_num_rows($postsData["result"]) != 0) {
			displayPosts ($postsData["result"], $minimal, $open, $db);
		}
		else {
			print "<p>No results found for your search parameters.</p>";
		}
	}
	global $pages;
	if ($minimal == TRUE) {
		parse_str($httpQuery, $queryResults);
		unset($queryResults["n"]);
		unset($queryResults["offset"]);
		$linkQuery = http_build_query($queryResults);
		print "<div class=\"page-buttons\">
		<a class=\"ss-button\" href=\"".$pages["posts"]->address."/?".htmlspecialchars($linkQuery)."\">More</a>
		</div>";
	}
	else {
		$limit = NULL;
		if (!empty($_REQUEST["n"])) $limit = $_REQUEST["n"];
		pageButtons ($pages["posts"]->getAddress(), $limit, $postsData["total"]);
	}
}

?>
