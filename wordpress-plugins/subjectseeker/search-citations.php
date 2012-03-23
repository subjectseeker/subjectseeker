<?php
/*
Plugin Name: SubjectSeeker Generate Citations
Plugin URI: http://scienceseeker.org/
Description: Generate citations for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://scienceseeker.org/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssGenerateCitations')) {
  class ssGenerateCitations {
    function ssGenerateCitations() {
      $this->version = "0.1";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssGenerateCitations($args) {
        extract($args);
        $options = get_option('widget_ssGenerateCitations');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssGenerateCitations();
        echo $after_widget;
      }
      function widget_ssGenerateCitations_control() {
        $options = get_option('widget_ssGenerateCitations');
        if ( $_POST['ssGenerateCitations-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssGenerateCitations-title']));
          update_option('widget_ssGenerateCitations', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssGenerateCitations-title">Title:<input class="widefat" name="ssGenerateCitations-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssGenerateCitations-submit" name="ssGenerateCitations-submit" value="1" />';
      }
      register_sidebar_widget('ssGenerateCitations', 'widget_ssGenerateCitations');
      register_widget_control('ssGenerateCitations', 'widget_ssGenerateCitations_control');
    }
  }
}

$ssGenerateCitations = new ssGenerateCitations();
add_action( 'plugins_loaded', array(&$ssGenerateCitations, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssGenerateCitations, 'setupActivation' ));

function get_ssGenerateCitations($settings = array()) {
	global $ssGenerateCitations;
  searchCitations();
}

// Non-widget functions

function searchCitations() {
	global $metadata2coins;
	$db  = ssDbConnect();
	$step = $_REQUEST["step"];

	if ($step == NULL) {
		print "<h3>Search Citation</h3>
		<div class=\"subtle-text\">
		<p>This tool will allow you to generate a citation that you can add to your posts for reference and aggregation here and with other services that use the industry-standard COinS system for citation of peer-reviewed research.</p>
		<p>Please enter words from the title of the article you'd like to cite. The first 7 or 8 words work best. You can also use the DOI, author name, or other keywords. Our system will search the CrossRef database for the article.</p>
		</div>
		<form id=\"center-text\" method=\"GET\">
		<input type=\"hidden\" name=\"step\" value=\"results\" />
		<input class=\"big-input\" type=\"text\" name=\"title\" />
		<div class=\"ss-div\"><input class=\"big-button\" type=\"submit\" value=\"Search\"></div>
		</form>";
	}
	if ($step == "results") {
		$title = $_REQUEST["title"];
		print "<input class=\"ss-button\" type=\"button\" value=\"Go Back\" onClick=\"history.go(-1);return true;\"><br />
		<h3>Select citation.</h3>
		<p>Please select a citation from the results below or modify your search to refine the results.</p>
		<form id=\"center-text\" method=\"GET\">
		<input type=\"hidden\" name=\"step\" value=\"results\" />
		<input class=\"big-input\" type=\"text\" name=\"title\" value=\"$title\" />
		<div class=\"ss-div\"><input class=\"big-button\" type=\"submit\" value=\"Modify Search\"></div>
		<div id=\"loading-message\" class=\"ss-div-2\">Please wait while we search the Crossref database for your citation.<br />
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
			foreach ($results as $result) {
				print "<div class=\"ss-entry-wrapper\">
				<form style=\"display: inline;\" method=\"POST\">
				<input type=\"hidden\" name=\"step\" value=\"edit\" />
				<textarea readonly=\"readonly\" style=\"display: none;\" name=\"selected\">$result</textarea>
				<div class=\"ss-div\" style=\"display: inline-block; width: 85%;\">$result</div>
				<div style=\"display: inline-block;\" class=\"alignright\">
				<input class=\"ss-button\" type=\"submit\" value=\"Select\" />
				</div>
				</form>
				</div>";
			}
		}
	}
	if ($step == "edit") {
		print "<input class=\"ss-button\" type=\"button\" value=\"Go Back\" onClick=\"history.go(-1);return true;\"><br />
		<h3>Edit Citation</h3>
		<form method=\"POST\">
		<input type=\"hidden\" name=\"step\" value=\"end\" />";
		$citation = stripslashes($_REQUEST["selected"]);
		$data = parseCitation($citation, $db);
		$values = array_map('utf8_decode', $data);
		print "<input type=\"hidden\" name=\"rtfId\" value=\"".$values["rft_id"]."\" />
		<input type=\"hidden\" name=\"rfrId\" value=\"".$values["rfr_id"]."\" />
		<p>Please confirm the data is correct before generating the citation.</p>
		<p id=\"padding-content\">$citation</p>
		<input type=\"button\" id=\"add-author\" class=\"alignright\" value=\"+ Add Author\" />
		<br />
		<div class=\"ss-div-2\">
		<h4>Title</h4>
		<textarea name=\"title\" rows=\"2\" cols=\"65\">".$values["rft.atitle"]."</textarea>
		</div>";
		foreach ($data["authors"] as $author) {
			print "<div class=\"removable-parent\">
			<div class=\"ss-div-2\">
			<h4>Author <span id=\"remove-parent\" class=\"alignright\">X</span></h4>
			<span class=\"subtle-text\">First Name:</span> <textarea name=\"firstName[]\" rows=\"1\" cols=\"56\">".utf8_decode($author["rft.aufirst"])."</textarea><br />
			<br />
			<span class=\"subtle-text\">Last Name:</span> <textarea name=\"lastName[]\" rows=\"1\" cols=\"56\">".utf8_decode($author["rft.aulast"])."</textarea>
			</div>
			</div>";
		}
		print "<div id=\"journal\" class=\"ss-div-2\">
		<h4>Journal</h4>
		<textarea name=\"journal\" rows=\"2\" cols=\"65\">".$values["rft.jtitle"]."</textarea>
		</div>
		<div class=\"ss-div-2\">
		<h4>Article Url</h4>
		<textarea name=\"url\" rows=\"2\" cols=\"65\">".$values["rft.artnum"]."</textarea>
		</div>
		<div class=\"ss-div-2\">
		<h4>Volume</h4>
		<textarea name=\"volume\" rows=\"1\" cols=\"65\">".$values["rft.volume"]."</textarea>
		</div>
		<div class=\"ss-div-2\">
		<h4>Issue</h4>
		<textarea name=\"issue\" rows=\"1\" cols=\"65\">".$values["rft.issue"]."</textarea>
		</div>
		<div class=\"ss-div-2\">
		<h4>ISSN</h4>
		<textarea name=\"issn\" rows=\"1\" cols=\"65\">".$values["rft.issn"]."</textarea>
		</div>
		<div class=\"ss-div-2\">
		<h4>First Page</h4>
		<textarea name=\"spage\" rows=\"1\" cols=\"65\">".$values["rft.spage"]."</textarea>
		</div>
		<div class=\"ss-div-2\">
		<h4>Year of Publication</h4>
		<textarea name=\"date\" rows=\"1\" cols=\"65\">".$values["rft.date"]."</textarea>
		</div>
		<div class=\"ss-div-2\">
		<h4>ID</h4>
		<textarea name=\"id\" rows=\"1\" cols=\"65\">".$values["id"]."</textarea>
		</div>
		<div class=\"ss-div-2\">
		<h4>Allow the citation to be aggregated by</h4>
		<div class=\"alignleft\"><div class=\"ss-div\"><input type=\"checkbox\" checked=\"checked\" name=\"ssInclude\" value=\"1\" /> ScienceSeeker</div></div> <div class=\"alignleft\"><div class=\"ss-div\"><input class=\"toggleHidenOption\" type=\"checkbox\" checked=\"checked\" name=\"rbInclude\" value=\"1\" /> Research Blogging</div></div>
		</div>
		<br />
		<div style=\"width: 100%;\" class=\"ss-hidden-option\">
		<div style=\"height: 100px;\" class=\"ss-div-2\">
		<h4>Research Blogging Tags</h4>";
		$rbTags = array ("Anthropology", "Astronomy", "Biology", "Chemistry", "Computer Science / Engineering", "Ecology / Conservation", "Geosciences", "Health", "Mathematics", "Medicine", "Neuroscience", "Philosophy", "Physics", "Psychology", "Social Science", "Research / Scholarship", "Other");
		foreach ($rbTags as $tag) {
			print "<div class=\"alignleft\"><div class=\"ss-div\"><input type=\"checkbox\" name=\"rbTags[]\" value=\"$tag\" /> $tag</div></div>";
		}
		print "</div>
		</div>
		<br />
		<div class=\"ss-div-2\">
		<input class=\"ss-button\" type=\"submit\" value=\"Submit\" />
		</div>
		</form>";
	}
	
	if ($step == "end") {
		global $homeUrl;
		print "<input class=\"ss-button\" type=\"button\" value=\"Go Back\" onClick=\"history.go(-1);return true;\"> <a class=\"ss-button\" href=\"$homeUrl\">Homepage</a><br />
		<h3>Result</h3>";
		foreach ($_REQUEST as $attribute => $value) {
			if (is_array($value) == TRUE) {
				$value = array_map('stripslashes', $value);
			}
			else {
				$value = stripslashes($value);
			}
			$$attribute = $value;
		}
		$citation = "<span class=\"Z3988\" title=\"ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Ajournal&amp;rft.jtitle=".rawurlencode($journal)."&amp;rft_id=".rawurlencode($rtfId)."&amp;rfr_id=".rawurlencode($rfrId)."&amp;rft.atitle=".rawurlencode($title)."&amp;rft.issn=$issn&amp;rft.date=$date&amp;rft.volume=$volume&amp;rft.issue=$issue&amp;rft.spage=$spage&amp;rft.artnum=".rawurlencode($url);
		
		if ($firstName || $lastName) {
			$i = count($firstName);
			foreach ($firstName as $key => $first) {
				$buildFName = "";
				$last = $lastName[$key];
				$citation .= "&amp;rft.au=".urlencode("$last $first")."&amp;rft.aulast=".urlencode($last)."&amp;rft.aufirst=".urlencode($first);
				
				preg_match_all("/\w+/", $first, $matchResult);
				foreach ($matchResult[0] as $fName) {
				 $buildFName .= ucfirst($fName[0]).".";
				}
				$authors .= "$last, $buildFName";
				if ($key == ($i-2)) {
					$authors .= " & ";
				}
				elseif ($key != $i-1) {
					$authors .= ", ";
				}
			}
		}
		if ($date) {
			$date = "($date).";
		}
		if ($title) {
			$title = "$title,";
		}
		if ($journal) {
			$journal = "$journal,";
		}
		if ($issue) {
			$issue = "($issue),";
		}
		if ($spage) {
			$spage = "$spage.";
		}
		if ($ssInclude) {
			$citation .= "&rfs_dat=ss.included=1";
		}
		else {
			$citation .= "&rfs_dat=ss.included=0";
		}
		$citation .= "&rfe_dat=";
		if ($rbInclude) {
			$citation .= "bpr3.included=1";
		}
		else {
			$citation .= "bpr3.included=0";
		}
		if ($rbTags && $rbInclude) {
			$citation .= ";bpr3.tags=".urlencode(implode(",",$rbTags));
		}
		$citation .= "\">";
		$citation .= "$authors $date $title <span style=\"font-style:italic;\">$journal $volume</span> $issue $spage DOI: <a rev=\"review\" href=\"http://dx.doi.org/$id\">$id</a></span>";
		
		print "<p>This is how the citation will look after you copy the HTML code to your article.</p>
		<p id=\"padding-content\">$citation</p>";
		print "<h4>HTML Code:</h4>
		<p>Please copy this code to your article to add the citation.</p>
		<textarea onClick=\"this.focus();this.select()\" rows=\"10\" cols=\"60\" readonly=\"readonly\">$citation</textarea>";
	}
}

?>
