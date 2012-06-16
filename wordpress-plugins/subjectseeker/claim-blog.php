<?php
/*
Plugin Name: SubjectSeeker Claim Blog
Plugin URI: http://scienceseeker.org/
Description: Claim blog for SubjectSeeker tool
Author: Jessica P. Hekman
Version: 1
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssClaimBlog')) {
  class ssClaimBlog {
    function ssClaimBlog() {
      $this->version = "0.1";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssClaimBlog($args) {
        extract($args);
        $options = get_option('widget_ssClaimBlog');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssClaimBlog();
        echo $after_widget;
      }
      function widget_ssClaimBlog_control() {
        $options = get_option('widget_ssClaimBlog');
        if ( $_POST['ssClaimBlog-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssClaimBlog-title']));
          update_option('widget_ssClaimBlog', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssClaimBlog-title">Title:<input class="widefat" name="ssClaimBlog-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssClaimBlog-submit" name="ssClaimBlog-submit" value="1" />';
      }
      register_sidebar_widget('ssClaimBlog', 'widget_ssClaimBlog');
      register_widget_control('ssClaimBlog', 'widget_ssClaimBlog_control');
    }
  }
}

$ssClaimBlog = new ssClaimBlog();
add_action( 'plugins_loaded', array(&$ssClaimBlog, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssClaimBlog, 'setupActivation' ));

function get_ssClaimBlog($settings = array()) {
	global $ssClaimBlog;
	determineClaimStep();
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
    $displayName = $current_user->user_login;
    $email = $current_user->user_email;

    $step = $_REQUEST["step"];

    // Connect to DB.
    $db  = ssDbConnect();

    // If this is the first time this user has tried to interact with
    // the SS system, create a USER entry for them
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
