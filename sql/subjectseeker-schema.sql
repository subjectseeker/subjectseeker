-- phpMyAdmin SQL Dump
-- version 3.4.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 21, 2013 at 08:26 AM
-- Server version: 5.0.95
-- PHP Version: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `sciseek_backend_alpha`
--

-- --------------------------------------------------------

--
-- Table structure for table `ADMINISTRATOR_NOTE`
--

DROP TABLE IF EXISTS `ADMINISTRATOR_NOTE`;
CREATE TABLE IF NOT EXISTS `ADMINISTRATOR_NOTE` (
  `ADMINISTRATOR_NOTE_ID` int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for a note.',
  `ADMINISTRATOR_NOTE_CONTENT` text collate utf8_unicode_ci NOT NULL COMMENT 'The free-text content of a note.',
  PRIMARY KEY  (`ADMINISTRATOR_NOTE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A system internal note associated with a particular system o';

-- --------------------------------------------------------

--
-- Table structure for table `ARTICLE`
--

DROP TABLE IF EXISTS `ARTICLE`;
CREATE TABLE IF NOT EXISTS `ARTICLE` (
  `ARTICLE_ID` int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for an article.',
  `ARTICLE_TITLE` varchar(900) collate utf8_unicode_ci default NULL COMMENT 'Title of an article.',
  `ARTICLE_JOURNAL_TITLE` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'Name of a journal where the article has been published.',
  `ARTICLE_JOURNAL_ISSUE` varchar(100) collate utf8_unicode_ci default NULL COMMENT 'Issue of the journal where an article was published.',
  `ARTICLE_JOURNAL_VOLUME` varchar(100) collate utf8_unicode_ci default NULL COMMENT 'Volume of the journal where an article was published.',
  `ARTICLE_ISSN` varchar(30) collate utf8_unicode_ci default NULL COMMENT 'ISSN code associated with an article.',
  `ARTICLE_NUMBER` varchar(2083) collate utf8_unicode_ci default NULL COMMENT 'Identifier associated with an article in the journal.',
  `ARTICLE_PUBLICATION_DATE` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'Year of publication of an article.',
  `ARTICLE_START_PAGE` varchar(30) collate utf8_unicode_ci default NULL COMMENT 'Start page of an article in the journal.',
  `ARTICLE_END_PAGE` varchar(30) collate utf8_unicode_ci default NULL COMMENT 'End page of an article in the journal.',
  `ARTICLE_FROM_ORIGINAL_SOURCE` tinyint(1) NOT NULL default '0' COMMENT 'Indicator whether this article data is based on the original source.',
  PRIMARY KEY  (`ARTICLE_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Peer-reviewed articles.';

-- --------------------------------------------------------

--
-- Table structure for table `ARTICLE_AUTHOR`
--

DROP TABLE IF EXISTS `ARTICLE_AUTHOR`;
CREATE TABLE IF NOT EXISTS `ARTICLE_AUTHOR` (
  `ARTICLE_AUTHOR_ID` int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for this article author.',
  `ARTICLE_AUTHOR_FIRST_NAME` varchar(125) collate utf8_unicode_ci default NULL COMMENT 'First name of the article author.',
  `ARTICLE_AUTHOR_LAST_NAME` varchar(125) collate utf8_unicode_ci default NULL COMMENT 'Last name of the article''s author.',
  `ARTICLE_AUTHOR_FULL_NAME` varchar(250) collate utf8_unicode_ci default NULL COMMENT 'Full name of the article author',
  PRIMARY KEY  (`ARTICLE_AUTHOR_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Authors of peer-reviewed articles.';

-- --------------------------------------------------------

--
-- Table structure for table `ARTICLE_AUTHOR_LINK`
--

DROP TABLE IF EXISTS `ARTICLE_AUTHOR_LINK`;
CREATE TABLE IF NOT EXISTS `ARTICLE_AUTHOR_LINK` (
  `ARTICLE_ID` int(15) NOT NULL COMMENT 'Reference to an article created by an author.',
  `ARTICLE_AUTHOR_ID` int(15) NOT NULL COMMENT 'Reference to an author of an article.',
  UNIQUE KEY `FK_AUTHOR_TO_ARTICLE` (`ARTICLE_ID`,`ARTICLE_AUTHOR_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Articles containing these authors.';

-- --------------------------------------------------------

--
-- Table structure for table `ARTICLE_IDENTIFIER`
--

DROP TABLE IF EXISTS `ARTICLE_IDENTIFIER`;
CREATE TABLE IF NOT EXISTS `ARTICLE_IDENTIFIER` (
  `ARTICLE_IDENTIFIER_ID` int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for an article identifier.',
  `ARTICLE_IDENTIFIER_TYPE` varchar(20) collate utf8_unicode_ci NOT NULL COMMENT 'Type of identifier associated with an article (DOI, PMID, arXiv...)',
  `ARTICLE_IDENTIFIER_TEXT` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Identifier associated with an article.',
  `ARTICLE_ID` int(15) NOT NULL COMMENT 'Reference to an article with this ID.',
  PRIMARY KEY  (`ARTICLE_IDENTIFIER_ID`),
  UNIQUE KEY `FK_ARTICLE_IDENTIFIER` (`ARTICLE_IDENTIFIER_TEXT`,`ARTICLE_IDENTIFIER_TYPE`,`ARTICLE_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Unique article identifiers.';

-- --------------------------------------------------------

--
-- Table structure for table `BLOG`
--

DROP TABLE IF EXISTS `BLOG`;
CREATE TABLE IF NOT EXISTS `BLOG` (
  `BLOG_ID` int(10) unsigned NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for this blog.',
  `BLOG_STATUS_ID` tinyint(3) unsigned NOT NULL default '0' COMMENT 'Reference to the moderation status associated with this blog.',
  `BLOG_NAME` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Human-readable display name of this blog.',
  `BLOG_URI` text collate utf8_unicode_ci NOT NULL COMMENT 'The URI intended for visiting this blog in normal usage.',
  `BLOG_SYNDICATION_URI` text collate utf8_unicode_ci NOT NULL COMMENT 'The URI via which a syndication feed for this blog can be accessed.',
  `BLOG_DESCRIPTION` text collate utf8_unicode_ci COMMENT 'The free-text human-readable description of the nature and intent of the blog.',
  `ADDED_DATE_TIME` datetime NOT NULL COMMENT 'The date and time when this blog was added to the aggregator.',
  `CRAWLED_DATE_TIME` datetime default NULL COMMENT 'The date and time on which this blog was last checked by the aggregator.',
  `BLOG_BANNER` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Name of a banner associated with a blog.',
  PRIMARY KEY  (`BLOG_ID`),
  KEY `FK_BLOG_STATUS` (`BLOG_STATUS_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A Web log or feed therefrom intended for aggregation by the ';

-- --------------------------------------------------------

--
-- Table structure for table `BLOG_ADMINISTRATOR_NOTE`
--

DROP TABLE IF EXISTS `BLOG_ADMINISTRATOR_NOTE`;
CREATE TABLE IF NOT EXISTS `BLOG_ADMINISTRATOR_NOTE` (
  `ADMINISTRATOR_NOTE_ID` int(15) NOT NULL COMMENT 'Reference to a note associated with this blog.',
  `BLOG_ID` int(15) NOT NULL COMMENT 'Reference to a blog annotated by this note.',
  PRIMARY KEY  (`ADMINISTRATOR_NOTE_ID`,`BLOG_ID`),
  KEY `FK_BLOG_NOTES` (`BLOG_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Association of an administrator note with a particular blog.';

-- --------------------------------------------------------

--
-- Table structure for table `BLOG_AUTHOR`
--

DROP TABLE IF EXISTS `BLOG_AUTHOR`;
CREATE TABLE IF NOT EXISTS `BLOG_AUTHOR` (
  `BLOG_AUTHOR_ID` int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for this blog author.',
  `BLOG_ID` int(15) NOT NULL COMMENT 'Reference to the blog with which this author is associated.',
  `USER_ID` int(15) default NULL COMMENT 'Reference to an user associated with this author.',
  `BLOG_AUTHOR_ACCOUNT_NAME` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'The identifier used for this author on the blog to which they contribute: the user name or display name, as appropriate for the blog in question.',
  PRIMARY KEY  (`BLOG_AUTHOR_ID`),
  UNIQUE KEY `AK_BLOG_AUTHOR_ACCOUNT` (`BLOG_ID`,`BLOG_AUTHOR_ACCOUNT_NAME`),
  KEY `FK_CLAIMED_AUTHORS` (`USER_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A known contributor to a blog of interest, who may or may no';

-- --------------------------------------------------------

--
-- Table structure for table `BLOG_GROUP`
--

DROP TABLE IF EXISTS `BLOG_GROUP`;
CREATE TABLE IF NOT EXISTS `BLOG_GROUP` (
  `BLOG_GROUP_ID` int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for this group.',
  `BLOG_GROUP_NAME` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Human-readable display name of this group.',
  `BLOG_GROUP_URI` varchar(2083) collate utf8_unicode_ci NOT NULL COMMENT 'The URI intended for visiting this blog group in normal usage.',
  `BLOG_GROUP_SYNDICATION_URI` varchar(2083) collate utf8_unicode_ci default NULL COMMENT 'The URI via which a syndication feed for this blog group can be accessed.',
  `BLOG_GROUP_DESCRIPTION` text collate utf8_unicode_ci COMMENT 'The free-text human-readable description of the nature and intent of the blog group.',
  PRIMARY KEY  (`BLOG_GROUP_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A collection of blogs characterized by network affiliation, ';

-- --------------------------------------------------------

--
-- Table structure for table `BLOG_GROUPING`
--

DROP TABLE IF EXISTS `BLOG_GROUPING`;
CREATE TABLE IF NOT EXISTS `BLOG_GROUPING` (
  `BLOG_GROUP_ID` int(15) NOT NULL COMMENT 'Reference to the group into which the blog is being grouped.',
  `BLOG_ID` int(15) NOT NULL COMMENT 'Reference to the blog being grouped.',
  PRIMARY KEY  (`BLOG_GROUP_ID`,`BLOG_ID`),
  KEY `FK_BLOG_GROUPS` (`BLOG_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Association of blogs into groups.';

-- --------------------------------------------------------

--
-- Table structure for table `BLOG_GROUP_ADMINISTRATOR_NOTE`
--

DROP TABLE IF EXISTS `BLOG_GROUP_ADMINISTRATOR_NOTE`;
CREATE TABLE IF NOT EXISTS `BLOG_GROUP_ADMINISTRATOR_NOTE` (
  `ADMINISTRATOR_NOTE_ID` int(15) NOT NULL COMMENT 'Reference to a note associated with this blog group.',
  `BLOG_GROUP_ID` int(15) NOT NULL COMMENT 'Reference to a blog group annotated by this note.',
  PRIMARY KEY  (`ADMINISTRATOR_NOTE_ID`,`BLOG_GROUP_ID`),
  KEY `FK_BLOG_GROUP_NOTES` (`BLOG_GROUP_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Association of an administrator note with a particular blog ';

-- --------------------------------------------------------

--
-- Table structure for table `BLOG_LANGUAGE`
--

DROP TABLE IF EXISTS `BLOG_LANGUAGE`;
CREATE TABLE IF NOT EXISTS `BLOG_LANGUAGE` (
  `LANGUAGE_ID` int(15) NOT NULL COMMENT 'Reference to the language in which a blog is written.',
  `BLOG_ID` int(15) NOT NULL COMMENT 'Reference to a blog written in this language.',
  PRIMARY KEY  (`LANGUAGE_ID`,`BLOG_ID`),
  KEY `FK_BLOG_LANGUAGES` (`BLOG_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Languages used by blogs of interest.';

-- --------------------------------------------------------

--
-- Table structure for table `BLOG_POST`
--

DROP TABLE IF EXISTS `BLOG_POST`;
CREATE TABLE IF NOT EXISTS `BLOG_POST` (
  `BLOG_POST_ID` int(15) unsigned NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for this blog post.',
  `BLOG_ID` int(15) unsigned NOT NULL COMMENT 'Reference to the blog on which this post was made.',
  `BLOG_AUTHOR_ID` int(15) unsigned NOT NULL COMMENT 'Reference to the author credited with this blog post.',
  `LANGUAGE_ID` int(15) default NULL COMMENT 'Reference to the language or locale in which this post is published.',
  `BLOG_POST_STATUS_ID` int(15) NOT NULL COMMENT 'Reference to the moderation status of this blog.',
  `BLOG_POST_URI` varchar(2083) collate utf8_unicode_ci NOT NULL COMMENT 'The URI at which this blog post can be read in normal usage',
  `BLOG_POST_DATE_TIME` datetime NOT NULL COMMENT 'The date and time at which this post was published to its home blog.',
  `BLOG_POST_INGEST_DATE_TIME` datetime NOT NULL COMMENT 'The date and time at which this post was collected by the aggregation system.',
  `BLOG_POST_SUMMARY` text collate utf8_unicode_ci NOT NULL COMMENT 'A free text summary of the blog post, typically created by truncating the start of the post.',
  `BLOG_POST_TITLE` varchar(1023) collate utf8_unicode_ci default NULL COMMENT 'The original title of the blog post.',
  `BLOG_POST_HAS_CITATION` tinyint(1) NOT NULL default '0' COMMENT 'Indicator whether this blog post has a research citation in it.',
  PRIMARY KEY  (`BLOG_POST_ID`),
  KEY `FK_BLOG_POSTS` (`BLOG_ID`),
  KEY `FK_BLOG_POST_LANGUAGE` (`LANGUAGE_ID`),
  KEY `FK_BLOG_POST_STATUS` (`BLOG_POST_STATUS_ID`),
  KEY `FK_POST_AUTHOR` (`BLOG_AUTHOR_ID`),
  KEY `BLOG_POST_TITLE` (`BLOG_POST_TITLE`(333)),
  FULLTEXT KEY `BLOG_POST_SUMMARY` (`BLOG_POST_SUMMARY`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A post to a blog of interest to the system.';

-- --------------------------------------------------------

--
-- Table structure for table `BLOG_POST_ADMINISTRATOR_NOTE`
--

DROP TABLE IF EXISTS `BLOG_POST_ADMINISTRATOR_NOTE`;
CREATE TABLE IF NOT EXISTS `BLOG_POST_ADMINISTRATOR_NOTE` (
  `ADMINISTRATOR_NOTE_ID` int(15) NOT NULL COMMENT 'Reference to a note associated with this blog post.',
  `BLOG_POST_ID` int(15) NOT NULL COMMENT 'Reference to a blog post annotated by this note.',
  PRIMARY KEY  (`ADMINISTRATOR_NOTE_ID`,`BLOG_POST_ID`),
  KEY `FK_BLOG_POST_NOTES` (`BLOG_POST_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Association of an administrator note with a particular blog ';

-- --------------------------------------------------------

--
-- Table structure for table `BLOG_POST_STATUS`
--

DROP TABLE IF EXISTS `BLOG_POST_STATUS`;
CREATE TABLE IF NOT EXISTS `BLOG_POST_STATUS` (
  `BLOG_POST_STATUS_ID` int(15) NOT NULL COMMENT 'Machine-readable unique identifier for a blog post status level.',
  `BLOG_POST_STATUS_DESCRIPTION` varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'Human-readable description of a blog post status level.',
  PRIMARY KEY  (`BLOG_POST_STATUS_ID`),
  UNIQUE KEY `AK_BLOG_POST_STATUS_DESCRIPTION` (`BLOG_POST_STATUS_DESCRIPTION`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Approval status of a blog post, e.g., active, under review.';

-- --------------------------------------------------------

--
-- Table structure for table `BLOG_SOCIAL_ACCOUNT`
--

DROP TABLE IF EXISTS `BLOG_SOCIAL_ACCOUNT`;
CREATE TABLE IF NOT EXISTS `BLOG_SOCIAL_ACCOUNT` (
  `SOCIAL_NETWORKING_ACCOUNT_ID` int(10) unsigned NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for a social networking account.',
  `SOCIAL_NETWORK_ID` int(15) NOT NULL COMMENT 'Reference to the social network on which this account is hosted.',
  `SOCIAL_NETWORKING_ACCOUNT_NAME` varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'The account identifier a persona uses on a particular social network.',
  `BLOG_ID` int(10) unsigned default NULL COMMENT 'The ID of a blog associated with this account.',
  `OAUTH_TOKEN` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'OAuth token for the social network API.',
  `OAUTH_SECRET_TOKEN` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'OAuth secret token for the social network API.',
  PRIMARY KEY  (`SOCIAL_NETWORKING_ACCOUNT_ID`),
  UNIQUE KEY `AK_SOCIAL_NETWORK_USER_LOCATOR` (`SOCIAL_NETWORK_ID`,`SOCIAL_NETWORKING_ACCOUNT_NAME`),
  UNIQUE KEY `FK_BLOG_ID` (`BLOG_ID`,`SOCIAL_NETWORK_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Social account to be associated with a blog';

-- --------------------------------------------------------

--
-- Table structure for table `BLOG_STATUS`
--

DROP TABLE IF EXISTS `BLOG_STATUS`;
CREATE TABLE IF NOT EXISTS `BLOG_STATUS` (
  `BLOG_STATUS_ID` int(15) NOT NULL COMMENT 'Machine-generated unique identifier for a blog status level.',
  `BLOG_STATUS_DESCRIPTION` varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'Human-readable description of a blog status level.',
  PRIMARY KEY  (`BLOG_STATUS_ID`),
  UNIQUE KEY `AK_BLOG_STATUS_DESCRIPTION` (`BLOG_STATUS_DESCRIPTION`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The approval status of a blog, e.g., submitted, under review';

-- --------------------------------------------------------

--
-- Table structure for table `CITATION`
--

DROP TABLE IF EXISTS `CITATION`;
CREATE TABLE IF NOT EXISTS `CITATION` (
  `CITATION_ID` int(15) NOT NULL auto_increment COMMENT 'A machine-generated unique identifier for a citation.',
  `CITATION_TEXT` text collate utf8_unicode_ci NOT NULL COMMENT 'The HTML text of this citation',
  `ARTICLE_ID` int(15) NOT NULL COMMENT 'Reference to an article with this citation.',
  PRIMARY KEY  (`CITATION_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `CLAIM_BLOG`
--

DROP TABLE IF EXISTS `CLAIM_BLOG`;
CREATE TABLE IF NOT EXISTS `CLAIM_BLOG` (
  `CLAIM_ID` int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for this claim.',
  `BLOG_ID` int(15) NOT NULL COMMENT 'Reference to a blog which is pending being claimed.',
  `USER_ID` int(15) NOT NULL COMMENT 'Reference to a user who wishes to claim a blog.',
  `CLAIM_TOKEN` varchar(64) collate utf8_unicode_ci NOT NULL COMMENT 'Token for use in claiming a blog.',
  `CLAIM_STATUS_ID` int(15) NOT NULL COMMENT 'ID of status of this claim',
  `CLAIM_DATE_TIME` datetime NOT NULL COMMENT 'The date and time at which this claim request was made.',
  PRIMARY KEY  (`CLAIM_ID`),
  KEY `FK_CLAIM_STATUS_ID` (`CLAIM_STATUS_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `CLAIM_BLOG_STATUS`
--

DROP TABLE IF EXISTS `CLAIM_BLOG_STATUS`;
CREATE TABLE IF NOT EXISTS `CLAIM_BLOG_STATUS` (
  `CLAIM_BLOG_STATUS_ID` int(15) NOT NULL COMMENT 'Numeric ID of a status.',
  `CLAIM_BLOG_STATUS_DESCRIPTION` int(15) NOT NULL COMMENT 'Textual description of a status.',
  PRIMARY KEY  (`CLAIM_BLOG_STATUS_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `COMMENT`
--

DROP TABLE IF EXISTS `COMMENT`;
CREATE TABLE IF NOT EXISTS `COMMENT` (
  `COMMENT_ID` int(10) unsigned NOT NULL auto_increment COMMENT 'Unique identifier of a comment.',
  `OBJECT_ID` int(10) unsigned NOT NULL COMMENT 'Unique identifier of an entry associated with a comment.',
  `USER_ID` int(10) unsigned NOT NULL COMMENT 'Unique identifier of a user author of a comment.',
  `OBJECT_TYPE_ID` int(11) NOT NULL COMMENT 'Unique identifier of a type of comment.',
  `COMMENT_SOURCE_ID` int(11) NOT NULL COMMENT 'Unique identifier of the source of a comment.',
  `COMMENT_TEXT` text collate utf8_unicode_ci NOT NULL COMMENT 'Comment text.',
  `COMMENT_DATE_TIME` datetime NOT NULL COMMENT 'Date and time a comment was made',
  PRIMARY KEY  (`COMMENT_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Comments associated with rows in the database';

-- --------------------------------------------------------

--
-- Table structure for table `FOLLOWER`
--

DROP TABLE IF EXISTS `FOLLOWER`;
CREATE TABLE IF NOT EXISTS `FOLLOWER` (
  `USER_ID` int(10) unsigned NOT NULL COMMENT 'Unique identifier of a follower user.',
  `OBJECT_TYPE_ID` int(10) NOT NULL COMMENT 'Unique identifier of an object type.',
  `OBJECT_ID` int(10) unsigned NOT NULL COMMENT 'Unique identifier of a followed object.',
  PRIMARY KEY  (`USER_ID`,`OBJECT_ID`,`OBJECT_TYPE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='User IDs of followers a followees.';

-- --------------------------------------------------------

--
-- Table structure for table `GROUP`
--

DROP TABLE IF EXISTS `GROUP`;
CREATE TABLE IF NOT EXISTS `GROUP` (
  `GROUP_ID` int(10) unsigned NOT NULL auto_increment COMMENT 'Unique identifier of a group.',
  `GROUP_NAME` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Name of a group.',
  `GROUP_DESCRIPTION` text collate utf8_unicode_ci NOT NULL COMMENT 'Description of a group.',
  `GROUP_BANNER` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'Name of an image associated with a group.',
  `GROUP_MATCHING_POSTS` tinyint(1) NOT NULL default '1' COMMENT 'Option for groups to display matching posts.',
  `GROUP_MATCHING_SITES` tinyint(1) NOT NULL default '0' COMMENT 'Option for groups to display posts from matching sites.',
  `CREATION_DATE_TIME` datetime NOT NULL COMMENT 'Date and time of creation of a group.',
  PRIMARY KEY  (`GROUP_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A group of objects in the database.';

-- --------------------------------------------------------

--
-- Table structure for table `GROUP_MANAGER`
--

DROP TABLE IF EXISTS `GROUP_MANAGER`;
CREATE TABLE IF NOT EXISTS `GROUP_MANAGER` (
  `GROUP_ID` int(10) NOT NULL COMMENT 'Reference to a group.',
  `USER_ID` int(10) NOT NULL COMMENT 'Reference to a user of a group.',
  `MANAGER_PRIVILEGE_ID` int(10) NOT NULL COMMENT 'Reference to the managing privileges of a group.',
  PRIMARY KEY  (`GROUP_ID`,`USER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Associations between groups and managers of the group.';

-- --------------------------------------------------------

--
-- Table structure for table `GROUP_TAG`
--

DROP TABLE IF EXISTS `GROUP_TAG`;
CREATE TABLE IF NOT EXISTS `GROUP_TAG` (
  `GROUP_ID` int(10) NOT NULL COMMENT 'Reference to a group.',
  `TAG_ID` int(10) NOT NULL COMMENT 'Reference to a tag associated with a group.',
  PRIMARY KEY  (`GROUP_ID`,`TAG_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Associations between groups and tags.';

-- --------------------------------------------------------

--
-- Table structure for table `LANGUAGE`
--

DROP TABLE IF EXISTS `LANGUAGE`;
CREATE TABLE IF NOT EXISTS `LANGUAGE` (
  `LANGUAGE_ID` int(15) NOT NULL auto_increment COMMENT 'A machine-generated unique identifier for a human language.',
  `LANGUAGE_IETF_CODE` varchar(31) collate utf8_unicode_ci NOT NULL COMMENT 'IETF BCP 47 code for a language or locale, including language, region, and script information, e.g. en, en-US, en-latn-US.',
  `LANGUAGE_ENGLISH_NAME` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Human-readable English name of an IETF locale or language.',
  `LANGUAGE_LOCAL_NAME` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'Human-readable name of an IETF locale or language, in the language and script so described.',
  PRIMARY KEY  (`LANGUAGE_ID`),
  UNIQUE KEY `AK_LANGUAGE_IETF_CODE` (`LANGUAGE_IETF_CODE`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A human language (actually a locale), as defined in IETF BCP';

-- --------------------------------------------------------

--
-- Table structure for table `NOTIFICATION`
--

DROP TABLE IF EXISTS `NOTIFICATION`;
CREATE TABLE IF NOT EXISTS `NOTIFICATION` (
  `NOTIFICATION_ID` int(11) NOT NULL auto_increment COMMENT 'Unique identifier of a notification',
  `OBJECT_ID` int(11) NOT NULL COMMENT 'Reference to an object.',
  `OBJECT_TYPE_ID` int(11) NOT NULL COMMENT 'Reference to a type of object.',
  `USER_ID` int(11) default NULL COMMENT 'Reference to a user.',
  `NOTIFICATION_STATUS_ID` tinyint(4) NOT NULL COMMENT 'Reference to the status of a notification.',
  `NOTIFICATION_TYPE_ID` int(11) NOT NULL COMMENT 'Reference to a type of notification.',
  `NOTIFICATION_DATE_TIME` datetime NOT NULL COMMENT 'Date and time of creation of a notification.',
  PRIMARY KEY  (`NOTIFICATION_ID`),
  UNIQUE KEY `OBJECT_ID` (`OBJECT_ID`,`OBJECT_TYPE_ID`,`USER_ID`,`NOTIFICATION_TYPE_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Notifications to be sent to users.';

-- --------------------------------------------------------

--
-- Table structure for table `OBJECT_TYPE`
--

DROP TABLE IF EXISTS `OBJECT_TYPE`;
CREATE TABLE IF NOT EXISTS `OBJECT_TYPE` (
  `OBJECT_TYPE_ID` int(15) NOT NULL COMMENT 'Unique identifier of type of object.',
  `OBJECT_TYPE_NAME` varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'Name of a type of object.',
  PRIMARY KEY  (`OBJECT_TYPE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Types of objects in the database.';

-- --------------------------------------------------------

--
-- Table structure for table `PERSONA_ADMINISTRATOR_NOTE`
--

DROP TABLE IF EXISTS `PERSONA_ADMINISTRATOR_NOTE`;
CREATE TABLE IF NOT EXISTS `PERSONA_ADMINISTRATOR_NOTE` (
  `ADMINISTRATOR_NOTE_ID` int(15) NOT NULL COMMENT 'Reference to a note associated with this persona.',
  `PERSONA_ID` int(15) NOT NULL COMMENT 'Reference to a person annotated by this note.',
  PRIMARY KEY  (`PERSONA_ID`,`ADMINISTRATOR_NOTE_ID`),
  KEY `FK_NOTE_PERSONAE` (`ADMINISTRATOR_NOTE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Association of an administrator note with a particular perso';

-- --------------------------------------------------------

--
-- Table structure for table `POST_CITATION`
--

DROP TABLE IF EXISTS `POST_CITATION`;
CREATE TABLE IF NOT EXISTS `POST_CITATION` (
  `CITATION_ID` int(15) NOT NULL COMMENT 'Reference to a citation included in a post.',
  `BLOG_POST_ID` int(15) NOT NULL COMMENT 'Reference to a post including a citation.',
  PRIMARY KEY  (`CITATION_ID`,`BLOG_POST_ID`),
  UNIQUE KEY `FK_CITATION_TO_POST` (`CITATION_ID`,`BLOG_POST_ID`),
  KEY `FK_POST_CITATIONS` (`BLOG_POST_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Blog posts containing citations.';

-- --------------------------------------------------------

--
-- Table structure for table `POST_TOPIC`
--

DROP TABLE IF EXISTS `POST_TOPIC`;
CREATE TABLE IF NOT EXISTS `POST_TOPIC` (
  `TOPIC_ID` int(15) unsigned NOT NULL COMMENT 'Reference to a topic covered by a post.',
  `BLOG_POST_ID` int(15) unsigned NOT NULL COMMENT 'Reference to a post covering a topic.',
  `TOPIC_SOURCE` int(15) NOT NULL COMMENT 'Reference to the source of this topic',
  PRIMARY KEY  (`TOPIC_ID`,`BLOG_POST_ID`),
  KEY `FK_POST_TOPICS` (`BLOG_POST_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Subjects of a particular post on a blog of interest.';

-- --------------------------------------------------------

--
-- Table structure for table `PRIMARY_BLOG_TOPIC`
--

DROP TABLE IF EXISTS `PRIMARY_BLOG_TOPIC`;
CREATE TABLE IF NOT EXISTS `PRIMARY_BLOG_TOPIC` (
  `TOPIC_ID` int(15) NOT NULL COMMENT 'Reference to a topic primarily covered by a blog.',
  `BLOG_ID` int(15) NOT NULL COMMENT 'Reference to a blog primarily covering a topic.',
  PRIMARY KEY  (`TOPIC_ID`,`BLOG_ID`),
  KEY `FK_BLOG_PRIMARY_TOPICS` (`BLOG_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The main subjects of a blog of interest.';

-- --------------------------------------------------------

--
-- Table structure for table `RECOMMENDATION`
--

DROP TABLE IF EXISTS `RECOMMENDATION`;
CREATE TABLE IF NOT EXISTS `RECOMMENDATION` (
  `RECOMMENDATION_ID` int(11) NOT NULL auto_increment COMMENT 'Unique identifier of recommendation.',
  `OBJECT_ID` int(11) NOT NULL COMMENT 'ID of the row that is being recommended.',
  `USER_ID` int(11) NOT NULL COMMENT 'ID of the user who made the recommendation.',
  `OBJECT_TYPE_ID` int(11) NOT NULL COMMENT 'ID of the type of recommendation.',
  `REC_DATE_TIME` datetime NOT NULL COMMENT 'Date and time the recommendation was made.',
  `REC_IMAGE` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Name of an image associated with a recommendation.',
  `REC_NOTIFICATION` tinyint(4) NOT NULL default '0' COMMENT 'Indicator of pending notification.',
  PRIMARY KEY  (`RECOMMENDATION_ID`),
  UNIQUE KEY `AK_USER_TO_TYPE` (`USER_ID`,`OBJECT_TYPE_ID`,`OBJECT_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Recommendations of elements in the database.';

-- --------------------------------------------------------

--
-- Table structure for table `SCAN_POST`
--

DROP TABLE IF EXISTS `SCAN_POST`;
CREATE TABLE IF NOT EXISTS `SCAN_POST` (
  `BLOG_ID` int(15) NOT NULL COMMENT 'Reference to a blog that must be scanned.',
  `MARKER_DATE_TIME` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP COMMENT 'Date of creation of a marker',
  `MARKER_TYPE_ID` int(15) NOT NULL COMMENT 'Reference to the type of marker.',
  UNIQUE KEY `FK_MARKER` (`BLOG_ID`,`MARKER_TYPE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Blogs with markers for the crawler.';

-- --------------------------------------------------------

--
-- Table structure for table `SECONDARY_BLOG_TOPIC`
--

DROP TABLE IF EXISTS `SECONDARY_BLOG_TOPIC`;
CREATE TABLE IF NOT EXISTS `SECONDARY_BLOG_TOPIC` (
  `TOPIC_ID` int(15) NOT NULL COMMENT 'Reference to a topic secondarily covered by a blog.',
  `BLOG_ID` int(15) NOT NULL COMMENT 'Reference to a blog secondarily covering a topic.',
  PRIMARY KEY  (`TOPIC_ID`,`BLOG_ID`),
  KEY `FK_BLOG_SECONDARY_TOPICS` (`BLOG_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Subjects of a blog of interest, other than the primary topic';

-- --------------------------------------------------------

--
-- Table structure for table `SOCIAL_NETWORK`
--

DROP TABLE IF EXISTS `SOCIAL_NETWORK`;
CREATE TABLE IF NOT EXISTS `SOCIAL_NETWORK` (
  `SOCIAL_NETWORK_ID` int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for a social network.',
  `SOCIAL_NETWORK_NAME` varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'Human-readable name of a social network.',
  PRIMARY KEY  (`SOCIAL_NETWORK_ID`),
  UNIQUE KEY `AK_SOCIAL_NETWORK_NAME` (`SOCIAL_NETWORK_NAME`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A social network system with well-defined unique user IDs, t';

-- --------------------------------------------------------

--
-- Table structure for table `SOCIAL_NETWORK_USER`
--

DROP TABLE IF EXISTS `SOCIAL_NETWORK_USER`;
CREATE TABLE IF NOT EXISTS `SOCIAL_NETWORK_USER` (
  `SOCIAL_NETWORK_USER_ID` int(10) unsigned NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for a social networking account.',
  `SOCIAL_NETWORK_ID` int(15) NOT NULL COMMENT 'Reference to the social network on which this account is hosted.',
  `SOCIAL_NETWORK_USER_EXT_ID` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'Unique identifier social network user on the network.',
  `SOCIAL_NETWORK_USER_NAME` varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'The account name of a social network user.',
  `SOCIAL_NETWORK_USER_AVATAR` varchar(2083) collate utf8_unicode_ci NOT NULL COMMENT 'Uri linking to an image representing a user.',
  `USER_ID` int(11) default NULL COMMENT 'Unique identifier of a user.',
  `BLOG_ID` int(11) default NULL COMMENT 'Unique identifier of a blog.',
  `OAUTH_TOKEN` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'OAuth token for the social network API.',
  `OAUTH_SECRET_TOKEN` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'OAuth secret token for the social network API.',
  `OAUTH_REFRESH_TOKEN` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'OAuth refresh token for the social network API.',
  `CRAWLED_DATE_TIME` datetime default NULL COMMENT 'The date and time on which this user was last checked by the aggregator.',
  PRIMARY KEY  (`SOCIAL_NETWORK_USER_ID`),
  UNIQUE KEY `AK_SOCIAL_NETWORK_USER_ID` (`SOCIAL_NETWORK_ID`,`SOCIAL_NETWORK_USER_EXT_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='User of a social network.';

-- --------------------------------------------------------

--
-- Table structure for table `TAG`
--

DROP TABLE IF EXISTS `TAG`;
CREATE TABLE IF NOT EXISTS `TAG` (
  `TAG_ID` int(11) NOT NULL auto_increment COMMENT 'Unique identifier of a tag.',
  `TOPIC_ID` int(11) NOT NULL COMMENT 'Reference to a topic associated with an object.',
  `OBJECT_ID` int(11) NOT NULL COMMENT 'Reference to an object associated with a topic.',
  `OBJECT_TYPE_ID` int(11) NOT NULL COMMENT 'Reference to a type of object.',
  `TOPIC_SOURCE_ID` int(11) NOT NULL COMMENT 'Reference to the source of a topic.',
  `USER_ID` int(11) default NULL COMMENT 'Reference to a user who created a topic.',
  `PRIVATE_STATUS` tinyint(1) NOT NULL COMMENT 'Privacy status of a tag.',
  `CREATION_DATE_TIME` datetime NOT NULL COMMENT 'Date and time an object was linked to a topic.',
  PRIMARY KEY  (`TAG_ID`),
  UNIQUE KEY `UNIQUE_KEY` (`TOPIC_ID`,`OBJECT_ID`,`OBJECT_TYPE_ID`,`TOPIC_SOURCE_ID`,`USER_ID`,`PRIVATE_STATUS`),
  KEY `FK_OBJECTS` (`OBJECT_ID`,`OBJECT_TYPE_ID`),
  KEY `TOPIC_ID` (`TOPIC_ID`),
  KEY `OBJECT_ID` (`OBJECT_ID`,`OBJECT_TYPE_ID`,`TOPIC_SOURCE_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Associations between topics and objects.';

-- --------------------------------------------------------

--
-- Table structure for table `TOPIC`
--

DROP TABLE IF EXISTS `TOPIC`;
CREATE TABLE IF NOT EXISTS `TOPIC` (
  `TOPIC_ID` int(10) unsigned NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for this topic.',
  `TOPIC_NAME` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'The human-readable but language-independent string that carries the entire meaning of this topic.',
  `TOPIC_TOP_LEVEL_INDICATOR` tinyint(1) NOT NULL default '0' COMMENT 'Indicator whether this topic should be presented as a root for interactive topic navigation, independent of whether or not its more general topics are known.',
  PRIMARY KEY  (`TOPIC_ID`),
  UNIQUE KEY `AK_TOPIC_NAME` (`TOPIC_NAME`),
  KEY `topLevelTopics` (`TOPIC_ID`,`TOPIC_NAME`,`TOPIC_TOP_LEVEL_INDICATOR`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A string or tag representing a subject of interest to aggreg';

-- --------------------------------------------------------

--
-- Table structure for table `TOPIC_ADMINISTRATOR_NOTE`
--

DROP TABLE IF EXISTS `TOPIC_ADMINISTRATOR_NOTE`;
CREATE TABLE IF NOT EXISTS `TOPIC_ADMINISTRATOR_NOTE` (
  `TOPIC_ID` int(15) NOT NULL COMMENT 'Reference to a topic annotated by this note.',
  `ADMINISTRATOR_NOTE_ID` int(15) NOT NULL COMMENT 'Reference to a note associated with this topic.',
  PRIMARY KEY  (`TOPIC_ID`,`ADMINISTRATOR_NOTE_ID`),
  KEY `FK_NOTE_TOPICS` (`ADMINISTRATOR_NOTE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Association of an administrator note with a particular topic';

-- --------------------------------------------------------

--
-- Table structure for table `TOPIC_SIMILARITY`
--

DROP TABLE IF EXISTS `TOPIC_SIMILARITY`;
CREATE TABLE IF NOT EXISTS `TOPIC_SIMILARITY` (
  `TOPIC_ID_1` int(15) NOT NULL COMMENT 'Reference to the first half of a similarity assertion.',
  `TOPIC_ID_2` int(15) NOT NULL COMMENT 'Reference to the second half of a similarity assertion.',
  PRIMARY KEY  (`TOPIC_ID_1`,`TOPIC_ID_2`),
  KEY `FK_TOPIC_SIMILARITY_2` (`TOPIC_ID_2`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Assertion of equivalence between topics.';

-- --------------------------------------------------------

--
-- Table structure for table `TOPIC_SOURCE`
--

DROP TABLE IF EXISTS `TOPIC_SOURCE`;
CREATE TABLE IF NOT EXISTS `TOPIC_SOURCE` (
  `TOPIC_SOURCE_ID` int(15) NOT NULL COMMENT 'Numeric ID of a topic source',
  `TOPIC_SOURCE_NAME` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Textual description of a source.',
  PRIMARY KEY  (`TOPIC_SOURCE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The source of a topic, e.g., Post (the post itself) or Scien';

-- --------------------------------------------------------

--
-- Table structure for table `TOPIC_SPECIFICITY`
--

DROP TABLE IF EXISTS `TOPIC_SPECIFICITY`;
CREATE TABLE IF NOT EXISTS `TOPIC_SPECIFICITY` (
  `SPECIFIC_TOPIC_ID` int(15) NOT NULL COMMENT 'Reference to the more specific topic being generalized.',
  `GENERAL_TOPIC_ID` int(15) NOT NULL COMMENT 'Reference to the more general topic being specialized.',
  PRIMARY KEY  (`SPECIFIC_TOPIC_ID`,`GENERAL_TOPIC_ID`),
  KEY `FK_TOPIC_SPECIALIZATION` (`GENERAL_TOPIC_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Assertion of more or less specific subject coverage of topic';

-- --------------------------------------------------------

--
-- Table structure for table `TWEET`
--

DROP TABLE IF EXISTS `TWEET`;
CREATE TABLE IF NOT EXISTS `TWEET` (
  `TWEET_ID` int(11) NOT NULL auto_increment COMMENT 'Unique identifier of a tweet.',
  `TWEET_TWITTER_ID` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'Unique identifier of a tweet on twitter.',
  `TWEET_TEXT` varchar(500) collate utf8_unicode_ci NOT NULL COMMENT 'Tweet content.',
  `SOCIAL_NETWORK_USER_ID` int(10) unsigned NOT NULL COMMENT 'Unique identifier of a user on a social network.',
  `TWEET_DATE_TIME` datetime NOT NULL COMMENT 'Timestamp of a tweet.',
  PRIMARY KEY  (`TWEET_ID`),
  UNIQUE KEY `TWEET_TWITTER_ID` (`TWEET_TWITTER_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Tweets from Twitter.';

-- --------------------------------------------------------

--
-- Table structure for table `USER`
--

DROP TABLE IF EXISTS `USER`;
CREATE TABLE IF NOT EXISTS `USER` (
  `USER_ID` int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for this user account.',
  `USER_PRIVILEGE_ID` int(15) NOT NULL COMMENT 'Reference to the privilege level of this user account.',
  `USER_STATUS_ID` int(15) NOT NULL COMMENT 'Reference to the account status of this user account.',
  `USER_NAME` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'The unique login name associated with the user account.',
  `PASSWORD` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'The encrypted password used to log in with the account.',
  `EMAIL_ADDRESS` varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'E-mail address associated with a user account.',
  `DISPLAY_NAME` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Human-readable name associated with this user.',
  `USER_AVATAR_LOCATOR` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'The system identifier for a graphical image associated with a user.',
  `REGISTRATION_DATE_TIME` datetime NOT NULL COMMENT 'Date and time a user registered.',
  PRIMARY KEY  (`USER_ID`),
  UNIQUE KEY `AK_USER_NAME` (`USER_NAME`),
  KEY `FK_USER_PRIVILEGE_LEVEL` (`USER_PRIVILEGE_ID`),
  KEY `FK_USER_STATUS` (`USER_STATUS_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A user of the aggregation system, equating to a single login';

-- --------------------------------------------------------

--
-- Table structure for table `USER_ADMINISTRATOR_NOTE`
--

DROP TABLE IF EXISTS `USER_ADMINISTRATOR_NOTE`;
CREATE TABLE IF NOT EXISTS `USER_ADMINISTRATOR_NOTE` (
  `USER_ID` int(15) NOT NULL COMMENT 'Reference to a user account annotated by this note.',
  `ADMINISTRATOR_NOTE_ID` int(15) NOT NULL COMMENT 'Reference to a note associated with this user account.',
  PRIMARY KEY  (`ADMINISTRATOR_NOTE_ID`,`USER_ID`),
  KEY `FK_USER_NOTES` (`USER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Association of an administrator note with a particular user ';

-- --------------------------------------------------------

--
-- Table structure for table `USER_PREFERENCE`
--

DROP TABLE IF EXISTS `USER_PREFERENCE`;
CREATE TABLE IF NOT EXISTS `USER_PREFERENCE` (
  `USER_ID` int(10) NOT NULL COMMENT 'Reference to a user with a preference.',
  `USER_URL` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Personal URL of a user.',
  `USER_BIOGRAPHY` text collate utf8_unicode_ci NOT NULL COMMENT 'Personal biography of a user.',
  `EMAIL_EDITOR_PICK` tinyint(1) NOT NULL default '0' COMMENT 'Allow emails for editor''s picks.',
  `EMAIL_ANNOUNCEMENTS` tinyint(1) NOT NULL default '0' COMMENT 'Allow emails for announcements.',
  `EMAIL_FOLLOWS` tinyint(1) NOT NULL COMMENT 'Allow emails for new follows.',
  `USER_LOCATION` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Location of a user.',
  `USER_BANNER` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'Name of a banner associated with a user.',
  PRIMARY KEY  (`USER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='User preferences for their accounts.';

-- --------------------------------------------------------

--
-- Table structure for table `USER_PRIVILEGE`
--

DROP TABLE IF EXISTS `USER_PRIVILEGE`;
CREATE TABLE IF NOT EXISTS `USER_PRIVILEGE` (
  `USER_PRIVILEGE_ID` int(15) NOT NULL COMMENT 'Machine-generated unique identifier for a particular user privilege level.',
  `USER_PRIVILEGE_DESCRIPTION` varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'Human-readable description of a user privilege level.',
  PRIMARY KEY  (`USER_PRIVILEGE_ID`),
  UNIQUE KEY `AK_USER_PRIVILEGE_DESCRIPTION` (`USER_PRIVILEGE_DESCRIPTION`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Privilege status for a user of the aggregation system, e.g. ';

-- --------------------------------------------------------

--
-- Table structure for table `USER_RECOMMENDATION`
--

DROP TABLE IF EXISTS `USER_RECOMMENDATION`;
CREATE TABLE IF NOT EXISTS `USER_RECOMMENDATION` (
  `USER_ID` int(15) NOT NULL default '0' COMMENT 'Reference to an user associated with this recommendation.',
  `AUTHOR_USER_ID` int(15) NOT NULL COMMENT 'Reference to the user who made the recommendation.',
  `REC_DATE_TIME` datetime NOT NULL COMMENT 'The date and time at which this recommendation was made.',
  `REC_COMMENT` text collate utf8_unicode_ci NOT NULL COMMENT 'Comment from the persona, associated with this recommendation.',
  PRIMARY KEY  (`USER_ID`,`AUTHOR_USER_ID`),
  KEY `FK_RECOMMENDATIONS` (`AUTHOR_USER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Recommendations of particular posts.';

-- --------------------------------------------------------

--
-- Table structure for table `USER_SECRET_CODE`
--

DROP TABLE IF EXISTS `USER_SECRET_CODE`;
CREATE TABLE IF NOT EXISTS `USER_SECRET_CODE` (
  `USER_ID` int(10) unsigned NOT NULL COMMENT 'Reference to a user that solicited a recovery code.',
  `USER_SECRET_CODE_TEXT` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'Unique secret code associated with a user',
  `USER_SECRET_CODE_DATE_TIME` datetime NOT NULL COMMENT 'The date and time at which this code was made.',
  `USER_SECRET_CODE_SOURCE_ID` tinyint(4) NOT NULL COMMENT 'Reference to the source of this code.',
  PRIMARY KEY  (`USER_ID`,`USER_SECRET_CODE_TEXT`,`USER_SECRET_CODE_SOURCE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Secret codes associated with users.';

-- --------------------------------------------------------

--
-- Table structure for table `USER_SECRET_CODE_SOURCE`
--

DROP TABLE IF EXISTS `USER_SECRET_CODE_SOURCE`;
CREATE TABLE IF NOT EXISTS `USER_SECRET_CODE_SOURCE` (
  `USER_SECRET_CODE_SOURCE_ID` int(15) NOT NULL auto_increment COMMENT 'Unique identifier of a secret code source.',
  `USER_SECRET_CODE_SOURCE_NAME` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Name of a secret code source.',
  PRIMARY KEY  (`USER_SECRET_CODE_SOURCE_ID`),
  UNIQUE KEY `AK_SECRET_CODE_SOURCE_NAME` (`USER_SECRET_CODE_SOURCE_NAME`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Sources for secret codes.';

-- --------------------------------------------------------

--
-- Table structure for table `USER_STATUS`
--

DROP TABLE IF EXISTS `USER_STATUS`;
CREATE TABLE IF NOT EXISTS `USER_STATUS` (
  `USER_STATUS_ID` int(15) NOT NULL COMMENT 'Machine-generated unique identifier for a user status.',
  `USER_STATUS_DESCRIPTION` varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'Human-readable description of a user status level.',
  PRIMARY KEY  (`USER_STATUS_ID`),
  UNIQUE KEY `AK_USER_STATUS_DESCRIPTION` (`USER_STATUS_DESCRIPTION`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The approval status of a user account, e.g., active, under r';

-- --------------------------------------------------------

--
-- Table structure for table `SETTING`
--

DROP TABLE IF EXISTS `SETTING`;
CREATE TABLE IF NOT EXISTS `SETTING` (
  `SETTING_ID` int(10) unsigned NOT NULL auto_increment COMMENT 'Unique identifier of a setting',
  `SETTING_NAME` varchar(155) collate utf8_unicode_ci NOT NULL COMMENT 'Name of a setting.',
  `SETTING_VALUE` text collate utf8_unicode_ci NOT NULL COMMENT 'Value of a setting.',
  PRIMARY KEY  (`SETTING_ID`),
  UNIQUE KEY `SETTING_NAME` (`SETTING_NAME`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Settings of the installation.';

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
