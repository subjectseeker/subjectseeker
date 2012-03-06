<?php
/*
Plugin Name: SubjectSeeker Edit Blog
Plugin URI: http://scienceseeker.org/
Description: Edit an existing blog in the database
Author: Jessica P. Hekman
Version: 0.1
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssEditBlog')) {
  class ssEditBlog {
    function ssEditBlog() {
      $this->version = "0.1";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssEditBlog($args) {
        extract($args);
        $options = get_option('widget_ssEditBlog');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssEditBlog();
        echo $after_widget;
      }
      function widget_ssEditBlog_control() {
        $options = get_option('widget_ssEditBlog');
        if ( $_POST['ssEditBlog-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssEditBlog-title']));
          update_option('widget_ssEditBlog', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssEditBlog-title">Title:<input class="widefat" name="ssEditBlog-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssEditBlog-submit" name="ssEditBlog-submit" value="1" />';
      }
      register_sidebar_widget('ssEditBlog', 'widget_ssEditBlog');
      register_widget_control('ssEditBlog', 'widget_ssEditBlog_control');
    }
  }
}

$ssEditBlog = new ssEditBlog();
add_action( 'plugins_loaded', array(&$ssEditBlog, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssEditBlog, 'setupActivation' ));

function get_ssEditBlog($settings = array()) {
  global $ssEditBlog;

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
    $displayName = $current_user->user_login;
    $email = $current_user->user_email;

    $step = $_REQUEST["step"];
    $blogId = $_REQUEST["blogId"];

    // Connect to DB.
    $db  = ssDbConnect();

    if ($step === null) {
      displayEditBlogsForm(null, $db);
    } else if ($step === "doEdit") {
      doEditBlog($db);
    } else if ($step === "verify") {
      doVerifyEditClaim($db);
    } else {
      print "ERROR: Unknown step $step.";
    }

    // DELETEME ssDbClose($db);
    // this line of code causes errors in other plugins on the same page
    // dunno why, but the doc says it is not necessary to explicitly close a db connection

  } else {
    print "You must log in before you can edit your blog.<br />\n";
  }
}

?>
