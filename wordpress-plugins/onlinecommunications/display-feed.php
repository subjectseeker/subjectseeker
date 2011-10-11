<?php
/*
Plugin Name: OnlineCommunications Display Feed
Plugin URI: http://scienceseeker.org/
Description: Display feed, filtered as requested
Author: Jessica P. Hekman
Version: 0.1
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

include_once "oc-includes.inc";

if (!class_exists('ocDisplayFeed')) {
  class ocDisplayFeed {
    function ocDisplayFeed() {
      $this->version = "0.1";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ocDisplayFeed($args) {
        extract($args);
        $options = get_option('widget_ocDisplayFeed');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ocDisplayFeed();
        echo $after_widget;
      }
      function widget_ocDisplayFeed_control() {
        $options = get_option('widget_ocDisplayFeed');
        if ( $_POST['ocDisplayFeed-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ocDisplayFeed-title']));
          update_option('widget_ocDisplayFeed', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ocDisplayFeed-title">Title:<input class="widefat" name="ocDisplayFeed-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ocDisplayFeed-submit" name="ocDisplayFeed-submit" value="1" />';
      }
      register_sidebar_widget('ocDisplayFeed', 'widget_ocDisplayFeed');
      register_widget_control('ocDisplayFeed', 'widget_ocDisplayFeed_control');
    }
  }
}

$ocDisplayFeed = new ocDisplayFeed();
add_action( 'plugins_loaded', array(&$ocDisplayFeed, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ocDisplayFeed, 'setupActivation' ));

function get_ocDisplayFeed($settings = array()) {
	global $ocDisplayFeed;

        displayFeed($settings);
}

/*
 * Non-plugin functions
 */

function displayFeed() {

  // Params: list of blogs to display
  // filter1=topic
  // value1=cdata

  $type = $_REQUEST["type"]; // "blog"
  $ocParams = parseHttpParams();
  $httpParams["n"] = $_REQUEST["n"];
  $httpParams["offset"] = $_REQUEST["offset"];

  $resourceXml = searchFeeds ($type, $ocParams, $httpParams);

  printFeeds($resourceXml);
}

// Pass the requested search parameters on to the search module.
// Parse the XML response into objects and return it.
function searchFeeds($type, $ocParams, $httpParams) {

  // Construct search query

  $curl = getSerializerCurl($type, $ocParams, $httpParams);
  $result = curl_exec($curl);

  curl_close($curl);

  return $result;
}

// Input: XML document containing a list of resources.
// Action: Print the contents of this document in HTML.
function printFeeds($xmlFeed) {
  global $feed2html;

  $dom = new DOMDocument();
  $dom->loadXML($xmlFeed);

  $xslt = new xslTProcessor();
  $xsl = new SimpleXMLElement($feed2html, null, true);

  $baseurl = "http://scienceseeker.org/displayfeed/?type=blog";
  $ignoreParams = array("type", "n", "offset");
  $urlParamString = getUrlParamString($ignoreParams);
  if ($urlParamString != null) {
    $baseurl .= "&$urlParamString";
  }

  setXsltParameter($xslt, "offset", $_REQUEST["offset"]);
  setXsltParameter($xslt, "pagesize", $_REQUEST["n"]);
  setXsltParameter($xslt, "baseurl", $baseurl);

  $xslt->importStylesheet($xsl);

  print $xslt->transformToXML($dom);

}

?>
