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

--
-- Table structure for table `ARTICLE`
--

CREATE TABLE IF NOT EXISTS `ARTICLE` (
  `ARTICLE_ID` int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for an article.',
  `ARTICLE_TITLE` varchar(900) collate utf8_unicode_ci default NULL COMMENT 'Title of an article.',
  `ARTICLE_JOURNAL_TITLE` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'Name of a journal where the article has been published.',
  `ARTICLE_JOURNAL_ISSUE` varchar(100) collate utf8_unicode_ci default NULL COMMENT 'Issue of the journal where an article was published.',
  `ARTICLE_JOURNAL_VOLUME` varchar(100) collate utf8_unicode_ci default NULL COMMENT 'Volume of the journal where an article was published.',
  `ARTICLE_ISSN` varchar(30) collate utf8_unicode_ci default NULL COMMENT 'ISSN code associated with an article.',
  `ARTICLE_NUMBER` varchar(2083) collate utf8_unicode_ci default NULL COMMENT 'Identifier associated with an article in the journal.',
  `ARTICLE_PUBLICATION_DATE` varchar(255) default NULL COMMENT 'Year of publication of an article.',
  `ARTICLE_START_PAGE` varchar(30) default NULL COMMENT 'Start page of an article in the journal.',
  `ARTICLE_END_PAGE` varchar(30) default NULL COMMENT 'End page of an article in the journal.',
  `ARTICLE_FROM_ORIGINAL_SOURCE` tinyint(1) NOT NULL default '0' COMMENT 'Indicator whether this article data is based on the original source.',
  PRIMARY KEY  (`ARTICLE_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Peer-reviewed articles.';

-- --------------------------------------------------------

--
-- Table structure for table `ARTICLE_AUTHOR`
--

CREATE TABLE IF NOT EXISTS `ARTICLE_AUTHOR` (
  `ARTICLE_AUTHOR_ID` int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for this article author.',
  `ARTICLE_AUTHOR_FIRST_NAME` varchar(125) collate utf8_unicode_ci default NULL COMMENT 'First name of the article author.',
  `ARTICLE_AUTHOR_LAST_NAME` varchar(125) collate utf8_unicode_ci default NULL COMMENT 'Last name of the article''s author.',
  `ARTICLE_AUTHOR_FULL_NAME` varchar(250) collate utf8_unicode_ci default NULL COMMENT 'Full name of the article author',
  PRIMARY KEY  (`ARTICLE_AUTHOR_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Authors of peer-reviewed articles.';

-- --------------------------------------------------------

--
-- Table structure for table `ARTICLE_IDENTIFIER`
--

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
-- Table structure for table `AUTHOR_ARTICLE`
--

CREATE TABLE IF NOT EXISTS `AUTHOR_ARTICLE` (
  `ARTICLE_ID` int(15) NOT NULL COMMENT 'Reference to an article created by an author.',
  `ARTICLE_AUTHOR_ID` int(15) NOT NULL COMMENT 'Reference to an author of an article.',
  UNIQUE KEY `FK_AUTHOR_TO_ARTICLE` (`ARTICLE_ID`,`ARTICLE_AUTHOR_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Articles containing these authors.';

-- --------------------------------------------------------

--
-- Table structure for table `CITATION`
--

ALTER TABLE `CITATION` ADD `ARTICLE_ID` int(15) NOT NULL COMMENT 'Reference to an article with this citation.';

-- --------------------------------------------------------

--
-- Table structure for table `POST_CITATION`
--

ALTER TABLE `POST_CITATION` ADD CONSTRAINT `FK_CITATION_TO_POST` UNIQUE (`CITATION_ID`,`BLOG_POST_ID`);
