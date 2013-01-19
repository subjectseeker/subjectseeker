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

CREATE INDEX `BLOG_POST_TITLE` ON `BLOG_POST` (`BLOG_POST_TITLE`(333));
CREATE FULLTEXT INDEX `BLOG_POST_SUMMARY` ON `BLOG_POST` (`BLOG_POST_SUMMARY`);

ALTER TABLE `FOLLOWER` CHANGE FOLLOW_TYPE_ID OBJECT_TYPE_ID int(10) NOT NULL COMMENT 'Unique identifier of an object type.';

ALTER TABLE `FOLLOWER` CHANGE FOLLOWED_ID OBJECT_ID int(10) unsigned NOT NULL COMMENT 'Unique identifier of a followed object.';

ALTER TABLE USER_PREFERENCE ADD `USER_BANNER` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'Name of a banner associated with a user.';

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