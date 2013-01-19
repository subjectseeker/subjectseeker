<?php

$sitename = "SubjectSeeker";
$subtitle = "News Aggregator";
$siteadminemail = "admin@example.com";
$siteApprovalEmail = "admin@example.com";
$contactEmail = "admin@example.com";
$dbName = "databaseName";
$dbUser = "databaseUser";
$dbPass = "password";
$host = "localhost";
$debugSite = "false";
$httpsEnabled = "false";
$themeName = "scienceseeker";

// URLs
$homeUrl = "http://example.com";
$imagesUrl = $homeUrl . "/images";
$jsUrl = $homeUrl . "/js";
$thirdPartyUrl = $homeUrl . "/third-party";
$themeUrl = $homeUrl . "/themes/scienceseeker";
$scriptsUrl = $homeUrl . "/scripts";
$feedUrl = $homeUrl. "/feed/posts/";
$customHead = "";

// Files and directories
$basedir = "/home/username/public_html";
$imagedir = "$basedir/images";
$cachedir = "$basedir/cache";
$configFile = "$basedir/config/pages.config";
$crawlerLock = "$basedir/cron/lock.txt";

// CitationSeeker URLs
$rfrId = "example.com";
$crossRefUrl = "http://api.labs.crossref.org/search?q=";
$pubMedUrl = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&term=";
$pubMedIdUrl = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&retmode=xml&id=";
$arxivUrl = "http://export.arxiv.org/api/query?max_results=6&search_query=all:";

// Social Network Integration
$googleClientId = "****"; // Google API client ID
$googleClientSecret = "*****"; // Google API client secret code
$googleApiKey = "****"; // Google API key
$recaptchaPublicKey = "****"; // Recaptcha Public Key
$recaptchaPrivateKey = "****"; // Recaptcha Private Key
$twitterConsumerKey = "****"; // Twitter Consumer Key
$twitterConsumerSecret = "****"; // Twitter Consumer Secret Key
$twitterListId = "****"; // Twitter ID of Member List
$twitterListToken = "****"; // Twitter Token for account that owns the member list
$twitterListTokenSecret = "****"; // Twitter Secret Token for account that owns the member list
$twitterNotesToken = "****"; // Twitter Token of Twitter account that tweets all notes
$twitterNotesTokenSecret = "****"; // Twitter Secret Token of Twitter account that tweets all notes
$bitlyUser = "****"; // bitly user
$bitlyKey = "****"; // bitly API key
$bitlyApiUrl = "http://api.bit.ly/v3/shorten?login="; // bitly API url
$twitterListApi = "https://api.twitter.com/1/lists/statuses.json?list_id="; // Twitter list API link
$twitterUserApi = "http://api.twitter.com/1/users/lookup.json?user_id="; // Twitter user details API link

// Parameters
$numResults = 30;
$maximumResults = 500;
$cacheTime = 3600; // Seconds

// E-mails
$rejectedSiteReasons = "	1. The site may not have enough science content. We are flexible in our definition of a \"science\" site, but science must be one of the main topics.
	
	2. The site must produce primarily original content that does not aggregate other sources. In no case is plagiarism allowed. When other sources are used, they must be clearly cited.
	
	3. Claims without scientific evidence that fall within the realm of pseudoscience and misinformation are not allowed."; // Reasons why a site is rejected to include in an email to the author.
	
?>