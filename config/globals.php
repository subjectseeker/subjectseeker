<?php

$sitename = "ScienceSeeker";
$subtitle = "Science News Aggregator";
$siteadminemail = "sciseekers@gmail.com";
$siteApprovalEmail = "SciSeek-Approve@googlegroups.com";
$contactEmail = "contact@scienceseeker.org";
$dbName = "subjectseeker";
$dbUser = "root";
$dbPass = "";
$host = "localhost";
$debugSite = "true";

// URLs
$homeUrl = "http://localhost/subjectseeker";
$imagesUrl = $homeUrl . "/images";
$jsUrl = $homeUrl . "/js";
$thirdPartyUrl = $homeUrl . "/third-party";
$themeUrl = $homeUrl . "/theme";
$scriptsUrl = $homeUrl . "/scripts";
$feedUrl = $homeUrl. "/feed/posts/";
$localStylesheet = "";

// Files and directories
$basedir = "C:/Users/Hewlett-Packard/Dropbox/SubjectSeeker";
$imagedir = "$basedir/images";
$cachedir = "$basedir/cache";
$configFile = "$basedir/config/pages.config";
$crawlerLock = "$basedir/cron/lock.txt";

// CitationSeeker URLs
$rfrId = "scienceseeker.org";
$crossRefUrl = "http://api.labs.crossref.org/search?q=";
$pubMedUrl = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&term=";
$pubMedIdUrl = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&retmode=xml&id=";
$arxivUrl = "http://export.arxiv.org/api/query?max_results=6&search_query=all:";

// Third Party APIs
$recaptchaPublicKey = "6LccLdUSAAAAACmythQ_99ow815l20ztwpk2Lsiw";
$recaptchaPrivateKey = "6LccLdUSAAAAAE57NPfoxI1DhG7FsQPml1SDl-HC";
$twitterConsumerKey = "6ql8Qi397KApfgenrNNqA";
$twitterConsumerSecret = "P3c4qNofPm5WlqfxTzzDVEfxTVnj72Z3e9N7JEYcU";
$twitterListId = "33101576";
$twitterListToken = "234367247-5EX8OOmCbRxX7QNVWYYPiMWN9j5rw8Hu830W0QpT";
$twitterListTokenSecret = "MouaWhQ8IGB0vrtixWAg0OMeYvgQUliLmKWLn1Z4";
$twitterNotesToken = "580140474-GgJIu9oW1eaju8BWVHiJmjlHP06TBmIBttFodivM";
$twitterNotesTokenSecret = "avGKg51w8C6X2RSqFkaYjJRKQv1KcAPQPWLF77Yct0";
$bitlyUser = "liminalityy";
$bitlyKey = "R_e4700fe46e057a194fc1cf18d2d1ae23";
$bitlyApiUrl = "http://api.bit.ly/v3/shorten?login=";

// Parameters
$numResults = 30;
$maximumResults = 500;

// E-mails
$rejectedSiteReasons = "	1. The site may not have enough science content. We are flexible in our definition of a \"science\" site, but science must be one of the main topics.
	
	2. The site must produce primarily original content that does not aggregate other sources. In no case is plagiarism allowed. When other sources are used, they must be clearly cited.
	
	3. Claims without scientific evidence that fall within the realm of pseudoscience and misinformation are not allowed."; // Reasons why a site is rejected to include in an email to the author.

?>
