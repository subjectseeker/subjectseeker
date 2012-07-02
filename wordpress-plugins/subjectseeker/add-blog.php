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
	// Connect to DB.
	$db  = ssDbConnect();
	
  if (is_user_logged_in()){
    global $current_user;
    get_currentuserinfo();
    $displayName = $current_user->user_login;
    $email = $current_user->user_email;
		$userId = addUser($displayName, $email, $db);
	}
	else {
		global $loginUrl;
		print "<p class=\"ss-warning\">You can claim your blog if you <a href=\"$loginUrl\" title=\"Log In Page\">log in</a>.</p>";
	}

	$step = $_REQUEST["step"];
	$blogId = $_REQUEST["blogId"];
	$submitUrl = $_REQUEST["submitUrl"];

	if ($step === null) {
		if ($submitUrl == null) {
			displayShortBlogForm(null, $db);
		} else {
			displayBlogForm(null, $db);
		}
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
		doLinkUserAndAuthor($userId, $displayName, $db);
	} else {
		print "<p class=\"ss-error\">ERROR: Unknown step $step.</p>";
	}
}

function displayShortBlogForm ($errormsg, $db) {
  $submitUrl = $_REQUEST["submitUrl"];
  $blogSyndicationUri = $_REQUEST["blogsyndicationuri"];
  $userId;

  if (is_user_logged_in()){
    global $current_user;
    get_currentuserinfo();
    $displayName = $current_user->user_login;

    // If this is the first time this user has tried to interact with
    // the SS system, create a USER entry for them
    $userId = addUser($displayName, $email, $db);
  }

?>
<p>Submit a new blog to be aggregated by our system. If you have a large number of blogs to add to the system, please <a href='/contact-us/'>contact us</a> to discuss a data upload.</p>
<form method="POST">
<?php

   if ($errormsg !== null) {
     print "$errormsg\n";
   }

  print "<h3>Blog Location</h3>
	<div class=\"center-text\">
	<p class=\"margin-bottom-small\">Please enter either the URL of the blog or the URL of the blog's feed (RSS or Atom):</p>
	<div class=\"margin-bottom-small\"><input class=\"big-input\" type=\"text\" name=\"submitUrl\" size=\"40\" value=\"$submitUrl\" /></div>
	<p class=\"subtle-text\">(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)</p>\n
	<p><input class=\"big-button\" type=\"submit\" value=\"Next step\" /></p>
	</div>
	</form>";
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

?>
<p>Submit a new site to be aggregated by our system. If you have a large number of blogs to add to the system, please <a href='/contact-us/'>contact us</a> to discuss a data upload.</p>
<?php

	// TODO: Looks like we are not using this error area, remove it?
	if ($errormsg !== null) {
		print "<p class=\"ss-error\">Error: $errormsg</p>\n";
	}

  // Attempt to prepopulate from URL if submitUrl param set
	$submitUri = $_REQUEST["submitUrl"];
	if ($submitUri != NULL) {
		$feed = @getSimplePie($submitUri);
		$blogName; $blogUri; $blogDescription; $blogSyndicationUri;

   if ($feed->error()) {
     print "<p class=\"ss-error\">Unable to find feed for $submitUri. You can enter the address manually below.</p>\n";
   } else {
     $blogName = $feed->get_title();
     $blogUri = $feed->get_link();
     $blogDescription = $feed->get_description();
     $blogSyndicationUri = $feed->subscribe_url();

     $blogId = getBlogByAltUri($blogUri, $db);
     if ($blogId != null) {
       print "<p class=\"ss-error\">This blog is already in the system.</p>\n";
     } else {
       $blogId = getBlogByAltSyndicationUri($blogSyndicationUri, $db);
       if ($blogId != null) {
         print "<p class=\"ss-error\">This feed is already in the system.</p>\n";
       }
     }
		 // TODO: if blog/feed already found, take us to the profile page for the blog -- once we have one
   }
 }
 
 submitBlogForm ($blogName, $blogUri, $blogDescription, $blogSyndicationUri, NULL, $userId, $db);

}

function submitBlogForm ($blogName, $blogUri, $blogDescription, $blogSyndicationUri, $twitterHandle, $userId, $db) {
	print "<form method=\"POST\">
<input type=\"hidden\" name=\"step\" value=\"blogInfo\" />
	<h3>General Information</h3>
	<p>Blog Name: <input type=\"text\" name=\"blogName\" size=\"40\" value=\"".htmlspecialchars($blogName, ENT_QUOTES)."\"/></p>\n
	<p>Blog URL: <input type=\"text\" name=\"blogUri\" size=\"40\" value=\"".htmlspecialchars($blogUri, ENT_QUOTES)."\" /><br /><span class=\"subtle-text\">(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)</span></p>\n
	<p>Blog Syndication URL: <input type=\"text\" name=\"blogSyndicationUri\" size=\"40\" value=\"".htmlspecialchars($blogSyndicationUri, ENT_QUOTES)."\" /><br /><span class=\"subtle-text\">(RSS or Atom feed. Must start with \"http://\", e.g., <em>http://feeds.feedburner.com/blogname/</em>.)</span></p>
	<p>Blog Description <span class=\"subtle-text\">(Optional)</span>:<br /><textarea name=\"blogDescription\" rows=\"5\" cols=\"60\">$blogDescription</textarea></p>\n
	<p>Blog Topics: <select name='topic1'>\n
	<option value='-1'>None</option>\n";
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
	<p>Blog Twitter Handle <span class=\"subtle-text\">(Optional)</span>: <input type=\"text\" name=\"twitterHandle\" size=\"40\" value=\"".htmlspecialchars($twitterHandle, ENT_QUOTES)."\"/></p>\n
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
  $userId;
	
	// Only get user info if logged in
	if (is_user_logged_in()){
		global $current_user;
		get_currentuserinfo();
		$displayName = $current_user->user_login;
		$email = $current_user->user_email;
		$userId = addUser($displayName, $email, $db);
		$userPriv = getUserPrivilegeStatus($userId, $db);
	}
	
	$errors = checkBlogData(NULL, $blogName, $blogUri, $blogSyndicationUri, $blogDescription, NULL, $topic1, $topic2, $twitterHandle, $userId, $db);
	
	if ($errors) {
		print "$errors";
		submitBlogForm ($blogName, $blogUri, $blogDescription, $blogSyndicationUri, $twitterHandle, $userId, $db);
	}
	else {
		$addBlog = addBlog($blogName, $blogUri, $blogSyndicationUri, $blogDescription, $topic1, $topic2, $userId, $db);
	
		$blogId = $addBlog["id"];
		
		if ($twitterHandle) {
			addBlogSocialAccount($twitterHandle, 1, $blogId, $db);
		}
	
		if ($addBlog["errormsg"] === null) {
			echo "<p class=\"ss-successful\">Successfully added blog to the system.</p>";
			if ($userPriv == 0) {
				global $submitSite;
				echo "<p>This site will not be publicly displayed in the system until it has been approved by an editor.</p>";
			}
			if ($userIsAuthor && $email) {
				global $sitename;
				global $contactEmail;
				$subject = "Site Submission Status: Pending";
				$message = "Hello, ".$displayName."!

Thanks for submitting ".$blogName." to ".$sitename.".
					
Before this site appears in the system, it must be approved by one of our editors. We will notify you when this happens if you have claimed your site.
					
If you have any questions, feel free to contact us at ".$contactEmail."
					
The ".$sitename." Team.";
				sendMail($email, $subject, $message);
			}
			else {
				echo "<p><a class=\"ss-button\" href=\"/\">Go to Home Page</a> <a class=\"ss-button\" href=\"$submitSite\">Submit another site</a></p>";
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
