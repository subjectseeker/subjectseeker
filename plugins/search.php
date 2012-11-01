<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function searchForm() {
	global $pages;
	print "<form action=\"".$pages["search"]->getAddress()."\" method=\"get\">
	<input class=\"search-input\" type=\"text\" name=\"text\"";
	if (!empty($_REQUEST["text"])) {
		print " value=\"".$_REQUEST["text"]."\"";
	}
	print "/><input class=\"search-button\" type=\"submit\" value=\"Search\" />
	</form>";
}

function searchPage($text = NULL, $limit = 30, $minimal = FALSE, $open = FALSE) {
	$db = ssDbConnect();
	
	$offset = 0;
	if (empty($text) && isset($_REQUEST["text"])) {
		$text = $_REQUEST["text"];
	}
	if (!empty($_REQUEST["n"])) {
		$limit = $_REQUEST["n"];
	}
	if (!empty($_REQUEST["offset"])) {
		$offset = $_REQUEST["offset"];
	}
	
	$cache = new cache("search", TRUE, TRUE);
	if ($cache->caching == TRUE) {
		// Use optimal query for best results
		// TO DO: Use API for this
		$searchValue = mysql_real_escape_string(preg_replace("/[^A-Za-z0-9\s]/", "%", $text));
		$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM (SELECT post.BLOG_POST_ID, post.BLOG_POST_URI, post.BLOG_POST_DATE_TIME, post.BLOG_POST_SUMMARY, post.BLOG_POST_TITLE, post.BLOG_POST_HAS_CITATION, blog.BLOG_ID, blog.BLOG_NAME, blog.BLOG_URI FROM BLOG_POST post INNER JOIN BLOG blog ON blog.BLOG_ID = post.BLOG_ID INNER JOIN BLOG_AUTHOR author ON blog.BLOG_ID = author.BLOG_ID AND post.BLOG_AUTHOR_ID = author.BLOG_AUTHOR_ID WHERE BLOG_POST_STATUS_ID = 0 AND BLOG_STATUS_ID = 0 AND ( (BLOG_POST_TITLE LIKE '%".$searchValue."%') OR (BLOG_POST_SUMMARY LIKE '%".$searchValue."%') OR (BLOG_AUTHOR_ACCOUNT_NAME LIKE '%".$searchValue."%') OR (BLOG_NAME LIKE '%".$searchValue."%') ) 
	UNION
	SELECT post.BLOG_POST_ID, post.BLOG_POST_URI, post.BLOG_POST_DATE_TIME, post.BLOG_POST_SUMMARY, post.BLOG_POST_TITLE, post.BLOG_POST_HAS_CITATION, blog.BLOG_ID, blog.BLOG_NAME, blog.BLOG_URI FROM BLOG_POST post INNER JOIN BLOG blog ON blog.BLOG_ID = post.BLOG_ID INNER JOIN BLOG_AUTHOR author ON blog.BLOG_ID = author.BLOG_ID AND post.BLOG_AUTHOR_ID = author.BLOG_AUTHOR_ID INNER JOIN POST_TOPIC pt ON post.BLOG_POST_ID=pt.BLOG_POST_ID INNER JOIN TOPIC t ON t.TOPIC_ID=pt.TOPIC_ID WHERE BLOG_POST_STATUS_ID = 0 AND BLOG_STATUS_ID = 0 AND TOPIC_NAME='".$searchValue."' ) as a ORDER BY BLOG_POST_DATE_TIME DESC LIMIT ".$limit." OFFSET ".$offset."";
		$postsData = mysql_query($sql, $db);
		$sql = "SELECT FOUND_ROWS()";
		$sqlTotal = mysql_fetch_array(mysql_query($sql, $db));
		$total = array_shift($sqlTotal);
		
		$posts = array();
		while ($row = mysql_fetch_array($postsData)) {
			$post["postId"] = $row["BLOG_POST_ID"];
			$post["postTitle"] = $row["BLOG_POST_TITLE"];
			$post["postUrl"] = htmlspecialchars($row["BLOG_POST_URI"]);
			$post["postSummary"] = $row["BLOG_POST_SUMMARY"];
			$post["blogName"] = $row["BLOG_NAME"];
			$post["blogUrl"] = htmlspecialchars($row["BLOG_URI"]);
			$post["postDate"] = $row["BLOG_POST_DATE_TIME"];
			$post["hasCitation"] = $row["BLOG_POST_HAS_CITATION"];
			
			array_push($posts, $post);
		}
		$cacheVars["posts"] = $posts;
		$cacheVars["total"] = $total;
		
		$cache->storeVars($cacheVars);
	} else {
		$cacheVars = $cache->varCache();
		$posts = $cacheVars["posts"];
		$total = $cacheVars["total"];
	}
		
	if (!empty($posts)) {
		displayPosts ($posts, $minimal, $open, $db);
	} else {
		print "<p>No results found for your search parameters.</p>";
	}
	global $pages;
	if ($minimal == TRUE) {
		print "<div class=\"page-buttons\">
		<a class=\"ss-button\" href=\"".$pages["search"]->getAddress()."/?text=$text\">More</a>
		</div>";
	}
	else {
		$limit = NULL;
		if (isset($_REQUEST["n"])) {
			$limit = $_REQUEST["n"];
		}
		pageButtons ($pages["search"]->getAddress(), $limit, $total);
	}
}

?>
