<?php
/*
Plugin Name: SubjectSeeker Count Blogs
Plugin URI: http://scienceseeker.org/
Description: Count blogs for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://www.binaryparticle.com/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssBlogCount')) {
  class ssBlogCount {
    function ssBlogCount() {
      $this->version = "0.1";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssBlogCount($args) {
        extract($args);
        $options = get_option('widget_ssBlogCount');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssBlogCount();
        echo $after_widget;
      }
      function widget_ssBlogCount_control() {
        $options = get_option('widget_ssBlogCount');
        if ( $_POST['ssBlogCount-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssBlogCount-title']));
          update_option('widget_ssBlogCount', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssBlogCount-title">Title:<input class="widefat" name="ssBlogCount-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssBlogCount-submit" name="ssBlogCount-submit" value="1" />';
      }
      register_sidebar_widget('ssBlogCount', 'widget_ssBlogCount');
      register_widget_control('ssBlogCount', 'widget_ssBlogCount_control');
    }
  }
}

$ssBlogCount = new ssBlogCount();
add_action( 'plugins_loaded', array(&$ssBlogCount, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssBlogCount, 'setupActivation' ));

function get_ssBlogCount($settings = array()) {
	global $ssBlogCount;
	blogCount();
}

// Non-widget functions
function blogCount (){
$db = ssDbConnect();
$count = getBlogCount($db);
print "<p class=\"about\">ScienceSeeker collects posts from science blogs around the world, so you can find the latest science news and discussion on any topic.</p>
<br />
<p class=\"about\">Current blog count: $count</p>
<br />
<p class=\"about\">Are you a science blogger?</p>
<p class=\"about\"><a href=\"http://scienceseeker.org/submit/\">Submit your blog today</a>.</p>";
}
?>