<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function displayFilters() {
	global $pages;
	global $feedUrl;
	global $currentPage;
	
	$params = httpParamsToSearchQuery();
	$db = ssDbConnect();
	
	foreach ($params as $param) {
		if ($param->name == "blog" && $param->modifier == "topic") {
			$topics[] = $param->value;
		}
		if ($param->name == "topic") {
			$topics[] = $param->value;
		}
		if ($param->name == "has-citation" && $param->value != "false") {
			$checkCitation = TRUE;
		}
		if ($param->name == "recommender-status" && $param->value == "editor") {
			$checkEditorsPicks = TRUE;
		}
	}
	
	print "<div class=\"categories-wrapper\" data-posts=\"".$pages["posts"]->getAddress()."\" data-blogs=\"".$pages["sources"]->getAddress()."\" data-feed=\"$feedUrl\" data-widget=\"".$pages["widget"]->getAddress()."\">
	<div class=\"filter-buttons\"><a data-button=\"filter-posts\" class=\"button-small-red\" href=\"".$pages["posts"]->getAddress()."\">Posts</a><a data-button=\"filter-blogs\" class=\"button-small-red\" href=\"".$pages["sources"]->getAddress()."\">Sources</a><a data-button=\"filter-widget\" class=\"button-small-red\" href=\"".$pages["widget"]->getAddress()."\">Widget</a><a data-button=\"filter-feed\" class=\"button-small-yellow\" href=\"$feedUrl\" target=\"_blank\">Feed</a></div>
	<p>Search Title<br />
	<input class=\"filters-text\" type=\"text\" name=\"title\" /></p>
	<ul>
	<li><input class=\"filters\" type=\"checkbox\" name=\"category\" value=\"has-citation\"";
	if (isset($checkCitation)) print " checked=\"checked\"";
	print " /> <a href=\"".$pages["posts"]->getAddress()."/?type=post&amp;filter0=has-citation&amp;value0=true\">Citations</a></li>
	<li style=\"margin-bottom: 20px;\"><input class=\"filters\" type=\"checkbox\" name=\"category\" value=\"recommender-status\"";
	if (isset($checkEditorsPicks)) print " checked=\"checked\"";
	print " /> <a href=\"".$pages["posts"]->getAddress()."/?type=post&amp;filter0=recommender-status&amp;value0=editor\">Editors' Picks</a></li>";
	$topicList = getTopicList (1, $db);
	while ($row = mysql_fetch_array($topicList)) {
		$topicName = $row["TOPIC_NAME"];
		print "<li><input class=\"categories\" type=\"checkbox\" name=\"category\" value=\"$topicName\"";
		if (isset($topics) && array_search("$topicName", $topics) !== FALSE) {
			print " checked=\"checked\"";
		}
		if ($currentPage->id == "sources") {
			print " /> <a href=\"".$pages["sources"]->getAddress()."/?type=blog&amp;filter0=topic&amp;value0=".urlencode($topicName)."\">$topicName</a></li>";
		} else {
			print " /> <a href=\"".$pages["posts"]->getAddress()."/?type=post&amp;filter0=blog&amp;modifier0=topic&amp;value0=".urlencode($topicName)."\">$topicName</a></li>";
		}
	}
	
	print "</ul>
	<div class=\"filter-buttons\"><a data-button=\"filter-posts\" class=\"button-small-red\" href=\"".$pages["posts"]->getAddress()."\">Posts</a><a data-button=\"filter-blogs\" class=\"button-small-red\" href=\"".$pages["sources"]->getAddress()."\">Sources</a><a data-button=\"filter-widget\" class=\"button-small-red\" href=\"".$pages["widget"]->getAddress()."\">Widget</a><a data-button=\"filter-feed\" class=\"button-small-yellow\" href=\"$feedUrl\" target=\"_blank\">Feed</a></div>
	</div>";

}

?>
