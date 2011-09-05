<?php
/*
Plugin Name: OnlineCommunications Claim Blog
Plugin URI: http://scienceseeker.org/
Description: Claim blog for OnlineCommunications tool
Author: Jessica P. Hekman
Version: 1
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

include_once "oc-includes.inc";

if (!class_exists('ocClaimBlog')) {
  class ocClaimBlog {
    function ocClaimBlog() {
      $this->version = "0.1";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ocClaimBlog($args) {
        extract($args);
        $options = get_option('widget_ocClaimBlog');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ocClaimBlog();
        echo $after_widget;
      }
      function widget_ocClaimBlog_control() {
        $options = get_option('widget_ocClaimBlog');
        if ( $_POST['ocClaimBlog-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ocClaimBlog-title']));
          update_option('widget_ocClaimBlog', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ocClaimBlog-title">Title:<input class="widefat" name="ocClaimBlog-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ocClaimBlog-submit" name="ocClaimBlog-submit" value="1" />';
      }
      register_sidebar_widget('ocClaimBlog', 'widget_ocClaimBlog');
      register_widget_control('ocClaimBlog', 'widget_ocClaimBlog_control');
    }
  }
}

$ocClaimBlog = new ocClaimBlog();
add_action( 'plugins_loaded', array(&$ocClaimBlog, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ocClaimBlog, 'setupActivation' ));

function get_ocClaimBlog($settings = array()) {
	global $ocClaimBlog;

        determineClaimStep($settings);
}

// Non-widget functions

function determineClaimStep() {

  $blogId = $_REQUEST["blogId"];

  if ($blogId == null) {
    print "<p>ERROR: No blog specified to claim.</p>";
    return;
  }

  if (is_user_logged_in()){

    global $current_user;
    get_currentuserinfo();
    $displayName = $current_user->display_name;
    $email = $current_user->user_email;

    $step = $_REQUEST["step"];

    // Connect to DB.
    $db  = ocDbConnect();

    // If this is the first time this user has tried to interact with
    // the OC system, create a USER entry for them
    $userId = addUser($displayName, $email, $db);

    // If there is already a verified claim, move ahead to linking things
    if ($step == null && retrieveVerifiedClaimToken($blogId, $userId, $db)) {
      displayUserAuthorLinkForm($blogId, $userId, $displayName, $db);
    } else if ($step === null) {
      doClaimBlog($blogId, $displayName, $email, $db);
    } else if ($step === "verify") {
      doVerifyClaim($blogId, $displayName, $db);
    } else if ($step === "userAuthorLinkForm") {
      doLinkUserAndAuthor($displayName, $db);
    } else {
      print "ERROR: Unknown step $step.";
    }
  } else {
    print "You must log in before you can add a blog.<br />\n";
  }
}

?>
