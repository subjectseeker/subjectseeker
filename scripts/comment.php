<?php
include_once "ss-util.php";

// Connect to database
$db = ssDbConnect();

$personaId = $_REQUEST["persona"];
// Separate post ID from URL
preg_match('/\d+$/', $_REQUEST["id"], $matchResult);
$postId = implode($matchResult);
$step = $_REQUEST["step"];

// Display all comments
print "<h3>Notes</h3>";

// Check if a comment must be stored
if ($step == "store" || $step == "confirm") {
	if ($step != "confirm") {
		foreach (getComments($postId, $db) as $item) {
			$commentPersonaId = $item["personaId"];
			if ($commentPersonaId == $personaId) {
				$overwriteStatus = TRUE;
			}
		}
	}
	if ($overwriteStatus == TRUE) {
		print "<form method=\"POST\">
		<div id=\"padding-content\"><p>You have already commented on this post, are you sure you want to overwrite your comment?</p>
		<input id=\"submit-comment\" class=\"submit-comment ss-button\" type=\"button\" data-step=\"confirm\" value=\"Yes\" /> <input id=\"submit-comment\" class=\"submit-comment ss-button\" type=\"button\" data-step=\"dont-update\" value=\"No\" />
		</div>
		</form>";
		return;
	}
	else {
		$comment = mysql_real_escape_string($_REQUEST["comment"]);
		$sql = "UPDATE RECOMMENDATION SET REC_COMMENT = '$comment' WHERE BLOG_POST_ID = $postId AND PERSONA_ID = $personaId";
		mysql_query($sql, $db);
	}
}

// Get comments list
$commentList = getComments($postId, $db);
$commentCount = count($commentList);

print "<div id=\"comment-list\" data-count=\"$commentCount\">";

if ($commentList == NULL) {
	print "<div id=\"padding-content\">There are no notes for this post. Recommend this post to leave a note.</div>";
}
else {
	// Display comments
	foreach ($commentList as $data) {
		$commentPersonaId = $data["personaId"];
		$postDate = $data["date"];
		$comment = $data["comment"];
		$personaName = getPersonaName($commentPersonaId, $db);
		print "<div class=\"comment-wrapper\" data-commenterPersonaId=\"$commentPersonaId\">
		<div class=\"comment-header\">$personaName<span class=\"alignright\">$postDate</span></div>
		<br />
		<div id=\"padding-content\">$comment</div>
		</div>";
	}
print "</div>";
}

?>