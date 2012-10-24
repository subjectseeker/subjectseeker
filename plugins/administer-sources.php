<?php

function adminSources() {
  if (isLoggedIn()){
		$db = ssDbConnect();
		
		// User Information
                $authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
                $userPriv = getUserPrivilegeStatus($authUserId, $db);
		
		if ($userPriv > 0) { // moderator or admin
			$step = NULL;
			if (!empty($_REQUEST["step"])) {
				$step = $_REQUEST["step"];
			}
			
			$sort = "alphabetical";
			$order = "asc";
			$pagesize = "30";
			$offset = "0";
			if (!empty($_REQUEST["sort"])) {
				$sort = $_REQUEST["sort"];
			}
			if (!empty($_REQUEST["order"])) {
				$order = $_REQUEST["order"];
			}
			if (!empty($_REQUEST["n"])) {
				$pagesize = $_REQUEST["n"];
			}
			if (!empty($_REQUEST["offset"])) {
				$offset = $_REQUEST["offset"];
			}
				
			print "<div class=\"toggle-button\">Display Options</div>
			<div class=\"ss-slide-wrapper\">
			<div class=\"ss-div-2\" id=\"filter-panel\">
			<form method=\"get\">
			Sort by: <select name='sort'>\n
			<option value='id'";
			if ($sort == "id") {
				print " selected";
			}
			print ">ID</option>\n
			<option value='alphabetical'";
			if ($sort == "alphabetical") {
				print " selected";
			}
			print ">Name</option>\n
			<option value='added-date'";
			if ($sort == "added-date") {
				print " selected";
			}
			print ">Added Date</option>\n
			<option value='crawled-date'";
			if ($sort == "crawled-date") {
				print " selected";
			}
			print ">Crawled Date</option>\n</select> | <select name='order'>\n
			<option value='asc'";
			if ($order == "asc") {
				print " selected";
			}
			print ">Ascending</option>\n
			<option value='desc'";
			if ($order == "desc") {
				print " selected";
			}
			print ">Descending</option>\n
			</select><br />\n
			Entries per page: <input class=\"small-input\" type=\"text\" name=\"n\" size=\"2\" value=\"$pagesize\"/> | Start at: <input class=\"small-input\" type=\"text\" name=\"offset\" size=\"2\" value=\"$offset\"/><br />
			<input class=\"ss-button\" type=\"submit\" value=\"Go\" />
			</form>
			</div>
			</div>
			<br />";
				
			if (!empty($step)) {
				confirmEditBlog ($step, $db);
			}
			$queryList = httpParamsToSearchQuery();
			$settings = httpParamsToExtraQuery();
			$settings["show-all"] = "true";
			$settings["type"] = "blog";
			$blogData = generateSearchQuery ($queryList, $settings, 1, $db);
			
			if (empty($blogData["result"])) {
				print "<p>There are no more blogs in the system.</p>";
			}
			else {
				print "<div class=\"entries\">";
				while ($row = mysql_fetch_array($blogData["result"])) {
					editBlogForm ($row, $userPriv, FALSE, $db);
				}
				print "</div>";
			}
			global $pages;
			pageButtons ($pages["administer-sources"]->getAddress(), $pagesize, $blogData["total"]);
		} else { // not moderator or admin
			print "<p>You are not authorized to administrate blogs.</p>";
		}
  } else { // not logged in
    print "<p>Please log in.</p>";
  }
}

?>