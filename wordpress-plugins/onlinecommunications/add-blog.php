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

function determineStep()
{

  if (is_user_logged_in()){

    global $current_user;
    get_currentuserinfo();
    $displayName = $current_user->display_name;
    $email = $current_user->user_email;

    $step = $_REQUEST["step"];
    $blogId = $_REQUEST["blogId"];

    // Connect to DB.
    $db  = ssDbConnect();

    if ($step === null) {
      displayBlogForm(null, $db);
    } else if ($step === "blogInfo") {
      doAddBlog($db);
    } else if ($step === "verify") {
      doVerifyClaim($blogId, $displayName, $db);
    } else if ($step === "userAuthorLinkForm") {
      doLinkUserAndAuthor($displayName, $db);
    } else {
      print "ERROR: Unknown step $step.";
    }

    // DELETEME ssDbClose($db);
    // this line of code causes errors in other plugins on the same page
    // dunno why, but the doc says it is not necessary to explicitly close a db connection

  } else {
    print "You must log in before you can add a blog.<br />\n";
  }
}

function displayBlogForm ($errormsg, $db) {
  $blogname = $_REQUEST["blogname"];
  $blogurl = $_REQUEST["blogurl"];
  $blogsyndicationuri = $_REQUEST["blogsyndicationuri"];
  $blogdescription = $_REQUEST["blogdescription"];

  global $current_user;
  get_currentuserinfo();
  $displayName = $current_user->display_name;

  // If this is the first time this user has tried to interact with
  // the SS system, create a USER entry for them
  $userId = addUser($displayName, $email, $db);

  // Only active users can claim blogs
  $userStatus = getUserStatus($userId, $db);
  if ($userStatus != 0) {
    print "<p class=\"error\"><font color=\"red\">You cannot claim this blog as your account is not currently active. You may <a href='/contact-us/'>contact us</a> to ask for more information.</font></p>\n";
    return;
  }


?>

<h2>Add a new blog to the system</h2>
   <p>If you have a large number of blogs to add to the system, please <a href='/contact-us/'>contact us</a> to discuss a data upload.</p>
<form method="POST">
<input type="hidden" name="step" value="blogInfo" />
<?php

   if ($errormsg !== null) {
     print "<p><font color='red'>Error: $errormsg</font></p>\n";
   }


  print "Welcome, $displayName<br /><br />\n";

  print "<p>*Required field</p>\n<p>\n";
  print "*Blog name: <input type=\"text\" name=\"blogname\" size=\"40\" value=\"$blogname\"/>\n</p>\n<p>\n*Blog URL: <input type=\"text\" name=\"blogurl\" size=\"40\" value=\"$blogurl\" /><br />(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)";
  print "</p><p>*Blog syndication URL: <input type=\"text\" name=\"blogsyndicationuri\" size=\"40\" value=\"$blogsyndicationuri\" /> <br />(RSS or Atom feed. Must start with \"http://\", e.g., <em>http://feeds.feedburner.com/blogname/</em>.)";
  print "</p><p>Blog description:<br /><textarea name=\"blogdescription\" rows=\"5\" cols=\"70\">$blogdescription</textarea><br />\n";

  print "Blog topics: <select name='topic1'>\n";
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
  print "</select>\n";
?>

<p>
<p><input type="checkbox" name="userIsAuthor" /> I want to be identified as an author of this blog.</p>
<input type="submit" value="Add blog" />
</p>
</form>

<?php
}

function doAddBlog ($db) {
  $blogname = stripslashes($_REQUEST["blogname"]);
  $blogurl = stripslashes($_REQUEST["blogurl"]);
  $blogsyndicationuri = stripslashes($_REQUEST["blogsyndicationuri"]);
  $blogdescription = stripslashes($_REQUEST["blogdescription"]);
  $topic1 = stripslashes($_REQUEST["topic1"]);
  $topic2 = stripslashes($_REQUEST["topic2"]);
  $userIsAuthor = stripslashes($_REQUEST["userIsAuthor"]);

  global $current_user;
  get_currentuserinfo();
  $displayName = $current_user->display_name;
  $email = $current_user->user_email;
  
  // check that there is a name
  if ($blogname == null) {
	  displayblogForm("You need to submit a name for the blog", $db);
	  return;
  }

  // check that blog URL is fetchable
  if (! uriFetchable($blogurl)) {
    displayBlogForm("Unable to fetch the contents of your blog at $blogurl. Did you remember to put \"http://\" before the URL when you entered it? If you did, make sure your blog page is actually working, or <a href='/contact-us/'>contact us</a> to ask for help in resolving this problem.", $db);
    return;
  }

  // check that syndication feed is parseable
  $feed = getSimplePie($blogsyndicationuri);
  if ($feed->get_type() == 0) {
    displayBlogForm("Unable to parse feed at $blogsyndicationuri. Are you sure it is Atom or RSS?", $db);
    return;
  }
  
  // Check that the user has selected at least one topic
  if ($topic1 == -1 && $topic2 == -1) {
	  displayBlogForm("You need to choose at least one topic.", $db);
	  return;
  }
  
  // check that blog URL and blog syndication URL are not the same
  if ($blogurl == $blogsyndicationuri) {
	  displayBlogForm("The blog URL (homepage) and the blog syndication URL (RSS or Atom feed) need to be different.", $db);
	  return;
  }

  // If this is the first time this user has tried to interact with
  // the SS system, create a USER entry for them
  $userId = addUser($displayName, $email, $db);

  $addBlog = addBlog($blogname, $blogurl, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $userId, $db);

  $blogId = $addBlog["id"];

  if ($addBlog["errormsg"] === null) {
    displaySuccess();
  } else {
    // Blog is already in the system.
    print "<p class=\"error\"><font color=\"red\">ERROR: " . $addBlog["errormsg"] . "</font></p>\n";
    print "<p class=\"info\">This could be because it was pre-populated in our database, someone else submitted it, or because our editors rejected it.</p><p class=\"info\">If it was rejected, you should have received an email from us explaining why.</p><p class=\"info\">Otherwise, you can <a href=\"/claimblog/?blogId=$blogId\">claim the blog</a> to show that you are (one of) the author(s). See our <a href=\"/help\">help pages</a> for more information.</p>\n";
    return;
  }

  if ($userIsAuthor === "on") {
    doClaimBlog($blogId, $displayName, $email, $db);
  }
}

function displaySuccess() {
  echo "<p>Successfully added blog to the system. This blog will not be publicly displayed in the system until it has been approved by a $sitename editor.</p>";
}
?>
