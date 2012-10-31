 <?php
/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function userActivity ($limit = 8) {
	$cache = new cache();
	if ($cache->caching) {
		global $twitterListId;
		global $homeUrl;
		$originalUrl = $homeUrl;
		if (isset($_REQUEST["url"])) {
			$originalUrl = $_REQUEST["url"];
		}
		
		$tweets = getTwitterList($twitterListId);
		
		print "<div id=\"user-activity\">";
		$i = 0;
		if (is_array($tweets)) {
			foreach ($tweets as $tweet) {
				$tweetAuthor = $tweet->user->screen_name;
				$tweetContent = $tweet->text;
				$tweetAvatar = $tweet->user->profile_image_url;
				
				$tweetContent = preg_replace('`\b(?:(?:https?|ftp|file)://|www\.|ftp\.)[-A-Z0-9+&@#/%=~_|$?!:,.]*[A-Z0-9+&@#/%=~_|$]`i', '<a href="$0">$0</a>', $tweetContent);
				$tweetContent = preg_replace('/(^|\s)@([a-z0-9_]+)/i', '$1<a href="http://www.twitter.com/$2">@$2</a>', $tweetContent);
				$tweetContent = preg_replace('/#([\\d\\w]+)/', '<a href="http://twitter.com/#search?q=%23$1">$0</a>', $tweetContent);
				
				print "<div class=\"tweet\"><div class=\"tweet-avatar\"><a href=\"http://twitter.com/$tweetAuthor\"><img src=\"$tweetAvatar\" alt=\"Twitter Avatar\" /></a></div><a class=\"tweet-author\" href=\"http://twitter.com/$tweetAuthor\">$tweetAuthor</a>: $tweetContent</div>";
				
				if ($i++ == 8) break;
			}
		}
		print "</div>";
	}
	$cache->close();
}

function getTwitterList($twitterListId) {
	global $twitterListApi;
	$connection = getTwitterAuthTokens ();
	$twitterListResults = $connection->get("lists/statuses", array('list_id' => $twitterListId));
	
	return $twitterListResults;
}

?>