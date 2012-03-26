<?php
/*
Plugin Name: SubjectSeeker Top Level Topics List
Plugin URI: http://scienceseeker.org/
Description: Listing of all top level topics
Author: Jessica P. Hekman
Version: 1
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

function widget_topLevelTopicsList($args) {
	global $mainFeed;
	global $serializerUrl;
	global $blogList;
	extract($args);
	$params = parseHttpParams();
	$db = ssDbConnect();
	echo $before_widget;
  echo $before_title;
	echo "Filters";
	echo $after_title;
	
	print "<div class=\"categories-wrapper\" data-posts=\"$mainFeed\" data-blogs=\"$blogsUrl\" data-rss=\"$serializerUrl\">
	<ul>
	<li><input id=\"modifier\" class=\"categories\" type=\"checkbox\" name=\"category\" value=\"citation\"";
	if ($params["modifier"] && array_search("citation", $params["modifier"]) !== FALSE) print " checked=\"checked\"";
	print " /> <a href=\"$mainFeed/?type=post&filter0=modifier&value0=citation\">Citations</a></li>
  <li><input id=\"modifier\" class=\"categories\" type=\"checkbox\" name=\"category\" value=\"editorsPicks\"";
	if ($params["modifier"] && array_search("editorsPicks", $params["modifier"]) !== FALSE) print " checked=\"checked\"";
	print " /> <a href=\"$mainFeed/?type=post&filter0=modifier&value0=editorsPicks\">Editors' Picks</a></li>
	<br />";
	
	$topicList = getTopicList (1, $db);
	while ($row = mysql_fetch_array($topicList)) {
		$topicName = $row["TOPIC_NAME"];
  	print "<li><input id=\"topic\" class=\"categories\" type=\"checkbox\" name=\"category\" value=\"$topicName\"";
		if ($params["topic"] && array_search("$topicName", $params["topic"]) !== FALSE) print " checked=\"checked\"";
		print " /> <a href=\"$mainFeed/?type=post&filter0=topic&value0=".urlencode($topicName)."\">$topicName</a></li>";
	}
	
	print "</ul>
	<div id=\"center-text\"><a id=\"filter-posts\" class=\"button-small-red\" href=\"$mainFeed\">Posts</a><a id=\"filter-blogs\" class=\"button-small-red\" href=\"$blogsUrl\">Blogs</a><a id=\"filter-rss\" class=\"button-small-yellow\" href=\"$serializerUrl\" target=\"_blank\">RSS</a></div>
	</div>";
	echo $after_widget;
}

function topLevelTopicsList_init()
{
  register_sidebar_widget(__('SS Top Level Topics List'), 'widget_topLevelTopicsList');
}
add_action("plugins_loaded", "topLevelTopicsList_init");

?>
