<?php
/*
Plugin Name: SubjectSeeker Add Blog
Plugin URI: http://scienceseeker.org/
Description: Add a new blog to the database
Author: Jessica P. Hekman
Version: 0.1
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssAddBlog')) {
  class ssAddBlog {
    function ssAddBlog() {
      $this->version = "0.1";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }
	
    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssAddBlog($args) {
        extract($args);
        $options = get_option('widget_ssAddBlog');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssAddBlog();
        echo $after_widget;
      }
      function widget_ssAddBlog_control() {
        $options = get_option('widget_ssAddBlog');
        if ( $_POST['ssAddBlog-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssAddBlog-title']));
          update_option('widget_ssAddBlog', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssAddBlog-title">Title:<input class="widefat" name="ssAddBlog-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssAddBlog-submit" name="ssAddBlog-submit" value="1" />';
      }
      register_sidebar_widget('ssAddBlog', 'widget_ssAddBlog');
      register_widget_control('ssAddBlog', 'widget_ssAddBlog_control');
    }
  }
}

$ssAddBlog = new ssAddBlog();
add_action( 'plugins_loaded', array(&$ssAddBlog, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssAddBlog, 'setupActivation' ));

function get_ssAddBlog($settings = array()) {
  global $ssAddBlog;

  determineStep();
}

/*
 * Non-plugin functions
 */

function determineStep() {
  if (is_user_logged_in()){
    global $current_user;
    get_currentuserinfo();
    $displayName = $current_user->user_login;
    $email = $current_user->user_email;
	}
	else {
		global $loginUrl;
		print "<p class=\"ss-warning\">You can claim your blog if you <a href=\"$loginUrl\" title=\"Log In Page\">log in</a>.</p>";
	}
	
	$step = $_REQUEST["step"];
	$blogId = $_REQUEST["blogId"];

	// Connect to DB.
	$db  = ssDbConnect();

	if ($step === null) {
		displayBlogForm(null, $db);
	} else if ($step === "blogInfo") {
		doAddBlog($db);
	} else if ($step === "verify") {
		if (! $displayName) {
			global $loginUrl;
			print "<p class=\"ss-error\">Error: You must <a href=\"$loginUrl\" title=\"Log In Page\">log in</a> to claim a blog.</p>\n";
			return;
		}
		doVerifyClaim($blogId, $displayName, $db);
	} else if ($step === "userAuthorLinkForm") {
		if (! $displayName) {
			global $loginUrl;
			print "<p class=\"ss-error\">Error: You must <a href=\"$loginUrl\" title=\"Log In Page\">log in</a> to claim a blog.</p>\n";
			return;
		}
		doLinkUserAndAuthor($displayName, $db);
	} else {
		print "ERROR: Unknown step $step.";
	}
}

function displayBlogForm ($errormsg, $db) {
  $blogName = $_REQUEST["blogname"];
  $blogUri = $_REQUEST["blogurl"];
  $blogSyndicationUri = $_REQUEST["blogsyndicationuri"];
  $blogDescription = $_REQUEST["blogdescription"];

	if (is_user_logged_in()){
		global $current_user;
		get_currentuserinfo();
		$displayName = $current_user->user_login;
	
		// If this is the first time this user has tried to interact with
		// the SS system, create a USER entry for them
		$userId = addUser($displayName, $email, $db);
	}

  // Only active users can claim blogs
  $userStatus = getUserStatus($userId, $db);
  if ($userStatus != 0) {
    print "<p class=\"ss-error\">You cannot claim this blog as your account is not currently active. You may <a href='/contact-us/'>contact us</a> to ask for more information.</p>\n";
    return;
  }


?>
<h2>Add a new source.</h2>
<p>Submit a new source to be aggregated by our system. If you have a large number of blogs to add to the system, please <a href='/contact-us/'>contact us</a> to discuss a data upload.</p>
<form method="POST">
<input type="hidden" name="step" value="blogInfo" />
<?php

	if ($errormsg !== null) {
		print "<p class=\"ss-error\">Error: $errormsg</p>\n";
	}

  // Attempt to prepopulate from URL if blogUri param set
  //
 $submitUri = $_REQUEST["blogUri"];
 if ($submitUri != NULL) {
   //   $submitUri = "http://dogzombie.blogspot.com/2012/06/mobile-veterinary-practice-and-federal.html";
   $feed = getSimplePie($submitUri);
   $blogName; $blogUri; $blogDescription; $blogSyndicationUri;

   if ($feed->error()) {
     print "<p class=\"ss-error\">Unable to find feed for $submitUri.</p>\n";
   } else {
     $blogName = $feed->get_title();
     $blogUri = $feed->get_link();
     $blogDescription = $feed->get_description();
     $blogSyndicationUri = $feed->subscribe_url();
   }
 }

  print "<h3>General Information</h3>
	<p>Blog Name: <input type=\"text\" name=\"blogName\" size=\"40\" value=\"$blogName\"/></p>\n
	<p>Blog URL: <input type=\"text\" name=\"blogUri\" size=\"40\" value=\"$blogUri\" /><br /><span class=\"subtle-text\">(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)</span></p>\n
	<p>Blog Syndication URL: <input type=\"text\" name=\"blogSyndicationUri\" size=\"40\" value=\"$blogSyndicationUri\" /><br /><span class=\"subtle-text\">(RSS or Atom feed. Must start with \"http://\", e.g., <em>http://feeds.feedburner.com/blogname/</em>.)</span></p>
	<p>Blog Description <span class=\"subtle-text\">(Optional)</span>:<br /><textarea name=\"blogDescription\" rows=\"5\" cols=\"60\">$blogDescription</textarea></p>\n";
  print "<p>Blog Topics: <select name='topic1'>\n";
  print "<option value='-1'>None</option>\n";
  $topicList = getTopicList(true, $db);
  while ($row = mysql_fetch_array($topicList)) {
    print "<option value='" . $row["TOPIC_ID"] . "'>" . $row["TOPIC_NAME"] . "</option>\n";
  }
  print "</select>&nbsp;<select name='topic2'>\n";
  print "<option value='-1'> None</option>\n";
  $topicList = getTopicList(true, $db);
  while ($row = mysql_fetch_array($topicList)) {
    print "<option value='" . $row["TOPIC_ID"] . "'> " . $row["TOPIC_NAME"] . "</option>\n";
  }
  print "</select></p>\n<p>";
	if ($userId) {
		print "<p><input type=\"checkbox\" name=\"userIsAuthor\" /> I want to be identified as an author of this blog.</p>";
	}
	print "<hr class=\"ss-div-2\" />
	<h3>Social Networks</h3>
	<p>Blog Twitter Handle <span class=\"subtle-text\">(Optional)</span>: <input type=\"text\" name=\"twitterHandle\" size=\"40\" value=\"\"/></p>\n
	<hr class=\"ss-div-2\" />
	<input class=\"ss-button\" type=\"submit\" value=\"Add Blog\" />
	</form>";
}

function doAddBlog ($db) {
  $blogName = $_REQUEST["blogName"];
  $blogUri = $_REQUEST["blogUri"];
  $blogSyndicationUri = $_REQUEST["blogSyndicationUri"];
  $blogDescription = $_REQUEST["blogDescription"];
  $topic1 = $_REQUEST["topic1"];
  $topic2 = $_REQUEST["topic2"];
  $twitterHandle = $_REQUEST["twitterHandle"];
  $userIsAuthor = $_REQUEST["userIsAuthor"];

  global $current_user;
  get_currentuserinfo();
  $displayName = $current_user->user_login;
  $email = $current_user->user_email;
  $errors = checkBlogData(NULL, $blogName, $blogUri, $blogSyndicationUri, $blogDescription, NULL, $topic1, $topic2, $twitterHandle, $userId, $db);

  if ($errors) {
    print "<ul class=\"ss-error\">$errors</ul>";
  }
  else {
    // If this is the first time this user has tried to interact with
    // the SS system, create a USER entry for them
    $userId = addUser($displayName, $email, $db);
    $userPriv = getUserPrivilegeStatus($userId, $db);
    $addBlog = addBlog($blogName, $blogUri, $blogSyndicationUri, $blogDescription, $topic1, $topic2, $userId, $db);
    $blogId = $addBlog["id"];

    addBlogSocialAccount (1, $twitterHandle, $blogId, $db);

    if ($addBlog["errormsg"] === null) {
      echo "<p>Successfully added blog to the system.</p>";
      if ($userPriv == 0) {
        echo "<p>This source will not be publicly displayed in the system until it has been approved by an editor.</p>";
      }
    } else {
      // Blog is already in the system.
      print "<p class=\"ss-error\">ERROR: " . $addBlog["errormsg"] . "</p>\n";
      print "<p class=\"info\">This could be because it was pre-populated in our database, someone else submitted it, or because our editors rejected it.</p><p class=\"info\">If it was rejected, you should have received an email from us explaining why.</p><p class=\"info\">Otherwise, you can <a href=\"/claimblog/?blogId=$blogId\">claim the blog</a> to show that you are (one of) the author(s). See our <a href=\"/help\">help pages</a> for more information.</p>\n";
      return;
    }

    if ($userIsAuthor === "on") {
      doClaimBlog($blogId, $displayName, $email, $db);
    }
  }
}

?>
