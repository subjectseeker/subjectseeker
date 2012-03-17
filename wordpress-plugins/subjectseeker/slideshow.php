<?php
/*
Plugin Name: SubjectSeeker Slideshow
Plugin URI: http://scienceseeker.org/
Description: Slideshow for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://scienceseeker.org/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssSlideShow')) {
  class ssSlideShow {
    function ssSlideShow() {
      $this->version = "0.1";
    }
	
    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssSlideShow($args) {
        extract($args);
        $options = get_option('widget_ssSlideShow');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssSlideShow();
        echo $after_widget;
      }
      function widget_ssSlideShow_control() {
        $options = get_option('widget_ssSlideShow');
        if ( $_POST['ssSlideShow-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssSlideShow-title']));
          update_option('widget_ssSlideShow', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssSlideShow-title">Title:<input class="widefat" name="ssSlideShow-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssSlideShow-submit" name="ssSlideShow-submit" value="1" />';
      }
      register_sidebar_widget('ssSlideShow', 'widget_ssSlideShow');
      register_widget_control('ssSlideShow', 'widget_ssSlideShow_control');
    }
  }
}

$ssSlideShow = new ssSlideShow();
add_action( 'plugins_loaded', array(&$ssSlideShow, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssSlideShow, 'setupActivation' ));

function get_ssSlideShow($settings = array()) {
  global $ssSlideShow;
  displaySlideShow();
}

function displaySlideShow() {
	global $imagesUrl;
  $db = ssDbConnect();
	print "<div class=\"pikachoose\">
	<ul id=\"pikame\">";
	$recommendations = getEditorsPicks("images", $db);
	foreach ($recommendations as $recommendation) {
		$comment = $recommendation["comment"];
		$imageName = $recommendation["image"];
		$author = $recommendation["author"];
		$postData = getPost("postId", $recommendation["postId"], $db);
		$title = $postData["title"];
		$url = $postData["uri"];
		print "<li><a href=\"$url\"><img src=\"$imagesUrl/headers/$imageName\"/></a><span><h3><a href=\"$url\">$title</a></h3>$comment - $author</span></li>";
	}
	print "</ul></div>";
}
?>