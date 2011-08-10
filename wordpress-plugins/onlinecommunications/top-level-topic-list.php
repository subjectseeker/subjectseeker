<?php
/*
Plugin Name: OnlineCommunications Top Level Topics List
Plugin URI: http://scienceseeker.org/
Description: Listing of all top level topics
Author: Jessica P. Hekman
Version: 1
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

include_once "oc-includes.inc";

function doTopLevelTopicsList()
{
  global $topics2html;

  // Download results of searching on toplevel topics
  $params["toplevel"] = 1;
  $curl = getSearchCurl("topic", $params);

  $result = curl_exec($curl);
  curl_close($curl);

  print transformXmlString($result, $topics2html);

  // print "RESULT: $result\n";

}

function widget_topLevelTopicsList($args) {
  extract($args);
  echo $before_widget;
  echo $before_title;?>View Blogs by Topic<?php echo $after_title;
  doTopLevelTopicsList();
  echo $after_widget;
}

function topLevelTopicsList_init()
{
  register_sidebar_widget(__('OC Top Level Topics List'), 'widget_topLevelTopicsList');
}
add_action("plugins_loaded", "topLevelTopicsList_init");

?>
