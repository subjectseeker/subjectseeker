<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

include_once (dirname(__FILE__)."/../initialize.php");

$db = ssDbConnect();
$postId = $_REQUEST["id"];

$categories = array(
"biology"=>"Best Biology Post",
"physics/astronomy"=>"Best Physics or Astronomy Post",
"psychology/neuroscience"=>"Best Psychology or Neuroscience Post",
"medicine"=>"Best Medicine Post",
"chemistry"=>"Best Chemistry Post",
"podcast/video"=>"Best Podcast or Video",
"peer-review"=>"Best Post About Peer-Reviewed Research",
"young-blogger"=>"Best Post by a High School or Undergraduate Blogger",
"art"=>"Best Science Art Post",
"science-life"=>"Best Life-in-Science Post",
);

print "<h2>ScienceSeeker Awards</h2><p>To nominate this post for an award, click on the award category below.</p><ul data-id=\"$id\">";
foreach ($categories as $key => $category) {
	$sql = "SELECT topic.TOPIC_NAME FROM TAG tag INNER JOIN TOPIC topic ON tag.TOPIC_ID = topic.TOPIC_ID WHERE OBJECT_ID = '$postId' AND OBJECT_TYPE_ID = '1' AND TOPIC_NAME = 'ssawards-$key'";
	$result = mysql_query($sql, $db);
	$row = mysql_fetch_array($result);
	
	print "<li class=\"contest-category\" data-category=\"$key\">$category";
	
	if ($row) {
		print "<span class=\"contest-nominated\">Nominated</span>";
	}
	
	print "</li>";
}
print '</ul>';

?>