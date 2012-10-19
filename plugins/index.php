<?php

/*

Copyright © 2010–2012 Christopher R. Maden, Jessica Perry Hekman and Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function displayResources() {
	global $pages;
	
	$db = ssDbConnect();
	
	global $pagesize;
	if (!empty($_REQUEST["n"])) {
		$pagesize = $_REQUEST["n"];
	}
	
	$queryList = httpParamsToSearchQuery();
	$settings = httpParamsToExtraQuery();
	$settings["type"] = "blog";
	$settings["limit"] = $pagesize;
	$blogsData = generateSearchQuery ($queryList, $settings, 0, $db);
	
	if (! empty($blogsData["errors"])) {
		print "<div class=\"padding-content\">";
		foreach ($blogsData["errors"] as $error) {
			print "<p class=\"ss-error\">$error</p>";
		}
		print "</div>";
		return;
	}

	if (empty($blogsData["result"]) || mysql_num_rows($blogsData["result"]) == 0) {
    print "<p>No results found for your search parameters.</p>";
  }
	else {
		print "<div class=\"entries\">";
		while ($row = mysql_fetch_array($blogsData["result"])) {
			$blogId = $row["BLOG_ID"];
			$blogName = $row["BLOG_NAME"];
			$blogUri = $row["BLOG_URI"];
			$blogSyndication = $row[ "BLOG_SYNDICATION_URI"];
			$blogDescription = $row[ "BLOG_DESCRIPTION"];
			
			if (empty($blogDescription)) {
				$blogDescription = "No summary available for this site.";
			}
			
			print "<div class=\"ss-entry-wrapper\">
			<div class=\"entry-indicator\">+</div>
			<a class=\"post-header\" href=\"".$blogUri."\">".$blogName."</a>
			<div class=\"ss-slide-wrapper\">
				<div class=\"padding-content\">
				<div class=\"margin-bottom\">".$blogDescription."</div>
				<div>
				<a class=\"ss-button\" href=\"".$blogSyndication."\">Feed</a> <a class=\"ss-button\" href=\"".$pages["home"]->getAddress()."claim/".$blogId."\">Claim this site</a>
				</div>
				</div>
			</div>
			</div>";
		}
		print "</div>";
	}
	pageButtons ($pages["sources"]->getAddress(), $pagesize, $blogsData["total"]);
}

?>
