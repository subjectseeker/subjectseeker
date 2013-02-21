<?php
/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function editImage() {
	global $scriptsUrl;
	global $thirdPartyUrl;
	$db = ssDbConnect();
	$content = "<script type=\"text/javascript\" src=\"".$thirdPartyUrl."/jcrop/jquery.Jcrop.js\" ?>\"></script>
	<script type=\"text/javascript\">
	function updateCoords(c) {
		$('#x').val(c.x);
		$('#y').val(c.y);
		$('#w').val(c.w);
		$('#h').val(c.h);
		
		$('#crop-button').removeAttr('disabled');
	};
	$(document).ready(function() {
		$('#crop-form').submit(function() {
			if(! $('#w').val()) {
				$('#notification-area').slideDown();
				$('#notification-content').html('<p>You must select an area of the image to upload.</p>');
				$('#crop-button').attr('disabled', true);
				return false;
			}
		});
		
		$('#jcrop-avatar').Jcrop({
			minSize: [ 80, 80 ],
			setSelect: [ 0, 0, 80, 80 ],
			boxWidth: 600,
			aspectRatio: 1/1,
			onSelect: updateCoords,
		},function() { 
			$('#crop-button').removeAttr('disabled');
		});
		
		$('#jcrop-header').Jcrop({
			minSize: [ 580, 200 ],
			setSelect: [ 0, 0, 580, 200 ],
			boxWidth: 600,
			aspectRatio: 59/20,
			onSelect: updateCoords,
		},function() { 
			$('#crop-button').removeAttr('disabled');
		});
		
		$('#jcrop-group-banner').Jcrop({
			minSize: [ 1000, 125 ],
			setSelect: [ 0, 0, 1000, 125 ],
			boxWidth: 600,
			aspectRatio: 8/1,
			onSelect: updateCoords,
		},function() { 
			$('#crop-button').removeAttr('disabled');
		});
		
		$('#jcrop-user-banner').Jcrop({
			boxWidth: 600,
			aspectRatio: 8/1,
			onSelect: updateCoords,
		},function() { 
			$('#crop-button').removeAttr('disabled');
		});
		
		$('#jcrop-site-banner').Jcrop({
			boxWidth: 600,
			aspectRatio: 8/1,
			onSelect: updateCoords,
		},function() { 
			$('#crop-button').removeAttr('disabled');
		});
	});
	</script>";
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
		
		// Get Image
		$image = $_FILES["image"];
		$extension = $image["type"];
		$size = $image["size"];
		
		// Check that file is secure
		if ($image && (($extension == "image/gif") || ($extension == "image/jpeg") || ($extension == "image/pjpeg") || ($extension == "image/png") && ($size < 8388608))) {
			if ($image["error"] > 0) {
				return $content .= "<p class=\"ss-error\">" . $image["error"] . "</p>";
			}
			// Get image folder and url from the server.
			global $imagedir;
			global $imagesUrl;
			
			//Assing name with random number
			$imageName = urlencode(rand(0, 999) . $image["name"]);
			
			// Move image to our folder.
			move_uploaded_file($image["tmp_name"], "$imagedir/tmp/$imageName");
			$tmpLocation = "$imagesUrl/tmp/$imageName";
			
			// Get width and height
			list($width, $height) = getimagesize($tmpLocation);
			
			$target = "avatar";
			
			// Check if editor is uploading a header.
			if (isset($_GET["type"]) && $_GET["type"] == "header" && $userPriv > 0) {		
				if ($width < 580 || $height < 200) {
					return $content .= "<p class=\"ss-error\">Your image must be at least 580 x 200.</p>";
				}
				$target = "header";
			} elseif (isset($_GET["type"]) && $_GET["type"] == "group-banner") {	
				$target = "group-banner";
				
			} elseif (isset($_GET["type"]) && $_GET["type"] == "user-banner") {	
				$target = "user-banner";
				
			} elseif (isset($_GET["type"]) && $_GET["type"] == "site-banner") {	
				$target = "site-banner";
				
			} elseif ($width < 80 || $height < 80) {
				return $content .= "<p class=\"ss-error\">Your avatar must be at least 80 x 80.</p>";
			}
			
			global $homeUrl;
			$originalUrl = $homeUrl;
			if (isset($_REQUEST["url"])) {
				$originalUrl = $_REQUEST["url"];
			}
			
			$content .= "<div class=\"box-title\">Image Cropper</div>
			<div class=\"floater-wrapper\" style=\"margin-bottom: 15px;\">
			<div class=\"alignleft\" style=\"margin-right: 40px;\"><h4>Name</h4><span class=\"subtle-text\">$imageName</span></div>
			<div class=\"alignleft\" style=\"margin-right: 40px;\"><h4>Size</h4><span class=\"subtle-text\">".($size / 1024)." KB</span></div>
			<div class=\"alignleft\" style=\"margin-right: 40px;\"><h4>Extension</h4><span class=\"subtle-text\">$extension</span></div>
			</div>
			<form id=\"crop-form\" class=\"margin-bottom\" action=\"$scriptsUrl/upload-image.php\" method=\"post\">
			<input type=\"hidden\" id=\"x\" name=\"x\" />
			<input type=\"hidden\" id=\"y\" name=\"y\" />
			<input type=\"hidden\" id=\"w\" name=\"w\" />
			<input type=\"hidden\" id=\"h\" name=\"h\" />
			<input type=\"hidden\" name=\"url\" value=\"$originalUrl\" />
			<input type=\"hidden\" name=\"imageName\" value=\"$imageName\" />";
			if ($userPriv > 0) {
				if (isset($_POST["userId"])) {
					$content .= "<input type=\"hidden\" name=\"userId\" value=\"".$_POST["userId"]."\" />";
				} elseif (isset($_GET["type"]) && $_GET["type"] == "header") {
					$postId = $_REQUEST["postId"];
					$content .= "<input type=\"hidden\" name=\"postId\" value=\"$postId\" />
					<input type=\"hidden\" name=\"type\" value=\"header\" />";
				}
			}
			if (isset($_GET["type"]) && $_GET["type"] == "group-banner") {
				$groupId = $_REQUEST["groupId"];
				$content .= "<input type=\"hidden\" name=\"groupId\" value=\"$groupId\" />
				<input type=\"hidden\" name=\"type\" value=\"group-banner\" />";
				
			} elseif (isset($_GET["type"]) && $_GET["type"] == "site-banner") {
				$siteId = $_REQUEST["siteId"];
				$content .= "<input type=\"hidden\" name=\"siteId\" value=\"$siteId\" />
				<input type=\"hidden\" name=\"type\" value=\"site-banner\" />";
				
			} elseif (isset($_GET["type"]) && $_GET["type"] == "user-banner") {
				$userId = $_REQUEST["userId"];
				$content .= "<input type=\"hidden\" name=\"userId\" value=\"$userId\" />
				<input type=\"hidden\" name=\"type\" value=\"user-banner\" />";
				
			}
			$content .= "<p><input id=\"crop-button\" class=\"ss-button\" disabled=\"disabled\" type=\"submit\" value=\"Crop Image\" /></p>
			<div class=\"center-text\"><img id=\"jcrop-$target\" src=\"$tmpLocation\" title=\"Preview\" /></div>
			</form>
			<div class=\"center-text\"><span class=\"subtle-text\">Tool powered by <a href=\"http://deepliquid.com/content/Jcrop.html\" title=\"Go to Jcrop home page\">Jcrop</a></div>";
		}
		else {
			$content .= "<p class=\"ss-error\">Uploaded image must be .jpg or .png, and under 8MB.</p>";
		}
	} else { // not logged in
		$content .= "<p class=\"ss-error\">You must be logged in to use this feature.</p>";
	}
	
	return $content;
}
?>