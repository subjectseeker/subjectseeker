<?php

/*

Copyright © 2010–2013 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function displayAdminPanel() {
	$db	= ssDbConnect();

	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$authUserPriv = getUserPrivilegeStatus($authUserId, $db);
	} else {
		print "<p class=\"ss-warning\">Please log in.</p>";
		
		return NULL;
	}
	
	if ($authUserPriv < 1) {
		print "<p class=\"ss-warning\">You don't have sufficient privileges to use this tool.</p>";
		
		return NULL;
	}

	if (isset($_POST["step"])) {
		$settings["Topic Title"] = $_POST["topic-title"];
		$settings["Home Topic"] = $_POST["topic"];
		editSetttings($settings, $db);
	}

	$settings = loadSettings($db);
	print "<h3>Homepage</h3>
	<form class=\"block\" method=\"post\">
	<input name=\"step\" type=\"hidden\" value=\"edit\" />
	<p>Topic Title<br />
	<input name=\"topic-title\" type=\"text\" value=\"".htmlspecialchars($settings["Topic Title"], ENT_QUOTES)."\" /></p>
	<p>Homepage Topic<br />
	<input name=\"topic\" type=\"text\" value=\"".htmlspecialchars($settings["Home Topic"], ENT_QUOTES)."\" /></p>
	<p><input class=\"ss-button\" type=\"submit\" value=\"Change Settings\" /></p>
	</form>";


}

?>