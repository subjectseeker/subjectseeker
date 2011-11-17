<?php
/*
Plugin Name: SubjectSeeker Display Feed
Plugin URI: http://scienceseeker.org/
Description: Display feed, filtered as requested
Author: Jessica P. Hekman
Version: 0.1
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssDisplayFeed')) {
  class ssDisplayFeed {
    function ssDisplayFeed() {
      $this->version = "0.1";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssDisplayFeed($args) {
        extract($args);
        $options = get_option('widget_ssDisplayFeed');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssDisplayFeed();
        echo $after_widget;
      }
      function widget_ssDisplayFeed_control() {
        $options = get_option('widget_ssDisplayFeed');
        if ( $_POST['ssDisplayFeed-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssDisplayFeed-title']));
          update_option('widget_ssDisplayFeed', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssDisplayFeed-title">Title:<input class="widefat" name="ssDisplayFeed-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssDisplayFeed-submit" name="ssDisplayFeed-submit" value="1" />';
      }
      register_sidebar_widget('ssDisplayFeed', 'widget_ssDisplayFeed');
      register_widget_control('ssDisplayFeed', 'widget_ssDisplayFeed_control');
    }
  }
}

$ssDisplayFeed = new ssDisplayFeed();
add_action( 'plugins_loaded', array(&$ssDisplayFeed, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssDisplayFeed, 'setupActivation' ));

function get_ssDisplayFeed($settings = array()) {
	global $ssDisplayFeed;

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
  $ssParams = parseHttpParams();
  $httpParams["n"] = $_REQUEST["n"];
  $httpParams["offset"] = $_REQUEST["offset"];

  $resourceXml = searchFeeds ($type, $ssParams, $httpParams);

  printFeeds($resourceXml);
}

// Pass the requested search parameters on to the search module.
// Parse the XML response into objects and return it.
function searchFeeds($type, $ssParams, $httpParams) {

  // Construct search query

  $curl = getSerializerCurl($type, $ssParams, $httpParams);
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
