<?php

function generateCitations() {
	global $pages;
	global $metadata2coins;
	$db  = ssDbConnect();
	$step = NULL;
	if (!empty($_REQUEST["step"])) {
		$step = $_REQUEST["step"];
	}

	if ($step == NULL) {
		if (! isLoggedIn()){
			print "<p class=\"ss-warning\">You should <a href=\"".$pages["login"]->getAddress()."\" title=\"Log In Page\">log in</a> for your blogs to be automatically scanned for citations.</p>";
		}
		
		print "<h3>Search Citation</h3>
		<div class=\"subtle-text\">
		<p>This tool will allow you to generate a citation that you can add to your posts for reference and aggregation here and with other services that use the industry-standard COinS system for citation of peer-reviewed research.</p>
		<p>Please enter words from the title of the article you'd like to cite. The first 7 or 8 words work best. You can also use the DOI, author name, or other keywords. Our system will search the CrossRef database for the article.</p>
		</div>
		<div class=\"center-text\">
		<form method=\"get\">
		<input type=\"hidden\" name=\"step\" value=\"results\" />
		<p class=\"margin-bottom-small\"><input class=\"big-input\" type=\"text\" name=\"title\" /></p>
		<p><input class=\"big-button\" type=\"submit\" value=\"Search\"></p>
		</form>
		<p>Or</p>
		<p><a class=\"big-button\" href=\"".$pages["generate-citations"]->getAddress()."/?step=create\">Create Citation</a></p>
		</div>";
	}
	elseif ($step == "create") {
		print "<h3>Create Citation</h3>
		<form method=\"post\">
		<input type=\"hidden\" name=\"step\" value=\"end\" />
		<p><input type=\"button\" id=\"add-author\" value=\"+ Add Author\" /></p>
		<h4>Title</h4>
		<p><textarea class=\"small-text-area\" name=\"title\" style=\"height: 48px;\"></textarea></p>
		<div class=\"removable-parent\">
		<h4>Author <span class=\"remove-parent\">X</span></h4>
		<p><span class=\"subtle-text\">First Name</span><br />
		<textarea class=\"small-text-area\" name=\"fName[]\"></textarea></p>
		<p><span class=\"subtle-text\">Last Name</span><br />
		<textarea class=\"small-text-area\" name=\"lName[]\"></textarea></p>
		</div>
		<div id=\"journal\">
		<h4>Journal</h4>
		<p><textarea class=\"small-text-area\" name=\"journal\"></textarea></p>
		</div>
		<h4>Article Url</h4>
		<p><textarea class=\"small-text-area\" name=\"article\"></textarea></p>
		<h4>Volume</h4>
		<p><textarea class=\"small-text-area\" name=\"volume\" ></textarea></p>
		<h4>Issue</h4>
		<p><textarea class=\"small-text-area\" name=\"issue\" ></textarea></p>
		<h4>ISSN</h4>
		<p><textarea class=\"small-text-area\" name=\"issn\" ></textarea></p>
		<h4>First Page</h4>
		<p><textarea class=\"small-text-area\" name=\"spage\" ></textarea></p>
		<h4>Last Page</h4>
		<p><textarea class=\"small-text-area\" name=\"spage\" ></textarea></p>
		<h4>Year of Publication</h4>
		<p><textarea class=\"small-text-area\" name=\"date\" ></textarea></p>
		<h4>ID Type</h4>
		<p><select name=\"idType\">
		<option value=\"doi\">DOI</option>
		<option value=\"pmid\">PMID</option>
		<option value=\"arxiv\">arXiv</option>
		<option value=\"other\">Other</option>
		</select></p>
		<h4>ID</h4>
		<p><textarea class=\"small-text-area\" name=\"id\" ></textarea></p>
		<h4>Allow the citation to be aggregated by</h4>
		<p><span class=\"inline-options\"><input type=\"checkbox\" checked=\"checked\" name=\"ssInclude\" value=\"1\" /> ScienceSeeker</span> <span class=\"inline-options\"><input class=\"toggleHidenOption\" type=\"checkbox\" checked=\"checked\" name=\"rbInclude\" value=\"1\" /> Research Blogging</span></p>
		<div class=\"ss-hidden-option\">
		<h4>Research Blogging Tags</h4>
		<p>";
		$rbTags = array ("Anthropology", "Astronomy", "Biology", "Chemistry", "Computer Science / Engineering", "Ecology / Conservation", "Geosciences", "Health", "Mathematics", "Medicine", "Neuroscience", "Philosophy", "Physics", "Psychology", "Social Science", "Research / Scholarship", "Other");
		foreach ($rbTags as $tag) {
			print "<span class=\"inline-options\"><input type=\"checkbox\" name=\"rbTags[]\" value=\"$tag\" /> $tag</span>";
		}
		print "</p>
		</div>
		<input class=\"ss-button\" type=\"submit\" value=\"Create Citation\" />
		</form>";
	}
	elseif ($step == "results") {
		$title = NULL;
		if (!empty($_REQUEST["title"])) {
			$title = $_REQUEST["title"];
		}
		print "<p><input class=\"ss-button\" type=\"button\" value=\"Go Back\" onClick=\"history.go(-1);return true;\"></p>
		<h3>Select citation.</h3>
		<p>Please select a citation from the results below or modify your search to refine the results.</p>
		<form class=\"center-text\" method=\"get\">
		<input type=\"hidden\" name=\"step\" value=\"results\" />
		<p class=\"margin-bottom-small\"><input class=\"big-input\" type=\"text\" name=\"title\" value=\"$title\" /></p>
		<p><input class=\"big-button\" type=\"submit\" value=\"Modify Search\"></p>
		<div id=\"loading-message\" class=\"margin-bottom\">Please wait while we search the Crossref database for your citation.<br />
		<img src=\"/images/icons/loading.gif\" alt=\"Loading\" title=\"Loading\" /></div>
		</form>";
		$results = titleToCitations($title, $metadata2coins);
		if ($results == NULL) {
			print "<p>No results found for your search.</p>";
		}
		elseif (is_array($results) == FALSE) {
		 print "<p class=\"ss-error\">$results <input class=\"ss-button\" type=\"button\" value=\"Retry\" onClick=\"window.location.reload()\"></p>";
		}
		else {
			print "<div class=\"entries\">";
			foreach ($results as $result) {
				print "<div class=\"ss-entry-wrapper\">
				<form style=\"display: inline;\" method=\"post\">
				<input type=\"hidden\" name=\"step\" value=\"edit\" />
				<textarea class=\"small-text-area\" readonly=\"readonly\" style=\"display: none;\" name=\"selected\">".htmlspecialchars($result)."</textarea>
				<div class=\"ss-div\" style=\"display: inline-block; width: 85%;\">$result</div>
				<div style=\"display: inline-block;\" class=\"alignright\">
				<input class=\"ss-button\" type=\"submit\" value=\"Select\" />
				</div>
				</form>
				</div>";
			}
			print "</div>";
		}
	}
	if ($step == "edit") {
		print "<p><input class=\"ss-button\" type=\"button\" value=\"Go Back\" onClick=\"history.go(-1);return true;\"></p>
		<h3>Edit Citation</h3>
		<form method=\"post\">
		<input type=\"hidden\" name=\"step\" value=\"end\" />";
		if (!empty($_REQUEST["selected"])) {
			$citation = $_REQUEST["selected"];
		}
		$articleData = parseCitation($citation, $db);
		storeArticle ($articleData, 1, $db);
		
		$atitle = NULL;
		$jtitle = NULL;
		$artnum = NULL;
		$volume = NULL;
		$issue = NULL;
		$issn = NULL;
		$spage = NULL;
		$epage = NULL;
		$date = NULL;
		$id = NULL;
		foreach ($articleData as $name => $parameter) {
			if (is_string($parameter)) {
				$name = str_replace(array("rft.", "_"), "", $name);
				$$name = htmlspecialchars($parameter);
			}
		}
		print "<p>Please confirm the data is correct before generating the citation.</p>
		<p class=\"padding-content\">$citation</p>
		<p><input type=\"button\" id=\"add-author\" value=\"+ Add Author\" /></p>
		<h4>Title</h4>
		<p><textarea class=\"small-text-area\" name=\"title\" style=\"height: 48px;\">".$atitle."</textarea></p>";
		if (!empty($articleData["authors"])) {
			foreach ($articleData["authors"] as $author) {
				$firstName = NULL;
				$lastName = NULL;
				$fullName = NULL;
				if (!empty($author["rft.aufirst"])) {
					$firstName = $author["rft.aufirst"];
				}
				if (!empty($author["rft.aulast"])) {
					$lastName = $author["rft.aulast"];
				}
				if (!empty($author["rft.au"])) {
					$fullName = $author["rft.au"];
				}
				print "<div class=\"removable-parent\">
				<h4>Author <span class=\"remove-parent\">X</span></h4>";
				if (!empty($fullName) && (empty($firstName) && empty($lastName))) {
					print "<span class=\"subtle-text\">Full Name</span><br />
					<p><textarea class=\"small-text-area\" name=\"fullName[]\">$fullName</textarea></p>";
				}
				print "<span class=\"subtle-text\">First Name</span><br />
				<p><textarea class=\"small-text-area\" name=\"fName[]\">$firstName</textarea></p>
				<span class=\"subtle-text\">Last Name</span><br />
				<p><textarea class=\"small-text-area\" name=\"lName[]\">$lastName</textarea></p>
				</div>";
			}
		}
		print "<div id=\"journal\">
		<h4>Journal</h4>
		<p><textarea class=\"small-text-area\" name=\"journal\">".$jtitle."</textarea></p>
		</div>
		<h4>Article Url</h4>
		<p><textarea class=\"small-text-area\" name=\"article\">".$artnum."</textarea></p>
		<h4>Volume</h4>
		<p><textarea class=\"small-text-area\" name=\"volume\" >".$volume."</textarea></p>
		<h4>Issue</h4>
		<p><textarea class=\"small-text-area\" name=\"issue\" >".$issue."</textarea></p>
		<h4>ISSN</h4>
		<p><textarea class=\"small-text-area\" name=\"issn\" >".$issn."</textarea></p>
		<h4>First Page</h4>
		<p><textarea class=\"small-text-area\" name=\"spage\" >".$spage."</textarea></p>
		<h4>Last Page</h4>
		<p><textarea class=\"small-text-area\" name=\"spage\" >".$epage."</textarea></p>
		<h4>Year of Publication</h4>
		<p><textarea class=\"small-text-area\" name=\"date\" >".$date."</textarea></p>
		<h4>ID Type</h4>
		<p><select name=\"idType\">
		<option value=\"doi\"";
		if ($idtype == "doi") {
			print " selected=\"selected\"";
		}
		print " >DOI</option>
		<option value=\"pmid\"";
		if ($idtype == "pmid") {
			print " selected=\"selected\"";
		}
		print " >PMID</option>
		<option value=\"arxiv\"";
		if ($idtype == "arxiv") {
			print " selected=\"selected\"";
		}
		print " >arXiv</option>
		<option value=\"other\"";
		if ($idtype == "other") {
			print " selected=\"selected\"";
		}
		print " >Other</option>
		</select>
		</p>
		<h4>ID</h4>
		<p><textarea class=\"small-text-area\" name=\"id\" >".$id."</textarea></p>
		<h4>Allow the citation to be aggregated by</h4>
		<p><span class=\"inline-options\"><input type=\"checkbox\" checked=\"checked\" name=\"ssInclude\" value=\"1\" /> ScienceSeeker</span> <span class=\"inline-options\"><input class=\"toggleHidenOption\" type=\"checkbox\" checked=\"checked\" name=\"rbInclude\" value=\"1\" /> Research Blogging</span></p>
		<div class=\"ss-hidden-option\">
		<h4>Research Blogging Tags</h4>
		<p>";
		$rbTags = array ("Anthropology", "Astronomy", "Biology", "Chemistry", "Computer Science / Engineering", "Ecology / Conservation", "Geosciences", "Health", "Mathematics", "Medicine", "Neuroscience", "Philosophy", "Physics", "Psychology", "Social Science", "Research / Scholarship", "Other");
		foreach ($rbTags as $tag) {
			print "<span class=\"inline-options\"><input type=\"checkbox\" name=\"rbTags[]\" value=\"$tag\" /> $tag</span>";
		}
		print "</p>
		</div>
		<input class=\"ss-button\" type=\"submit\" value=\"Submit\" />
		</form>";
	}
	
	if ($step == "end") {
		if (isLoggedIn()){
			$authUser = new auth();
			$authUserId = $authUser->userId;
			$authUserName = $authUser->userName;
			
			$blogIds = getBlogIdsByUserId($authUserId, $db);
			
			foreach ($blogIds as $blogId) {
				insertCitationMarker ($blogId, $db);
			}
		}
		
		print "<div class=\"margin-bottom\"><input class=\"ss-button\" type=\"button\" value=\"Go Back\" onClick=\"history.go(-1);return true;\"> <a class=\"ss-button\" href=\"".$pages["home"]->getAddress()."\">Homepage</a></div>
		<h3>Result</h3>";
		
		if (!empty($_REQUEST["idType"])) $articleData["id_type"] = $_REQUEST["idType"];
		if (!empty($_REQUEST["id"])) $articleData["id"] = $_REQUEST["id"];
		if (!empty($_REQUEST["id"])) $articleData["rft_id"] = $_REQUEST["id"];
		if (!empty($_REQUEST["title"])) $articleData["rft.atitle"] = $_REQUEST["title"];
		if (!empty($_REQUEST["journal"])) $articleData["rft.jtitle"] = $_REQUEST["journal"];
		if (!empty($_REQUEST["article"])) $articleData["rft.artnum"] = $_REQUEST["article"];
		if (!empty($_REQUEST["volume"])) $articleData["rft.volume"] = $_REQUEST["volume"];
		if (!empty($_REQUEST["issue"])) $articleData["rft.issue"] = $_REQUEST["issue"];
		if (!empty($_REQUEST["issn"])) $articleData["rft.issn"] = $_REQUEST["issn"];
		if (!empty($_REQUEST["spage"])) $articleData["rft.spage"] = $_REQUEST["spage"];
		if (!empty($_REQUEST["date"])) $articleData["rft.date"] = $_REQUEST["date"];
		if (!empty($_REQUEST["ssInclude"])) $articleData["ssInclude"] = $_REQUEST["ssInclude"];
		if (!empty($_REQUEST["rbInclude"])) $articleData["rbInclude"] = $_REQUEST["rbInclude"];
		if (empty($articleData["ssInclude"])) $articleData["ssInclude"] = 0;
		if (empty($articleData["rbInclude"])) $articleData["rbInclude"] = 0;
		if (!empty($_REQUEST["rbTags"])) $articleData["rbTags"] = $_REQUEST["rbTags"];
		if (!empty($_REQUEST["fName"])) {
			foreach ($_REQUEST["fName"] as $key => $value) {
				if (!empty($value) || !empty($_REQUEST["lName"][$key])) {
					$articleData["authors"][] = array("rft.aufirst"=>$value, "rft.aulast"=>$_REQUEST["lName"][$key]);
				}
				elseif (!empty($_REQUEST["fullName"][$key])) {
					$articleData["authors"][] = array("rft.au"=>$_REQUEST["fullName"][$key]);
				}
			}
		}
		
		$generatedCitation = generateCitation($articleData);

		global $sitename;
		print "<p>This is how the citation will look after you copy the HTML code to your article.</p>
		<p class=\"padding-content\">$generatedCitation</p>";
		print "<h4>HTML Code:</h4>";
		if (!empty($userId)) {
			print "<p>Please insert this HTML code into your post to add the citation. Our site will find the post within a few hours after you publish it. If you want to have our site find your post sooner, you can <a href=\"".$pages["my-posts"]->getAddress()."/?step=scan&amp;scanNow=1&amp;addPosts=1&amp;n=10\" title=\"Scan 10 most recent posts.\">scan your recent posts for citations</a>.</p>";
		}
		else {
			print "<p>Please insert this HTML code into your post to add the citation.</p>";
		}
		print "<p><textarea onClick=\"this.focus();this.select()\" style=\"height: 125px;\" readonly=\"readonly\">$generatedCitation</textarea>";
	}
}

?>
