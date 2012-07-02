<? 

/*

Copyright © 2010–2012 Christopher R. Maden and Jessica Perry Hekman.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

include_once "ss-util.php";

global $homeUrl;
global $sitename;
global $twitterConsumerKey;
global $twitterConsumerSecret;
global $twitterListId;
global $twitterListToken;
global $twitterListTokenSecret;
global $wpLoad;
global $twitterOAuth;
global $syncPage;

include_once $wpLoad;

$originalUrl = $_REQUEST["url"];

if (! $originalUrl) {
	$originalUrl = $homeUrl;
}

session_start();

//If logged in, allow synchronization
if (is_user_logged_in()){
	$db = ssDbConnect();
	global $current_user;
	get_currentuserinfo();
	$displayName = $current_user->user_login;
	$email = $current_user->user_email;
	$userId = addUser($displayName, $email, $db);
	$userSocialAccount = getUserSocialAccount(1, $userId, $db);
	
	// If coming from Twitter with the new token, display this.
	if ($_REQUEST['oauth_token']) {
		if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
			$msg = "<div class=\"message\">Session Expired!</div>
			<a class=\"button\" href=\"/sync/twitter/\" title=\"Go to synchronization page\">Retry synchronization</a> <a class=\"button\" title=\"Go back to the original page\" href=\"$originalUrl\">Take me back to $sitename</a>";
		}
		else {
			// Get Twitter Tokens
			$connection = new TwitterOAuth($twitterConsumerKey, $twitterConsumerSecret, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
			$token_credentials = $connection->getAccessToken();
			
			// Add new user to member list.
			$connection = new TwitterOAuth($twitterConsumerKey, $twitterConsumerSecret, $twitterListToken, $twitterListTokenSecret);
			$result = $connection->post('lists/members/create', array('list_id' => $twitterListId, 'user_id' => $token_credentials['user_id']));
			$httpCode = $connection->http_code;
			
			addUserSocialAccount (1, $token_credentials['screen_name'], $token_credentials['oauth_token'], $token_credentials['oauth_token_secret'], $userId, $db);
			
			$msg = "<div class=\"message\">Your Twitter account has been successfully synced with our system.</div>
			<a class=\"button\" title=\"Go back to the original page\" href=\"$originalUrl\">Take me back to $sitename</a>";
		}
	}
	// Confirm account removal
	elseif ($_REQUEST["remove"] == "true") {
		if ($userSocialAccount) {
			$msg = "<div class=\"message\">Are you sure you want to remove your Twitter account from our system?</div>
			<a class=\"button\" title=\"Confirm social network account removal\" href=\"/sync/twitter/?remove=confirmed&url=$originalUrl\">Yes</a> <a class=\"button\" title=\"Cancel social network account removal\" href=\"$originalUrl\">No, go back to $sitename</a>";
		}
		else {
			$msg = "<div class=\"message\">You don't have any Twitter accounts associated with $sitename</div>
			<<a class=\"button\" href=\"/sync/twitter/\" title=\"Go to synchronization page\">Sync my Twitter account</a>  <a class=\"button\" title=\"Cancel social network account removal\" href=\"$originalUrl\">Take me back to $sitename</a>";
		}
	}
	elseif ($_REQUEST["remove"] == "confirmed") {
		if ($userSocialAccount) {
			$msg = "<div class=\"message\">Your Twitter account has been successfully removed from our system.</div>
			<a class=\"button\" title=\"Go back to the original page\" href=\"$originalUrl\">Take me back to $sitename</a>";
			removeUserSocialAccount(1, $userId, $db);
		}
	}
	else {
		if ($userSocialAccount) {
			$msg = "<div class=\"message\">Your Twitter account is already synced with our system, do you want to desync it?.</div>
			<a class=\"button\" title=\"Confirm social network account removal\" href=\"/sync/twitter/?remove=confirmed&url=$originalUrl\">Yes</a> <a class=\"button\" title=\"Go back to the original page\" href=\"$originalUrl\">No, go back to $sitename</a>";
		}
		else {
			$connection = new TwitterOAuth($twitterConsumerKey, $twitterConsumerSecret);
			$request_token = $connection->getRequestToken("$syncPage/?url=$originalUrl");
			$_SESSION['oauth_token'] = $request_token['oauth_token'];
			$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
			
			$twitterUrl = $connection->getAuthorizeURL($request_token, false);
			
			$msg = "<div class=\"message\">You will now be directed to Twitter, where you can authorize $sitename to view your tweets and update your status, you can remove this authorization at any time. If you enable the Twitter synchronization, you can share your $sitename notes on Twitter and be added to the $sitename member list.</div>
			<a class=\"button\" title=\"Go back to the Twitter authorization page\" href=\"$twitterUrl\">Continue to the next step</a> <a class=\"button\" title=\"Go back to the original page\" href=\"$originalUrl\">Take me back to $sitename</a>";
		}
	}
}
else {
	$msg = "<div class=\"message\">You must have a $sitename account to use this feature.</div>
	<a class=\"button\" title=\"Go to log in page\" href=\"$loginUrl\">Log in</a> <a class=\"button\" title=\"Go to register page\" href=\"$registerUrl\">Register</a> <a class=\"button\" title=\"Go back to the original page\" href=\"$originalUrl\">Take me back to $sitename</a>";
}

print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">

<html xmlns=\"http://www.w3.org/1999/xhtml\">

<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />

<title>Sync | ScienceSeeker: Science News Aggregator</title>
<link rel=\"shortcut icon\" href=\"http://dev.scienceseeker.org/wp-content/themes/eximius/images/favicon.ico\" type=\"image/x-icon\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"http://dev.scienceseeker.org/wp-content/themes/eximius/style.css\" media=\"all\">
<style type=\"text/css\">
* {
	padding: 0;
	margin: 0;
}
html {
	height: 100%;
}
body {
	height: 100%;
	width: 100%;
  background: url(/images/background/background.jpg) #5b5b5b;
}
#wrapper {
	width: 100%;
	min-height: 100%;
}
.message {
	font-size: 1.4em;
	color: #B8B8B8;
	background: #1A1A1A;
	padding: 40px 20%;
}
.button, a.button, a:visited.button {
	padding: 10px 40px 10px 40px;
	background: #5E5E5E;
	color: white;
	font-size: 1.2em;
	position: relative;
	top: 11px;
}
a.button:hover {
	background: #939393;
}
a.button:active {
	background: #303030;
}
</style>
</head>
<body>
<div id=\"wrapper\">
<div class=\"center-text\">
<a href=\"$homeUrl\" title=\"Home page\"><img style=\"margin: 100px 0px 100px 0px;\" src=\"/images/logos/ScienceSeekerMediumLogo.png\" /></a>
$msg
</div>
</div>
</body>
</html>"
?>