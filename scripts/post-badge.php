<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

include_once (dirname(__FILE__)."/../globals.php");
include_once (dirname(__FILE__)."/util.php");

global $imagesUrl;
$db = ssDbConnect();

preg_match('/(?<=\/post\/)\d+/', $_SERVER["REQUEST_URI"], $matchResult);
$postId = $matchResult[0];

// Use Search API to find Blog ID and Post URL
$errormsgs = array();
$postData = getPost($postId, $db);
$postHasCitation = $postData["BLOG_POST_HAS_CITATION"];
$editorsPicksStatus = getRecommendationsCount($postId, NULL, NULL, 1, $db);

global $postProfile;
global $imagesUrl;
if ($postHasCitation == TRUE && $editorsPicksStatus == TRUE) {
	header("Location: $imagesUrl/icons/badge-3.gif");
}
elseif ($postHasCitation == TRUE) {
	header("Location: $imagesUrl/icons/badge-2.gif");
}
elseif ($editorsPicksStatus == TRUE) {
	header("Location: $imagesUrl/icons/badge-1.gif");
}
else {
	header("Location: $imagesUrl/icons/badge.gif");
}

?>