<? 
require_once "/home/sciseek/public_html/dev/twitteroauth/twitteroauth/twitteroauth.php";
require_once "ss-util.php";
require_once "/home/sciseek/public_html/dev/wp-load.php";

global $homeUrl;
global $sitename;
global $twitterConsumerKey;
global $twitterConsumerSecret;
global $twitterListId;
global $twitterListToken;
global $twitterListTokenSecret;

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
	// If there is a note, post note
	elseif ($_REQUEST['note']) {
		$userSocialAccount = getUserSocialAccount(1, $userId, $db);
		$connection = new TwitterOAuth($twitterConsumerKey, $twitterConsumerSecret, $userSocialAccount['OAUTH_TOKEN'], $userSocialAccount['OAUTH_SECRET_TOKEN']);
		$tweetmsg = $_REQUEST['note'];
		$result = $connection->post('statuses/update', array('status' => $tweetmsg));
		$httpCode = $connection->http_code;
		if ($httpCode == 200) {
			$msg .= "<div class=\"message\">Tweet Posted: ".$tweetmsg."</div>";
		}
		else {
			$msg .= "<div class=\"message\">Could not post Tweet. Error: ".$httpCode." Reason: ".$result->error."</div>";
		}
	}
	else {
		if ($userSocialAccount) {
			$msg = "<div class=\"message\">Your Twitter account is already synced with our system, do you want to desync it?.</div>
			<a class=\"button\" title=\"Confirm social network account removal\" href=\"/sync/twitter/?remove=confirmed&url=$originalUrl\">Yes</a> <a class=\"button\" title=\"Go back to the original page\" href=\"$originalUrl\">No, go back to $sitename</a>";
		}
		else {
			$connection = new TwitterOAuth($twitterConsumerKey, $twitterConsumerSecret);
			$request_token = $connection->getRequestToken("http://dev.scienceseeker.org/sync/twitter/?url=$originalUrl");
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
	font-size: 1.4em; color: #B8B8B8; background: #1A1A1A; padding: 40px;
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