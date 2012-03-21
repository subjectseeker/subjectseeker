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
	extract($args);
	$params = parseHttpParams();
	$db = ssDbConnect();
	echo $before_widget;
  echo $before_title;
	echo "Categories";
	echo $after_title;
	
	print "<div class=\"categories-wrapper\" data-feed=\"$mainFeed\" data-serializer=\"$serializerUrl\">
	<ul>
	<li><input id=\"modifier\" class=\"categories\" type=\"checkbox\" name=\"category\" value=\"citation\"";
	if ($params["modifier"] && array_search("citation", $params["modifier"]) !== FALSE) print " checked=\"checked\"";
	print " /> Citations</li>
  <li><input id=\"modifier\" class=\"categories\" type=\"checkbox\" name=\"category\" value=\"editorsPicks\"";
	if ($params["modifier"] && array_search("editorsPicks", $params["modifier"]) !== FALSE) print " checked=\"checked\"";
	print " /> Editors' Picks</li>
	<br />";
	
	$topicList = getTopicList (1, $db);
	while ($row = mysql_fetch_array($topicList)) {
		$topicName = $row["TOPIC_NAME"];
  	print "<li><input id=\"topic\" class=\"categories\" type=\"checkbox\" name=\"category\" value=\"$topicName\"";
		if ($params["topic"] && array_search("$topicName", $params["topic"]) !== FALSE) print " checked=\"checked\"";
		print " /> $topicName</li>";
	}
	
	print "</ul>
	<a class=\"ss-button\" href=\"$mainFeed\">Filter Posts</a> <a class=\"custom-rss\" href=\"$serializerUrl\" target=\"_blank\">Custom RSS</a>
	</div>";
	echo $after_widget;
}

function topLevelTopicsList_init()
{
  register_sidebar_widget(__('SS Top Level Topics List'), 'widget_topLevelTopicsList');
}
add_action("plugins_loaded", "topLevelTopicsList_init");

?>
