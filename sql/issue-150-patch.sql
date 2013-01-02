/*==============================================================*/
/* This file is not intended to be run as part of SubjectSeeker */
/* installation.  It is only to be run on databases that        */
/* before Issue 94 was addressed, as a patch to their schema.   */
/*==============================================================*/

/*==============================================================*/
/* Copyright © 2012 Liminality.                                 */
/*                                                              */
/* Permission is hereby granted, free of charge, to any person  */
/* obtaining a copy of this software and associated             */
/* documentation files (the “Software”), to deal in the         */
/* Software without restriction, including without limitation   */
/* the rights to use, copy, modify, merge, publish, distribute, */
/* sublicense, and/or sell copies of the Software, and to       */
/* permit persons to whom the Software is furnished to do so,   */
/* subject to the following conditions:                         */
/*                                                              */
/* The above copyright notice and this permission notice shall  */
/* be included in all copies or substantial portions of the     */
/* Software.                                                    */
/*                                                              */
/* THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY    */
/* KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE   */
/* WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR      */
/* PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS   */
/* OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR     */
/* OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR   */
/* OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE    */
/* SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.       */
/*==============================================================*/

ALTER TABLE `USER` ADD `REGISTRATION_DATE_TIME` datetime NOT NULL COMMENT 'Date and time a user registered.';

ALTER TABLE `USER_PREFERENCE` ADD `USER_LOCATION` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Location of a user.';

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

DROP TABLE IF EXISTS `FOLLOWER`;
CREATE TABLE IF NOT EXISTS `FOLLOWER` (
  `USER_ID` int(10) unsigned NOT NULL COMMENT 'Unique identifier of a follower user.',
  `FOLLOW_TYPE_ID` int(11) NOT NULL COMMENT 'Unique identifier of a type.',
  `FOLLOWED_ID` int(10) unsigned NOT NULL COMMENT 'Unique identifier of a followed user.',
  UNIQUE KEY `FOLLOWER_TO_FOLLOWEE` (`USER_ID`,`FOLLOWED_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='User IDs of followers a followees.';


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


DROP TABLE IF EXISTS `OBJECT_TYPE`;
CREATE TABLE IF NOT EXISTS `OBJECT_TYPE` (
  `OBJECT_TYPE_ID` int(15) NOT NULL COMMENT 'Unique identifier of type of object.',
  `OBJECT_TYPE_NAME` varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'Name of a type of object.',
  PRIMARY KEY  (`OBJECT_TYPE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Types of objects in the database.';

INSERT INTO SOCIAL_NETWORK (SOCIAL_NETWORK_ID, SOCIAL_NETWORK_NAME) VALUES (1, 'Twitter');
INSERT INTO SOCIAL_NETWORK (SOCIAL_NETWORK_ID, SOCIAL_NETWORK_NAME) VALUES (2, 'Facebook');
INSERT INTO SOCIAL_NETWORK (SOCIAL_NETWORK_ID, SOCIAL_NETWORK_NAME) VALUES (3, 'Google+');

INSERT INTO OBJECT_TYPE (OBJECT_TYPE_ID, OBJECT_TYPE_NAME) VALUES (1, 'post');
INSERT INTO OBJECT_TYPE (OBJECT_TYPE_ID, OBJECT_TYPE_NAME) VALUES (2, 'user');
INSERT INTO OBJECT_TYPE (OBJECT_TYPE_ID, OBJECT_TYPE_NAME) VALUES (3, 'site');