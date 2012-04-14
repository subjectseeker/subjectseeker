<?php
/*
Plugin Name: SubjectSeeker Edit Image
Plugin URI: http://scienceseeker.org/
Description: Allows you to resize and crop images for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://www.scienceseeker.org/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssEditImage')) {
  class ssEditImage {
    function ssEditImage() {
      $this->version = "0.1";
    }
	
    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssEditImage($args) {
        extract($args);
        $options = get_option('widget_ssEditImage');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssEditImage();
        echo $after_widget;
      }
      function widget_ssEditImage_control() {
        $options = get_option('widget_ssEditImage');
        if ( $_POST['ssEditImage-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssEditImage-title']));
          update_option('widget_ssEditImage', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssEditImage-title">Title:<input class="widefat" name="ssEditImage-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssEditImage-submit" name="ssEditImage-submit" value="1" />';
      }
      register_sidebar_widget('ssEditImage', 'widget_ssEditImage');
      register_widget_control('ssEditImage', 'widget_ssEditImage_control');
    }
  }
}

$ssEditImage = new ssEditImage();
add_action( 'plugins_loaded', array(&$ssEditImage, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssEditImage, 'setupActivation' ));

function get_ssEditImage($settings = array()) {
  global $ssEditImage;
  editImage();
}

function editImage() {
	$step = $_REQUEST["step"];
  $db = ssDbConnect();
  if (is_user_logged_in()){
    global $current_user;
    get_currentuserinfo();
    $displayName = $current_user->user_login;
    $email = $current_user->user_email;
    $userId = addUser($displayName, $email, $db);
    $userPriv = getUserPrivilegeStatus($userId, $db);
		$personaId = addPersona($userId, $displayName, $db);
		
    print "<p>Hello, $displayName.</p>\n";	
		if ($userPriv > 0) { // admin or editor
		
			// Separate post ID from URL
			preg_match('/\d+$/', $_REQUEST["postId"], $matchResult);
			$postId = implode($matchResult);
			
			$image = $_FILES["image"];
			$extension = $image["type"];
			$size = $image["size"];
			
			if ((($extension == "image/gif") || ($extension == "image/jpeg") || ($extension == "image/pjpeg") || ($extension == "image/png")) && ($size < 1048576)) {
				if ($image["error"] > 0) {
					print "<p>Error: " . $image["error"] . "</p>";
				}
				else {
					global $imagedir;
					$imageName = urlencode(rand(0, 200) . $image["name"]);
					move_uploaded_file($image["tmp_name"], "$imagedir/tmp/$imageName");
				}
			}
			
			global $imagesUrl;
			$tmpLocation = "$imagesUrl/tmp/$imageName";
			list($width, $height) = getimagesize($tmpLocation);
			$halfW = $width - ($width/2);
			
			if (! $imageName) {
				print "<span class=\"ss-error\">Error: No image was uploaded. Please check that your image is at least 580 x 200 and under 1 MB.</span>";
			}
			elseif ($width < 580 || $height < 200) {
				print "<span class=\"ss-error\">Error: Your image must be at least 580 x 200</span>";
			}
			else {
				print "<p><h4>Name</h4><span class=\"subtle-text\">$imageName</span></p>
				<p><h4>Size</h4><span class=\"subtle-text\">".($size / 1024)." KB</span></p>
				<p><h4>Extension</h4><span class=\"subtle-text\">$extension</span></p>
				<p><form action=\"/subjectseeker/upload-file.php\" method=\"post\" onsubmit=\"return checkCoords();\">
				<input type=\"hidden\" id=\"x\" name=\"x\">
				<input type=\"hidden\" id=\"y\" name=\"y\">
				<input type=\"hidden\" id=\"w\" name=\"w\">
				<input type=\"hidden\" id=\"h\" name=\"h\">
				<input type=\"hidden\" name=\"imageName\" value=\"$imageName\">
				<input type=\"hidden\" name=\"personaId\" value=\"$personaId\">
				<input type=\"hidden\" name=\"postId\" value=\"$postId\">
				<input class=\"ss-button\" type=\"submit\" value=\"Crop Image\">
				</form>
				</p>
				<div class=\"ss-div\" style=\"\width: ".$width."px; height: ".$height."px;\"><div style=\"position: absolute; left: 50%; width: ".$width."px; height: ".$height."px; margin-left: -".$halfW."px; -webkit-box-shadow: 0px 4px 5px 3px rgba(0, 0, 0, 0.2); -moz-box-shadow: 0px 4px 5px 3px rgba(0, 0, 0, 0.2); box-shadow: 0px 4px 5px 3px rgba(0, 0, 0, 0.2);\"><img id=\"jcrop-target\" src=\"$tmpLocation\" title=\"Preview\"></div></div>";
			}
		} else { // not moderator or admin
			print "You are not authorized to use the image editor.<br />";
		}
  } else { // not logged in
    print "Please log in.";
  }
}
?>