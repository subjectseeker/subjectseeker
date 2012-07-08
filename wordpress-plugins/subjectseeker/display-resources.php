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

function doDisplayResources() {
	
	$db = ssDbConnect();
	
	$pagesize = $_REQUEST["n"];
	if (! $pagesize) {
		$pagesize = 500;
	}
	
	$queryList = httpParamsToSearchQuery();
	$settings = httpParamsToExtraQuery();
	$errormsgs = array();
	$settings["type"] = "blog";
	$settings["limit"] = $pagesize;
	$blogsData = generateSearchQuery ($queryList, $settings, 0, $errormsgs, $db);
	
	if (! empty($errormsgs)) {
		print "<div id=\"padding-content\">";
		foreach ($errormsgs as $error) {
			print "<p>Error: $error</p>";
		}
		print "</div>";
		return;
	}

	$blogs = array();
	while ($row = mysql_fetch_array($blogsData)) {
		$blog["id"] = $row["BLOG_ID"];
		$blog["name"] = $row["BLOG_NAME"];
		$blog["uri"] = $row["BLOG_URI"];
		$blog["syndication"] = $row[ "BLOG_SYNDICATION_URI"];
		$blog["description"] = $row[ "BLOG_DESCRIPTION"];
		array_push($blogs, $blog);
	}
	
	if (empty($blogs)) {
		print "<div id=\"padding-content\">No results found for your search parameters.</div>";
	}
	
	else {
		print "<hr />";
		foreach ($blogs as $item) {
			if (! $item["description"]) {
				$item["description"] = "No summary available for this site.";
			}
			
			print "<div class=\"ss-entry-wrapper\">
			<a class=\"ss-postTitle\" href=\"".$item["uri"]."\">".$item["name"]."</a>
			<div class=\"ss-div-button alignright\"><div class=\"arrow-down\" title=\"Show Extra Info\"></div></div>
			<div class=\"ss-slide-wrapper\" style=\"display: none; \">
				<div id=\"padding-content\">
				<div class=\"margin-bottom\">".$item["description"]."</div>
				<div>
				<a class=\"ss-button\" href=\"".$item["syndication"]."\">Site's feed</a> <a class=\"ss-button\" href=\"/claimblog/?blogId=".$item["id"]."\">Claim this blog</a>
				</div>
				</div>
			</div>
			</div>
			<hr />";
		}
	}
	
	global $blogList;
	pageButtons ($blogList, $pagesize);

}

?>
