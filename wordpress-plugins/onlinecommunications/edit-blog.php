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

    // Connect to DB.
    $db  = ocDbConnect();

    if ($step === null) {
      displayEditBlogsForm(null, $db);
    } else if ($step === "doEdit") {
      doEditBlog($db);
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

function displayEditBlogsForm ($msg, $db) {
  $blogname = stripslashes($_REQUEST["blogname"]);
  $blogurl = stripslashes($_REQUEST["blogurl"]);
  $blogsyndicationuri = stripslashes($_REQUEST["blogsyndicationuri"]);
  $blogdescription = stripslashes($_REQUEST["blogdescription"]);

  global $current_user;
  get_currentuserinfo();
  $displayName = $current_user->display_name;

  print "Welcome, $displayName<br /><br />\n";

  if ($msg) {
    print "<p class='msg'>$msg</p><br />";
  }

  // If this is the first time this user has tried to interact with
  // the OC system, create a USER entry for them
  $userId = addUser($displayName, $email, $db);

  $blogIds = getBlogIdsByUserId($userId, $db);
  if (sizeof($blogIds) == 0) {
    print "<p class='msg'>$displayName has no blogs.</p><br />";
    return;
  }

  $blogData = blogIdsToBlogData($blogIds, $db);

  while ($row = mysql_fetch_array($blogData)) {
    displayEditBlogForm($db, $row);
  }

  // Only active users can edit blogs
  $userStatus = getUserPrivilegeStatus($userId, $db);
  if ($userStatus != 0) {
    print "<p class=\"error\"><font color=\"red\">You cannot edit your blog as your account is not currently active. You may <a href='/contact-us/'>contact us</a> to ask for more information.</font></p>\n";
    return;
  }
}

function displayEditBlogForm($db, $data) {
  $blogId = $data["BLOG_ID"];
  $blogname = $data["BLOG_NAME"];
  $blogurl = $data["BLOG_URI"];
  $blogsyndicationuri = $data["BLOG_SYNDICATION_URI"];
  $blogdescription = $data["BLOG_DESCRIPTION"];
  $blogtopics = getBlogTopics($blogId, $db);
  //$topic1 = $_REQUEST["topic1"];
  //$topic2 = $_REQUEST["topic2"];

  print "<h2>Edit $blogname</h2>\n";

?>
<form method="POST">
<input type="hidden" name="step" value="doEdit" />
<?php

   print "<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />\n";

   if ($errormsg !== null) {
     print "<p><font color='red'>Error: $errormsg</font></p>\n";
   }

  print "<p>*Required field</p>\n<p>\n";
  print "*Blog name: <input type=\"text\" name=\"blogname\" size=\"40\" value=\"$blogname\"/>\n</p>\n<p>\n*Blog URL: <input type=\"text\" name=\"blogurl\" size=\"40\" value=\"$blogurl\" /><br />(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)";
  print "</p><p>*Blog syndication URL: <input type=\"text\" name=\"blogsyndicationuri\" size=\"40\" value=\"$blogsyndicationuri\" /> <br />(RSS or Atom feed. Must start with \"http://\", e.g., <em>http://feeds.feedburner.com/blogname/</em>.)";
  print "</p><p>Blog description:<br /><textarea name=\"blogdescription\" rows=\"5\" cols=\"70\">$blogdescription</textarea><br />\n";

  print "Blog topic: <select name='topic1'>\n";
  print "<option value='-1'>None</option>\n";
  $topicList = getTopicList(true, $db);
  while ($row = mysql_fetch_array($topicList)) {
    print "<option value='" . $row["TOPIC_ID"] . "'";
    if ($row["TOPIC_ID"] == $blogtopics[0]) {
      print " selected";
    }
    print ">" . $row["TOPIC_NAME"] . "</option>\n";
  }
  print "</select><br />\n";

  print "Blog topic: <select name='topic2'>\n";
  print "<option value='-1'> None</option>\n";
  $topicList = getTopicList(true, $db);
  while ($row = mysql_fetch_array($topicList)) {
    print "<option value='" . $row["TOPIC_ID"] . "'";
    if ($row["TOPIC_ID"] == $blogtopics[1]) {
      print " selected";
    }
    print ">" . $row["TOPIC_NAME"] . "</option>\n";
  }
  print "</select>\n";
?>

<p>
<input type="submit" value="Edit blog info" />
</p>
</form>
<p><hr /></p>
<?php

}

function doEditBlog ($db) {
  $blogId = stripslashes($_REQUEST["blogId"]);
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

  // If this is the first time this user has tried to interact with
  // the OC system, create a USER entry for them
  $userId = addUser($displayName, $email, $db);

  // TODO
  // If user is requesting a blogUrl or blogsyndicationuri change, ensure that they own the new url

  $result = editBlog($blogId, $blogname, $blogurl, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $userId, $displayName, $db);

  if ($result == NULL) {
    displayEditBlogsForm("$blogname was updated.", $db);
    return;
  } else {
    displayEditBlogsForm("ERROR: $result", $db);
  }
}

?>
