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
	global $widgetPage;
	extract($args);
	$params = httpParamsToSearchQuery();
	$db = ssDbConnect();
	echo $before_widget;
  echo $before_title;
	echo "Filters";
	echo $after_title;
	
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
	
	print "<div class=\"categories-wrapper\" data-posts=\"$mainFeed\" data-blogs=\"$blogList\" data-rss=\"$serializerUrl\" data-widget=\"$widgetPage\">
	<div id=\"filter-buttons\" class=\"ss-div-2 center-text\" style=\"display: none;\"><a id=\"filter-posts\" class=\"button-small-red\" href=\"$mainFeed\">Posts</a><a id=\"filter-blogs\" class=\"button-small-red\" href=\"$blogList\">Blogs</a><a id=\"filter-rss\" class=\"button-small-yellow\" href=\"$serializerUrl\" target=\"_blank\">RSS</a></div>
	<ul>
	<li><input id=\"filter\" class=\"categories\" type=\"checkbox\" name=\"category\" value=\"has-citation\"";
	if ($checkCitation) print " checked=\"checked\"";
	print " /> <a href=\"$mainFeed/?type=post&filter0=has-citation&value0=true\">Citations</a></li>
  <li><input id=\"filter\" class=\"categories\" type=\"checkbox\" name=\"category\" value=\"recommender-status\"";
	if ($checkEditorsPicks) print " checked=\"checked\"";
	print " /> <a href=\"$mainFeed/?type=post&filter0=recommender-status&value0=editor\">Editors' Picks</a></li>
	<br />";
	
	$topicList = getTopicList (1, $db);
	while ($row = mysql_fetch_array($topicList)) {
		$topicName = $row["TOPIC_NAME"];
  	print "<li><input id=\"topic\" class=\"categories\" type=\"checkbox\" name=\"category\" value=\"$topicName\"";
		if ($topics && array_search("$topicName", $topics) !== FALSE) print " checked=\"checked\"";
		print " /> <a href=\"$mainFeed/?type=post&filter0=blog&modifier0=topic&value0=".urlencode($topicName)."\">$topicName</a></li>";
	}
	
	print "</ul>
	<div id=\"filter-buttons\" class=\"ss-div-2 center-text\" style=\"display: none;\"><a id=\"filter-posts\" class=\"button-small-red\" href=\"$mainFeed\">Posts</a><a id=\"filter-blogs\" class=\"button-small-red\" href=\"$blogList\">Blogs</a><a id=\"filter-rss\" class=\"button-small-yellow\" href=\"$serializerUrl\" target=\"_blank\">RSS</a><br /><br /><a id=\"filter-widget\" class=\"button-small-red\" style=\"width: 90px;\" href=\"/get-widget\">Get Widget</a></div>
	</div>";
	echo $after_widget;
}

function topLevelTopicsList_init()
{
  register_sidebar_widget(__('SS Top Level Topics List'), 'widget_topLevelTopicsList');
}
add_action("plugins_loaded", "topLevelTopicsList_init");

?>
