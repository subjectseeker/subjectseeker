<?php
/*
Plugin Name: SubjectSeeker Display Resources
Plugin URI: http://scienceseeker.org/
Description: Display the requested resources (usually blogs)
Author: Jessica P. Hekman
Version: 1
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssDisplayResources')) {
  class ssDisplayResources {
    function ssDisplayResources() {
      $this->version = "0.1";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssDisplayResources($args) {
        extract($args);
        $options = get_option('widget_ssDisplayResources');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssDisplayResources();
        echo $after_widget;
      }
      function widget_ssDisplayResources_control() {
        $options = get_option('widget_ssDisplayResources');
        if ( $_POST['ssDisplayResources-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssDisplayResources-title']));
          update_option('widget_ssDisplayResources', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssDisplayResources-title">Title:<input class="widefat" name="ssDisplayResources-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssDisplayResources-submit" name="ssDisplayResources-submit" value="1" />';
      }
      register_sidebar_widget('ssDisplayResources', 'widget_ssDisplayResources');
      register_widget_control('ssDisplayResources', 'widget_ssDisplayResources_control');
    }
  }
}

$ssDisplayResources = new ssDisplayResources();
add_action( 'plugins_loaded', array(&$ssDisplayResources, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssDisplayResources, 'setupActivation' ));

function get_ssDisplayResources($settings = array()) {
  global $ssDisplayResources;

  doDisplayResources();
}

/*
 * Non-widget functions
 */

function doDisplayResources()
{

  // Params: list of blogs to display

  // filter1=topic
  // value1=cdata

  $type = $_REQUEST["type"];
  if ($type == null || $type === "") {
    $type = "blog";
  }
  $params = parseHttpParams();
  $resourceXml = searchResources ($type, $params);

  $topics = $params["topic"];
  $topicStr = null;
  if ($topics != null) {
    $topicStr = array_shift($topics);
    foreach ($topics as $topic) {
      $topicStr .= "|" . $topic;
    }
  }

  printResources($resourceXml, $topicStr);
}

// Pass the requested search parameters on to the search module.
// Parse the XML response into objects and return it.
function searchResources($type, $params) {

  // Construct search query

  $curl = getSearchCurl($type, $params);
  $result = curl_exec($curl);
  curl_close($curl);

  return $result;
}

// Input: XML document containing a list of resources.
// Action: Print the contents of this document in HTML.
function printResources($xmlResources, $topics) {
  global $resources2html;

  $params = null;

  if ($topics != null) {
    $params["topics"] = $topics;
  }
  print transformXmlString($xmlResources, $resources2html, $params);
}

?>
