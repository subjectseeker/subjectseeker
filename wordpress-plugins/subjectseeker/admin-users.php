<?php
/*
Plugin Name: SubjectSeeker Administer Users
Plugin URI: http://scienceseeker.org/
Description: Administer Users for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://www.binaryparticle.com
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssAdminUsers')) {
  class ssAdminUsers {
    function ssAdminUsers() {
      $this->version = "0.1";
    }
	
    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssAdminUsers($args) {
        extract($args);
        $options = get_option('widget_ssAdminUsers');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssAdminUsers();
        echo $after_widget;
      }
      function widget_ssAdminUsers_control() {
        $options = get_option('widget_ssAdminUsers');
        if ( $_POST['ssAdminUsers-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssAdminUsers-title']));
          update_option('widget_ssAdminUsers', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssAdminUsers-title">Title:<input class="widefat" name="ssAdminUsers-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssAdminUsers-submit" name="ssAdminUsers-submit" value="1" />';
      }
      register_sidebar_widget('ssAdminUsers', 'widget_ssAdminUsers');
      register_widget_control('ssAdminUsers', 'widget_ssAdminUsers_control');
    }
  }
}

$ssAdminUsers = new ssAdminUsers();
add_action( 'plugins_loaded', array(&$ssAdminUsers, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssAdminUsers, 'setupActivation' ));

function get_ssAdminUsers($settings = array()) {
  global $ssAdminUsers;
  doAdminUsers();
}

function doAdminUsers() {
	$step = $_REQUEST["step"];
  $db = ssDbConnect();
  if (is_user_logged_in()){
    global $current_user;
		global $wpdb;
    get_currentuserinfo();
    $displayName = $current_user->display_name;
    $email = $current_user->user_email;
		$userId = addUser($displayName, $email, $db);
    $userPriv = getUserPrivilegeStatus($userId, $db);
		
    print "<p>Hello, $displayName.</p>\n";	
		if ($userPriv > 1) { // moderator or admin
			$arrange = $_REQUEST["arrange"];
			$order = $_REQUEST["order"];
			$pagesize = $_REQUEST["n"];
			$offset = $_REQUEST["offset"];
			if ($arrange == null) {
				$arrange = "USER_ID";
			}
			if ($order == null) {
				$order = "DESC";
			}
			if ($pagesize == null || is_numeric($pagesize) == FALSE) {
				$pagesize = "30";
			}
			if ($offset == null || is_numeric($offset) == FALSE) {
				$offset = "0";
			}
			print "<form method=\"GET\">\n";
			print "<input type=\"hidden\" name=\"filters\" value=\"filters\" />";
			print "Sort by: ";
			print "<select name='arrange'>\n";
			print "<option value='USER_ID'";
			if ($arrange == "USER_ID") {
				print " selected";
			}
			print ">Id</option>\n";
			print "<option value='USER_NAME'";
			if ($arrange == "USER_NAME") {
				print " selected";
			}
			print ">Name</option>\n";
			print "<option value='USER_STATUS_ID'";
			if ($arrange == "USER_STATUS_ID") {
				print " selected";
			}
			print ">Status</option>\n";
			print "<option value='USER_PRIVILEGE_ID'";
			if ($arrange == "USER_PRIVILEGE_ID") {
				print " selected";
			}
			print ">Privilege</option>\n";
			print "<option value='EMAIL_ADDRESS'";
			if ($arrange == "EMAIL_ADDRESS") {
				print " selected";
			}
			print ">E-mail</option>\n";
			print "</select>\n";
			print " | <select name='order'>\n";
			print "<option value='ASC'";
			if ($order == "ASC") {
				print " selected";
			}
			print ">Ascending</option>\n";
			print "<option value='DESC'";
			if ($order == "DESC") {
				print " selected";
			}
			print ">Descending</option>\n";
			print "</select><br />\n";
			print "Entries per page: <input type=\"text\" name=\"n\" size=\"2\" value=\"$pagesize\"/>";
			print " | Start at: <input type=\"text\" name=\"offset\" size=\"2\" value=\"$offset\"/>";
			print " <input class=\"ss-button\" type=\"submit\" value=\"Go\" />";
			print "</form><br />";
			if ($step != null) {
				$userID = stripslashes($_REQUEST["userId"]);
				$userName = stripslashes($_REQUEST["userName"]);
				$userStatus = stripslashes($_REQUEST["userStatus"]);
				$userEmail = stripslashes($_REQUEST["userEmail"]);
				$userPrivilege = stripslashes($_REQUEST["userPrivilege"]);
				$oldUserName = getUserName($userID, $db);
				$result = checkUserData($userID, $userName, $userStatus, $userEmail, $userPrivilege, $userId, $userPriv, $displayname, $db);
				if ($step == 'edit') {
					if ($result == NULL) {				
						editUser ($userID, $userName, $userStatus, $userEmail, $userPrivilege, $oldUserName, $wpdb, $db);
						print "<p>$userName (id $userID) was updated.</p>";  
						} 
					if ($result != NULL) {
						print "<p>$oldUserName (id $userID):<ul class=\"ss-error\">$result</ul></p>";
						print "<form class=\"ss-div\" method=\"POST\">
						<input type=\"hidden\" name=\"step\" value=\"confirm\" />
						<input type=\"hidden\" name=\"userId\" value=\"$userID\" />
						<input type=\"hidden\" name=\"userName\" value=\"$userName\" />
						<input type=\"hidden\" name=\"userStatus\" value=\"$userStatus\" />
						<input type=\"hidden\" name=\"userEmail\" value=\"$userEmail\" />
						<input type=\"hidden\" name=\"userPrivilege\" value=\"$userPrivilege\" />
						<p>There has been an error, are you sure you want to apply these changes?</p>
						<input class=\"ss-button\" name=\"confirm\" type=\"submit\" value=\"Yes\" /> <input class=\"ss-button\" type=\"Submit\" value=\"No\" />
						</form>";
					}
				}
				if ($step == 'confirm') {
					$confirm = $_REQUEST["confirm"];
					if ($confirm == 'Yes') {
						editUser ($userID, $userName, $userStatus, $userEmail, $userPrivilege, $oldUserName, $wpdb, $db);
						print "<p>$userName (id $userID) was updated.</p>";
					}
					else {
						$oldUserName = getUserName($userID, $db);
						print "<p>$oldUserName (id $userID) was not updated.</p>";
					}
				}
			}
			$baseUrl = removeParams();
			$userList = getUsers ($arrange, $order, $pagesize, $offset, $db);
			if ($userList == null) {
				print "There are no more users in the system.<br />";
			}
			else {
				foreach ($userList as $user) {
					$userID = $user["id"];
					$userName = $user["name"];
					$userStatusId = $user["status"];
					$userPrivilegeId = $user["privilege"];
					$userEmail = $user["email"];
					$userStatus = ucwords(userStatusIdToName ($userStatusId, $db));
					$userPrivilege = ucwords(userPrivilegeIdToName ($userPrivilegeId, $db));
					print "<p>$userID | $userName | $userStatus | $userPrivilege | <a class=\"ss-button\" id=\"showForm-$userID\" href=\"javascript:;\" onmousedown=\"toggleSlide('container-$userID'), toggleButton('showForm-$userID');\">Show</a></p>";
					print "<div id=\"container-$userID\" style=\"display:none; overflow:hidden; height:204px;\">";
					print "<div class=\"ss-form\">";
					print "<form name=\"form-$userID\" method=\"POST\">\n";
					print "<input type=\"hidden\" name=\"step\" value=\"edit\" />";
					if ($errormsg !== null) {
						print "<p><font color='red'>Error: $errormsg</font></p>\n";
					}
					print "<input type=\"hidden\" name=\"userId\" value=\"$userID\" />\n";
					print "<p>*Required field</p>\n\n";
					print "<p>*User name: <input type=\"text\" name=\"userName\" size=\"40\" value=\"$userName\"/></p>\n";
					print "<p>*User e-mail: <input type=\"text\" name=\"userEmail\" size=\"40\" value=\"$userEmail\"/></p>\n";
					print "<p>*User Status: <select name='userStatus'>\n";
					$statusList = getUserStatusList ($db);
					while ($row = mysql_fetch_array($statusList)) {
						print "<option value='" . $row["USER_STATUS_ID"] . "'";
						if ($row["USER_STATUS_ID"] == $userStatusId) {
							print " selected";
									}
						print ">" . ucwords($row["USER_STATUS_DESCRIPTION"]) . "</option>\n";
					}
					print "</select></p>\n";
					print "<p>*User Privilege: <select name='userPrivilege'>\n";
					$privilegeList = getUserPrivilegeList ($db);
					while ($row = mysql_fetch_array($privilegeList)) {
						print "<option value='" . $row["USER_PRIVILEGE_ID"] . "'";
						if ($row["USER_PRIVILEGE_ID"] == $userPrivilegeId) {
							print " selected";
									}
						print ">" . ucwords($row["USER_PRIVILEGE_DESCRIPTION"]) . "</option>\n";
					}
					print "</select></p>\n";
					print "<input id=\"submit-$userID\" class=\"ss-button\"type=\"submit\" value=\"Submit\" /><br />\n";
					print "</form>
					</div>
					</div>";
				}
				$nextOffset = $offset + $pagesize;
				$nextParams = "?filters=filters&arrange=$arrange&order=$order&n=$pagesize&offset=$nextOffset";
				$nextUrl = $baseUrl . $nextParams;
				print "<div class=\"alignright\"><h4><a title=\"Next page\" href=\"$nextUrl\"><b>Next Page »</b></a></h4></div>";
			}
			if ($offset > 0) {
			$previousOffset = $offset - $pagesize;
			$previousParams = "?filters=filters&arrange=$arrange&order=$order&n=$pagesize&offset=$previousOffset";
			$previousUrl = $baseUrl . $previousParams;
			print "<div class=\"alignleft\"><h4><a title=\"Previous page\" href=\"$previousUrl\"><b>« Previous Page</b></a></h4></div><br />";
			}
		} else { # not moderator or admin
			print "You are not authorized to administrate users.<br />";
		}
  } else {
    print "Please log in.";
  }
}
?>