<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function searchForm() {
	print "<form action=\"/search/\" method=\"get\">
	<input class=\"search-input\" type=\"text\" name=\"text\"";
	if (!empty($_REQUEST["text"])) {
		print " value=\"".$_REQUEST["text"]."\"";
	}
	print "/><input class=\"search-button\" type=\"submit\" value=\"Search\" />
	</form>";
}

function searchPage($text = NULL, $minimal = FALSE, $open = FALSE) {
	$db = ssDbConnect();
	
	global $numResults;
	$limit = $numResults;
	$offset = 0;
	if (empty($text) && !empty($_REQUEST["text"])) {
		$text = $_REQUEST["text"];
	}
	if (!empty($_REQUEST["n"])) {
		$limit = $numResults;
	}
	if (!empty($_REQUEST["offset"])) {
		$offset = $_REQUEST["offset"];
	}
	
	// Use optimal query for best results
	// TO DO: Use API for this
	$searchValue = mysql_real_escape_string(preg_replace("/[^A-Za-z0-9\s]/", "%", $text));
	$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM (SELECT post.BLOG_POST_ID, post.BLOG_POST_URI, post.BLOG_POST_DATE_TIME, post.BLOG_POST_SUMMARY, post.BLOG_POST_TITLE, post.BLOG_POST_HAS_CITATION, blog.BLOG_ID, blog.BLOG_NAME, blog.BLOG_URI FROM BLOG_POST post INNER JOIN BLOG blog ON blog.BLOG_ID = post.BLOG_ID INNER JOIN BLOG_AUTHOR author ON blog.BLOG_ID = author.BLOG_ID AND post.BLOG_AUTHOR_ID = author.BLOG_AUTHOR_ID WHERE BLOG_POST_STATUS_ID = 0 AND BLOG_STATUS_ID = 0 AND ( (BLOG_POST_TITLE LIKE '%".$searchValue."%') OR (BLOG_POST_SUMMARY LIKE '%".$searchValue."%') OR (BLOG_AUTHOR_ACCOUNT_NAME LIKE '%".$searchValue."%') OR (BLOG_NAME LIKE '%".$searchValue."%') ) 
UNION
SELECT post.BLOG_POST_ID, post.BLOG_POST_URI, post.BLOG_POST_DATE_TIME, post.BLOG_POST_SUMMARY, post.BLOG_POST_TITLE, post.BLOG_POST_HAS_CITATION, blog.BLOG_ID, blog.BLOG_NAME, blog.BLOG_URI FROM BLOG_POST post INNER JOIN BLOG blog ON blog.BLOG_ID = post.BLOG_ID INNER JOIN BLOG_AUTHOR author ON blog.BLOG_ID = author.BLOG_ID AND post.BLOG_AUTHOR_ID = author.BLOG_AUTHOR_ID INNER JOIN POST_TOPIC pt ON post.BLOG_POST_ID=pt.BLOG_POST_ID INNER JOIN TOPIC t ON t.TOPIC_ID=pt.TOPIC_ID WHERE BLOG_POST_STATUS_ID = 0 AND BLOG_STATUS_ID = 0 AND TOPIC_NAME='".$searchValue."' ) as a ORDER BY BLOG_POST_DATE_TIME DESC LIMIT ".$limit." OFFSET ".$offset."";
	$postsData = mysql_query($sql, $db);
	$total = array_shift(mysql_fetch_array(mysql_query("SELECT FOUND_ROWS()", $db)));
	
	if ($postsData && mysql_num_rows($postsData) != 0) {
		displayPosts ($postsData, $minimal, $open, $db);
	}
	else {
		print "<p>No results found for your search parameters.</p>";
	}
	global $pages;
	if ($minimal == TRUE) {
		parse_str($httpQuery, $queryResults);
		unset($queryResults["n"]);
		unset($queryResults["offset"]);
		$linkQuery = http_build_query($queryResults);
		print "<div class=\"page-buttons\">
		<a class=\"ss-button\" href=\"".$pages["search"]->getAddress()."/?$linkQuery\">More Posts</a>
		</div>";
	}
	else {
		$limit = NULL;
		if (!empty($_REQUEST["n"])) $limit = $_REQUEST["n"];
		pageButtons ($pages["search"]->getAddress(), $limit, $total);
	}
}

?>
