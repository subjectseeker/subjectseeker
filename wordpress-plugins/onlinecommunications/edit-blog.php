<?php
/*
Plugin Name: SubjectSeeker edit blog
Plugin URI: http://scienceseeker.org/
Description: Edit an existing blog in the database
Author: Jessica P. Hekman
Version: 0.1
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

include_once "oc-includes.inc";

if (!class_exists('ocEditBlog')) {
  class ocEditBlog {
    function ocEditBlog() {
      $this->version = "0.1";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ocEditBlog($args) {
        extract($args);
        $options = get_option('widget_ocEditBlog');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ocEditBlog();
        echo $after_widget;
      }
      function widget_ocEditBlog_control() {
        $options = get_option('widget_ocEditBlog');
        if ( $_POST['ocEditBlog-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ocEditBlog-title']));
          update_option('widget_ocEditBlog', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ocEditBlog-title">Title:<input class="widefat" name="ocEditBlog-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ocEditBlog-submit" name="ocEditBlog-submit" value="1" />';
      }
      register_sidebar_widget('ocEditBlog', 'widget_ocEditBlog');
      register_widget_control('ocEditBlog', 'widget_ocEditBlog_control');
    }
  }
}

$ocEditBlog = new ocEditBlog();
add_action( 'plugins_loaded', array(&$ocEditBlog, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ocEditBlog, 'setupActivation' ));

function get_ocEditBlog($settings = array()) {
  global $ocEditBlog;

  determineEditStep();
}

/*
 * Non-plugin functions
 */

function determineEditStep()
{

  if (is_user_logged_in()){

    global $current_user;
    get_currentuserinfo();
    $displayName = $current_user->display_name;
    $email = $current_user->user_email;

    $step = $_REQUEST["step"];
    $blogId = $_REQUEST["blogId"];

    // Connect to DB.
    $db  = ocDbConnect();

    if ($step === null) {
      displayEditBlogsForm(null, $db);
    } else if ($step === "doEdit") {
      doEditBlog($db);
    } else if ($step === "verify") {
      doVerifyEditClaim($db);
    } else {
      print "ERROR: Unknown step $step.";
    }

    // DELETEME ocDbClose($db);
    // this line of code causes errors in other plugins on the same page
    // dunno why, but the doc says it is not necessary to explicitly close a db connection

  } else {
    print "You must log in before you can edit your blog.<br />\n";
  }
}

?>
