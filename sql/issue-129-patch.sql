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

ALTER TABLE `BLOG_AUTHOR` ADD `USER_ID` int(15) NULL COMMENT 'Reference to an user associated with this author.' AFTER `PERSONA_ID`;

ALTER TABLE `RECOMMENDATION` ADD `USER_ID` int(15) NULL COMMENT 'Reference to an user associated with this recommendation.' AFTER `PERSONA_ID`;

ALTER TABLE `USER` ADD `DISPLAY_NAME` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Human-readable name associated with this user.';

ALTER TABLE `USER` ADD `BIOGRAPHY` text collate utf8_unicode_ci NULL COMMENT 'Arbitrarily long free text describing a user.';

ALTER TABLE `USER` ADD `USER_AVATAR_LOCATOR` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'The system identifier for a graphical image associated with a user.';

CREATE TABLE IF NOT EXISTS `BLOG_SOCIAL_ACCOUNT` (
  `SOCIAL_NETWORKING_ACCOUNT_ID` int(10) unsigned NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for a social networking account.',
  `SOCIAL_NETWORK_ID` int(15) NOT NULL COMMENT 'Reference to the social network on which this account is hosted.',
  `SOCIAL_NETWORKING_ACCOUNT_NAME` varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'The account identifier a persona uses on a particular social network.',
  `BLOG_ID` int(10) unsigned default NULL COMMENT 'The ID of a blog associated with this account.',
  `OAUTH_TOKEN` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'OAuth token for the social network API.',
  `OAUTH_SECRET_TOKEN` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'OAuth secret token for the social network API.',
  PRIMARY KEY  (`SOCIAL_NETWORKING_ACCOUNT_ID`),
  UNIQUE KEY `AK_SOCIAL_NETWORK_USER_LOCATOR` (`SOCIAL_NETWORK_ID`,`SOCIAL_NETWORKING_ACCOUNT_NAME`),
  UNIQUE KEY `FK_BLOG_ID` (`BLOG_ID`, `SOCIAL_NETWORK_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Social account to be associated with a blog';

CREATE TABLE IF NOT EXISTS `USER_SOCIAL_ACCOUNT` (
  `SOCIAL_NETWORKING_ACCOUNT_ID` int(10) unsigned NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for a social networking account.',
  `SOCIAL_NETWORK_ID` int(15) NOT NULL COMMENT 'Reference to the social network on which this account is hosted.',
  `SOCIAL_NETWORKING_ACCOUNT_NAME` varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'The account identifier a persona uses on a particular social network.',
  `USER_ID` int(10) unsigned default NULL COMMENT 'The ID of a user associated with this account.',
  `OAUTH_TOKEN` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'OAuth token for the social network API.',
  `OAUTH_SECRET_TOKEN` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'OAuth secret token for the social network API.',
  PRIMARY KEY  (`SOCIAL_NETWORKING_ACCOUNT_ID`),
  UNIQUE KEY `AK_SOCIAL_NETWORK_USER_LOCATOR` (`SOCIAL_NETWORK_ID`,`SOCIAL_NETWORKING_ACCOUNT_NAME`),
  UNIQUE KEY `FK_USER_ID` (`USER_ID`, `SOCIAL_NETWORK_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Social account to be associated with a user';