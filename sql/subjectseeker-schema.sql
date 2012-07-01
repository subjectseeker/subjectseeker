/*==============================================================*/
/* DBMS name:      MySQL 5.0                                    */
/* Created on:     12/20/2010 4:25:27 PM                        */
/*==============================================================*/

/*==============================================================*/
/* Copyright © 2010–2011 Christopher R. Maden, Jessica Perry    */
/* Hekman, Liminality.                                          */
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

/* Last patch file incorporated: issue-130-patch.sql. */

drop table if exists ADMINISTRATOR_NOTE;

drop table if exists BLOG;

drop table if exists BLOG_ADMINISTRATOR_NOTE;

drop table if exists BLOG_AUTHOR;

drop table if exists BLOG_GROUP;

drop table if exists BLOG_GROUPING;

drop table if exists BLOG_GROUP_ADMINISTRATOR_NOTE;

drop table if exists BLOG_LANGUAGE;

drop table if exists BLOG_POST;

drop table if exists BLOG_POST_ADMINISTRATOR_NOTE;

drop table if exists BLOG_POST_STATUS;

drop table if exists BLOG_STATUS;

drop table if exists CITATION;

drop table if exists LANGUAGE;

drop table if exists POST_TOPIC;

drop table if exists POST_CITATION;

drop table if exists PRIMARY_BLOG_TOPIC;

drop table if exists SECONDARY_BLOG_TOPIC;

drop table if exists SOCIAL_NETWORK;

drop table if exists SOCIAL_NETWORKING_ACCOUNT;

drop table if exists TOPIC;

drop table if exists TOPIC_ADMINISTRATOR_NOTE;

drop table if exists TOPIC_SIMILARITY;

drop table if exists TOPIC_SPECIFICITY;

drop table if exists USER;

drop table if exists USER_ADMINISTRATOR_NOTE;

drop table if exists USER_PRIVILEGE;

drop table if exists USER_STATUS;

drop table if exists CLAIM_BLOG;

drop table if exists CLAIM_BLOG_STATUS;

drop table if exists TOPIC_SOURCE;

drop table if exists RECOMMENDATION;

drop table if exists ARTICLE;

drop table if exists ARTICLE_AUTHOR;

drop table if exists ARTICLE_IDENTIFIER;

drop table if exists ARTICLE_AUTHOR_LINK;

drop table if exists SCAN_POST;

drop table if exists BLOG_SOCIAL_ACCOUNT;

drop table if exists USER_SOCIAL_ACCOUNT;


/*==============================================================*/
/* Table: ADMINISTRATOR_NOTE                                    */
/*==============================================================*/
create table ADMINISTRATOR_NOTE
(
   ADMINISTRATOR_NOTE_ID int(15) not null auto_increment comment 'Machine-generated unique identifier for a note.',
   ADMINISTRATOR_NOTE_CONTENT text not null comment 'The free-text content of a note.',
   primary key (ADMINISTRATOR_NOTE_ID)
) comment 'A system internal note associated with a particular system o';

/*==============================================================*/
/* Table: BLOG                                                  */
/*==============================================================*/
create table BLOG
(
   BLOG_ID              INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Machine-generated unique identifier for this blog.',
   BLOG_STATUS_ID       TINYINT UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Reference to the moderation status associated with this blog.',
   BLOG_NAME            VARCHAR(255) NOT NULL COMMENT 'Human-readable display name of this blog.',
   BLOG_URI             TEXT NOT NULL COMMENT 'The URI intended for visiting this blog in normal usage.',
   BLOG_SYNDICATION_URI TEXT NOT NULL COMMENT 'The URI via which a syndication feed for this blog can be accessed.',
   BLOG_DESCRIPTION     TEXT COMMENT 'The free-text human-readable description of the nature and intent of the blog.',
   ADDED_DATE_TIME      DATETIME NOT NULL COMMENT 'The date and time when this blog was added to the aggregator.',
   CRAWLED_DATE_TIME    DATETIME COMMENT 'The date and time on which this blog was last checked by the aggregator.',
   PRIMARY KEY (BLOG_ID)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT 'A Web log or feed therefrom intended for aggregation by the system';

/*==============================================================*/
/* Table: BLOG_ADMINISTRATOR_NOTE                               */
/*==============================================================*/
create table BLOG_ADMINISTRATOR_NOTE
(
   ADMINISTRATOR_NOTE_ID int(15) not null comment 'Reference to a note associated with this blog.',
   BLOG_ID              int(15) not null comment 'Reference to a blog annotated by this note.',
   primary key (ADMINISTRATOR_NOTE_ID, BLOG_ID)
) comment 'Association of an administrator note with a particular blog.';

/*==============================================================*/
/* Table: BLOG_AUTHOR                                           */
/*==============================================================*/
create table BLOG_AUTHOR
(
   BLOG_AUTHOR_ID       int(15) not null auto_increment comment 'Machine-generated unique identifier for this blog author.',
   BLOG_ID              int(15) not null comment 'Reference to the blog with which this author is associated.',
   USER_ID              int(15) NULL COMMENT 'Reference to an user associated with this author.',
  BLOG_AUTHOR_ACCOUNT_NAME varchar(255) not null comment 'The identifier used for this author on the blog to which they contribute: the user name or display name, as appropriate for the blog in question.',
   primary key (BLOG_AUTHOR_ID),
   unique key AK_BLOG_AUTHOR_ACCOUNT (BLOG_ID, BLOG_AUTHOR_ACCOUNT_NAME)
) comment 'A known contributor to a blog of interest, who may or may no';

/*==============================================================*/
/* Table: BLOG_GROUP                                            */
/*==============================================================*/
create table BLOG_GROUP
(
   BLOG_GROUP_ID        int(15) not null auto_increment comment 'Machine-generated unique identifier for this group.',
   BLOG_GROUP_NAME      varchar(255) not null comment 'Human-readable display name of this group.',
   BLOG_GROUP_URI       varchar(2083) not null comment 'The URI intended for visiting this blog group in normal usage.',
   BLOG_GROUP_SYNDICATION_URI varchar(2083) comment 'The URI via which a syndication feed for this blog group can be accessed.',
   BLOG_GROUP_DESCRIPTION text comment 'The free-text human-readable description of the nature and intent of the blog group.',
   primary key (BLOG_GROUP_ID)
) comment 'A collection of blogs characterized by network affiliation, ';

/*==============================================================*/
/* Table: BLOG_GROUPING                                         */
/*==============================================================*/
create table BLOG_GROUPING
(
   BLOG_GROUP_ID        int(15) not null comment 'Reference to the group into which the blog is being grouped.',
   BLOG_ID              int(15) not null comment 'Reference to the blog being grouped.',
   primary key (BLOG_GROUP_ID, BLOG_ID)
) comment 'Association of blogs into groups.';

/*==============================================================*/
/* Table: BLOG_GROUP_ADMINISTRATOR_NOTE                         */
/*==============================================================*/
create table BLOG_GROUP_ADMINISTRATOR_NOTE
(
   ADMINISTRATOR_NOTE_ID int(15) not null comment 'Reference to a note associated with this blog group.',
   BLOG_GROUP_ID        int(15) not null comment 'Reference to a blog group annotated by this note.',
   primary key (ADMINISTRATOR_NOTE_ID, BLOG_GROUP_ID)
) comment 'Association of an administrator note with a particular blog ';

/*==============================================================*/
/* Table: BLOG_LANGUAGE                                         */
/*==============================================================*/
create table BLOG_LANGUAGE
(
   LANGUAGE_ID          int(15) not null comment 'Reference to the language in which a blog is written.',
   BLOG_ID              int(15) not null comment 'Reference to a blog written in this language.',
   primary key (LANGUAGE_ID, BLOG_ID)
) comment 'Languages used by blogs of interest.';

/*==============================================================*/
/* Table: BLOG_POST                                             */
/*==============================================================*/
create table BLOG_POST
(
   BLOG_POST_ID         int(15) not null auto_increment comment 'Machine-generated unique identifier for this blog post.',
   BLOG_ID              int(15) not null comment 'Reference to the blog on which this post was made.',
   BLOG_AUTHOR_ID       int(15) not null comment 'Reference to the author credited with this blog post.',
   LANGUAGE_ID          int(15) comment 'Reference to the language or locale in which this post is published.',
   BLOG_POST_STATUS_ID  int(15) not null comment 'Reference to the moderation status of this blog.',
   BLOG_POST_URI        varchar(2083) not null comment 'The URI at which this blog post can be read in normal usage',
   BLOG_POST_DATE_TIME  datetime not null comment 'The date and time at which this post was published to its home blog.',
   BLOG_POST_INGEST_DATE_TIME datetime not null comment 'The date and time at which this post was collected by the aggregation system.',
   BLOG_POST_SUMMARY    text not null comment 'A free text summary of the blog post, typically created by truncating the start of the post.',
   BLOG_POST_HAS_CITATION bool not null default FALSE comment 'Indicator whether this blog post has a research citation in its content.',
   BLOG_POST_TITLE      varchar(1023) comment 'The original title of the blog post.',
   primary key (BLOG_POST_ID)
) comment 'A post to a blog of interest to the system.';

/*==============================================================*/
/* Table: BLOG_POST_ADMINISTRATOR_NOTE                          */
/*==============================================================*/
create table BLOG_POST_ADMINISTRATOR_NOTE
(
   ADMINISTRATOR_NOTE_ID int(15) not null comment 'Reference to a note associated with this blog post.',
   BLOG_POST_ID         int(15) not null comment 'Reference to a blog post annotated by this note.',
   primary key (ADMINISTRATOR_NOTE_ID, BLOG_POST_ID)
) comment 'Association of an administrator note with a particular blog ';

/*==============================================================*/
/* Table: BLOG_POST_STATUS                                      */
/*==============================================================*/
create table BLOG_POST_STATUS
(
   BLOG_POST_STATUS_ID  int(15) not null comment 'Machine-readable unique identifier for a blog post status level.',
   BLOG_POST_STATUS_DESCRIPTION varchar(127) not null comment 'Human-readable description of a blog post status level.',
   primary key (BLOG_POST_STATUS_ID),
   unique key AK_BLOG_POST_STATUS_DESCRIPTION (BLOG_POST_STATUS_DESCRIPTION)
) comment 'Approval status of a blog post, e.g., active, under review.';

/*==============================================================*/
/* Table: BLOG_STATUS                                           */
/*==============================================================*/
create table BLOG_STATUS
(
   BLOG_STATUS_ID       int(15) not null comment 'Machine-generated unique identifier for a blog status level.',
   BLOG_STATUS_DESCRIPTION varchar(127) not null comment 'Human-readable description of a blog status level.',
   primary key (BLOG_STATUS_ID),
   unique key AK_BLOG_STATUS_DESCRIPTION (BLOG_STATUS_DESCRIPTION)
) comment 'The approval status of a blog, e.g., submitted, under review';

/*==============================================================*/
/* Table: CITATION                                              */
/*==============================================================*/
create table CITATION
(
   CITATION_ID          int(15) not null auto_increment comment 'A machine-generated unique identifier for a citation.',
   CITATION_TEXT        text not null comment 'The HTML text of this citation',
   primary key (CITATION_ID),
   ARTICLE_ID            int(15) NOT NULL COMMENT 'Reference to an article with this citation.'
) comment 'Information about a citation of peer-reviewed content';

/*==============================================================*/
/* Table: LANGUAGE                                              */
/*==============================================================*/
create table LANGUAGE
(

   LANGUAGE_ID          int(15) not null auto_increment comment 'A machine-generated unique identifier for a human language.',
   LANGUAGE_IETF_CODE   varchar(31) not null comment 'IETF BCP 47 code for a language or locale, including language, region, and script information, e.g. en, en-US, en-latn-US.',
   LANGUAGE_ENGLISH_NAME varchar(255) not null comment 'Human-readable English name of an IETF locale or language.',
   LANGUAGE_LOCAL_NAME  varchar(255) comment 'Human-readable name of an IETF locale or language, in the language and script so described.',
   primary key (LANGUAGE_ID),
   unique key AK_LANGUAGE_IETF_CODE (LANGUAGE_IETF_CODE)
) comment 'A human language (actually a locale), as defined in IETF BCP';

/*==============================================================*/
/* Table: POST_TOPIC                                            */
/*==============================================================*/
create table POST_TOPIC
(
   TOPIC_ID             int(15) not null comment 'Reference to a topic covered by a post.',
   BLOG_POST_ID         int(15) not null comment 'Reference to a post covering a topic.',
   TOPIC_SOURCE		int(15) not null comment 'Reference to the source of this topic',
   primary key (TOPIC_ID, BLOG_POST_ID)
) comment 'Subjects of a particular post on a blog of interest.';

/*==============================================================*/
/* Table: POST_CITATION                                         */
/*==============================================================*/
create table POST_CITATION
(
   CITATION_ID             int(15) not null comment 'Reference to a citation included in a post.',
   BLOG_POST_ID         int(15) not null comment 'Reference to a post including a citation.',
   primary key (CITATION_ID, BLOG_POST_ID)
) comment 'Blog posts containing citations.';

/*==============================================================*/
/* Table: PRIMARY_BLOG_TOPIC                                    */
/*==============================================================*/
create table PRIMARY_BLOG_TOPIC
(
   TOPIC_ID             int(15) not null comment 'Reference to a topic primarily covered by a blog.',
   BLOG_ID              int(15) not null comment 'Reference to a blog primarily covering a topic.',
   primary key (TOPIC_ID, BLOG_ID)
) comment 'The main subjects of a blog of interest.';

/*==============================================================*/
/* Table: SECONDARY_BLOG_TOPIC                                  */
/*==============================================================*/
create table SECONDARY_BLOG_TOPIC
(
   TOPIC_ID             int(15) not null comment 'Reference to a topic secondarily covered by a blog.',
   BLOG_ID              int(15) not null comment 'Reference to a blog secondarily covering a topic.',
   primary key (TOPIC_ID, BLOG_ID)
) comment 'Subjects of a blog of interest, other than the primary topic';

/*==============================================================*/
/* Table: SOCIAL_NETWORK                                        */
/*==============================================================*/
create table SOCIAL_NETWORK
(
   SOCIAL_NETWORK_ID    int(15) not null auto_increment comment 'Machine-generated unique identifier for a social network.',
   SOCIAL_NETWORK_NAME  varchar(127) not null comment 'Human-readable name of a social network.',
   primary key (SOCIAL_NETWORK_ID),
   unique key AK_SOCIAL_NETWORK_NAME (SOCIAL_NETWORK_NAME)
) comment 'A social network system with well-defined unique user IDs';

/*==============================================================*/
/* Table: TOPIC                                                 */
/*==============================================================*/
create table TOPIC
(
   TOPIC_ID             INTEGER UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Machine-generated unique identifier for this topic.',
   TOPIC_NAME           VARCHAR(255) NOT NULL COMMENT 'The human-readable but language-independent string that carries the entire meaning of this topic.',
   TOPIC_TOP_LEVEL_INDICATOR BOOL NOT NULL DEFAULT '0' comment 'Indicator whether this topic should be presented as a root for interactive topic navigation, independent of whether or not its more general topics are known.',
   PRIMARY KEY (TOPIC_ID),
   UNIQUE KEY AK_TOPIC_NAME (TOPIC_NAME)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT 'A string or tag representing a subject of interest to aggregate';

/*==============================================================*/
/* Table: TOPIC_ADMINISTRATOR_NOTE                              */
/*==============================================================*/
create table TOPIC_ADMINISTRATOR_NOTE
(
   TOPIC_ID             int(15) not null comment 'Reference to a topic annotated by this note.',
   ADMINISTRATOR_NOTE_ID int(15) not null comment 'Reference to a note associated with this topic.',
   primary key (TOPIC_ID, ADMINISTRATOR_NOTE_ID)
) comment 'Association of an administrator note with a particular topic';

/*==============================================================*/
/* Table: TOPIC_SIMILARITY                                      */
/*==============================================================*/
create table TOPIC_SIMILARITY
(
   TOPIC_ID_1           int(15) not null comment 'Reference to the first half of a similarity assertion.',
   TOPIC_ID_2           int(15) not null comment 'Reference to the second half of a similarity assertion.',
   primary key (TOPIC_ID_1, TOPIC_ID_2)
) comment 'Assertion of equivalence between topics.';

/*==============================================================*/
/* Table: TOPIC_SPECIFICITY                                     */
/*==============================================================*/
create table TOPIC_SPECIFICITY
(
   SPECIFIC_TOPIC_ID    int(15) not null comment 'Reference to the more specific topic being generalized.',
   GENERAL_TOPIC_ID     int(15) not null comment 'Reference to the more general topic being specialized.',
   primary key (SPECIFIC_TOPIC_ID, GENERAL_TOPIC_ID)
) comment 'Assertion of more or less specific subject coverage of topic';

/*==============================================================*/
/* Table: USER                                                  */
/*==============================================================*/
create table USER
(
   USER_ID              int(15) not null auto_increment comment 'Machine-generated unique identifier for this user account.',
   USER_PRIVILEGE_ID    int(15) not null comment 'Reference to the privilege level of this user account.',
   USER_STATUS_ID       int(15) not null comment 'Reference to the account status of this user account.',
   USER_NAME            varchar(255) not null comment 'The unique login name associated with the user account.',
   PASSWORD             varchar(255) not null comment 'The encrypted password used to log in with the account.',
   EMAIL_ADDRESS        varchar(127) not null comment 'E-mail address associated with a user account.',
   DISPLAY_NAME         varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Human-readable name associated with this user.',
   BIOGRAPHY            text collate utf8_unicode_ci NULL COMMENT 'Arbitrarily long free text describing a user.',
   USER_AVATAR_LOCATOR  varchar(255) collate utf8_unicode_ci default NULL COMMENT 'The system identifier for a graphical image associated with a user.',
   primary key (USER_ID),
   unique key AK_USER_NAME (USER_NAME)
) comment 'A user of the aggregation system, equating to a single login';

/*==============================================================*/
/* Table: USER_ADMINISTRATOR_NOTE                               */
/*==============================================================*/
create table USER_ADMINISTRATOR_NOTE
(
   USER_ID              int(15) not null comment 'Reference to a user account annotated by this note.',
   ADMINISTRATOR_NOTE_ID int(15) not null comment 'Reference to a note associated with this user account.',
   primary key (ADMINISTRATOR_NOTE_ID, USER_ID)
) comment 'Association of an administrator note with a particular user ';

/*==============================================================*/
/* Table: USER_PRIVILEGE                                        */
/*==============================================================*/
create table USER_PRIVILEGE
(
   USER_PRIVILEGE_ID    int(15) not null comment 'Machine-generated unique identifier for a particular user privilege level.',
   USER_PRIVILEGE_DESCRIPTION varchar(127) not null comment 'Human-readable description of a user privilege level.',
   primary key (USER_PRIVILEGE_ID),
   unique key AK_USER_PRIVILEGE_DESCRIPTION (USER_PRIVILEGE_DESCRIPTION)
) comment 'Privilege status for a user of the aggregation system, e.g. ';

/*==============================================================*/
/* Table: USER_STATUS                                           */
/*==============================================================*/
create table USER_STATUS
(
   USER_STATUS_ID       int(15) not null comment 'Machine-generated unique identifier for a user status.',
   USER_STATUS_DESCRIPTION varchar(127) not null comment 'Human-readable description of a user status level.',
   primary key (USER_STATUS_ID),
   unique key AK_USER_STATUS_DESCRIPTION (USER_STATUS_DESCRIPTION)
) comment 'The approval status of a user account, e.g., active';


create table CLAIM_BLOG
(
   CLAIM_ID              int(15) not null auto_increment comment 'Machine-generated unique identifier for this claim.',
   BLOG_ID              int(15) not null comment 'Reference to a blog which is pending being claimed.',
   USER_ID              int(15) not null comment 'Reference to a user who wishes to claim a blog.',
   CLAIM_TOKEN          varchar(64) not null comment 'Token for use in claiming a blog.',
   CLAIM_STATUS_ID      int(15) not null comment 'ID of status of this claim',
   CLAIM_DATE_TIME datetime not null comment 'The date and time at which this claim request was made.',
   primary key (CLAIM_ID)
) comment 'Information necessary to allow user to claim a blog using a token inserted into feed';

create table CLAIM_BLOG_STATUS
(
   CLAIM_BLOG_STATUS_ID              int(15) not null comment 'Numeric ID of a status.',
   CLAIM_BLOG_STATUS_DESCRIPTION     int(15) not null comment 'Textual description of a status.',
   primary key (CLAIM_BLOG_STATUS_ID)
);

create table TOPIC_SOURCE
(
   TOPIC_SOURCE_ID              int(15) not null comment 'Numeric ID of a topic source',
   TOPIC_SOURCE_NAME            varchar(255) not null comment 'Textual description of a source.',
   primary key (TOPIC_SOURCE_ID)
) comment 'The source of a topic, e.g., Post (the post itself) or ScienceSeeker (specified on ScienceSeeker site)';

/*==============================================================*/
/* Table: RECOMMENDATION                                        */
/*==============================================================*/
create table RECOMMENDATION
(
   USER_ID              int(15) not null comment 'Reference to the user who recommended this post.',
   BLOG_POST_ID         int(15) not null comment 'Reference to a post recommended by this user.',
   REC_DATE_TIME datetime not null comment 'The date and time at which this recommendation was made.',
   REC_COMMENT text not null comment 'Comment from the user, associated with this recommendation.',
   REC_IMAGE varchar(255) comment 'The system identifier for a graphical image associated with a recommendation.',
   primary key (USER_ID, BLOG_POST_ID)
) COMMENT 'Recommendations of particular posts.';


/*==============================================================*/
/* Table: ARTICLE                                               */
/*==============================================================*/

CREATE TABLE IF NOT EXISTS ARTICLE (
  ARTICLE_ID int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for an article.',
  ARTICLE_TITLE varchar(900) collate utf8_unicode_ci default NULL COMMENT 'Title of an article.',
  ARTICLE_JOURNAL_TITLE varchar(255) collate utf8_unicode_ci default NULL COMMENT 'Name of a journal where the article has been published.',
  ARTICLE_JOURNAL_ISSUE varchar(100) collate utf8_unicode_ci default NULL COMMENT 'Issue of the journal where an article was published.',
  ARTICLE_JOURNAL_VOLUME varchar(100) collate utf8_unicode_ci default NULL COMMENT 'Volume of the journal where an article was published.',
  ARTICLE_ISSN varchar(30) collate utf8_unicode_ci default NULL COMMENT 'ISSN code associated with an article.',
  ARTICLE_NUMBER varchar(2083) collate utf8_unicode_ci default NULL COMMENT 'Identifier associated with an article in the journal.',
  ARTICLE_PUBLICATION_DATE varchar(255) default NULL COMMENT 'Year of publication of an article.',
  ARTICLE_START_PAGE varchar(30) default NULL COMMENT 'Start page of an article in the journal.',
  ARTICLE_END_PAGE varchar(30) default NULL COMMENT 'End page of an article in the journal.',
  ARTICLE_FROM_ORIGINAL_SOURCE tinyint(1) NOT NULL default '0' COMMENT 'Indicator whether this article data is based on the original source.',
  PRIMARY KEY  (ARTICLE_ID)
) COMMENT 'Peer-reviewed articles.';

/*==============================================================*/
/* Table: ARTICLE_AUTHOR                                        */
/*==============================================================*/

CREATE TABLE IF NOT EXISTS ARTICLE_AUTHOR (
  ARTICLE_AUTHOR_ID int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for this article author.',
  ARTICLE_AUTHOR_FIRST_NAME varchar(125) collate utf8_unicode_ci default NULL COMMENT 'First name of the article author.',
  ARTICLE_AUTHOR_LAST_NAME varchar(125) collate utf8_unicode_ci default NULL COMMENT 'Last name of the article''s author.',
  ARTICLE_AUTHOR_FULL_NAME varchar(250) collate utf8_unicode_ci default NULL COMMENT 'Full name of the article author',
  PRIMARY KEY  (ARTICLE_AUTHOR_ID)
) COMMENT 'Authors of peer-reviewed articles.';


/*==============================================================*/
/* Table: ARTICLE_IDENTIFIER                                    */
/*==============================================================*/

CREATE TABLE IF NOT EXISTS ARTICLE_IDENTIFIER (
  ARTICLE_IDENTIFIER_ID int(15) NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for an article identifier.',
  ARTICLE_IDENTIFIER_TYPE varchar(20) collate utf8_unicode_ci NOT NULL COMMENT 'Type of identifier associated with an article (DOI, PMID, arXiv...)',
  ARTICLE_IDENTIFIER_TEXT varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Identifier associated with an article.',
  ARTICLE_ID int(15) NOT NULL COMMENT 'Reference to an article with this ID.',
  PRIMARY KEY  (ARTICLE_IDENTIFIER_ID),
  UNIQUE KEY FK_ARTICLE_IDENTIFIER (ARTICLE_IDENTIFIER_TEXT,ARTICLE_IDENTIFIER_TYPE,ARTICLE_ID)
) COMMENT 'Unique article identifiers.';

/*==============================================================*/
/* Table: ARTICLE_AUTHOR_LINK                                   */
/*==============================================================*/

CREATE TABLE IF NOT EXISTS ARTICLE_AUTHOR_LINK (
  ARTICLE_ID int(15) NOT NULL COMMENT 'Reference to an article created by an author.',
  ARTICLE_AUTHOR_ID int(15) NOT NULL COMMENT 'Reference to an author of an article.',
  UNIQUE KEY FK_AUTHOR_TO_ARTICLE (ARTICLE_ID,ARTICLE_AUTHOR_ID)
) COMMENT 'Articles containing these authors.';

/*==============================================================*/
/* Table: SCAN_POST                                             */
/*==============================================================*/

CREATE TABLE IF NOT EXISTS SCAN_POST (
  BLOG_ID int(15) NOT NULL COMMENT 'Reference to a blog that must be scanned.',
  MARKER_DATE_TIME timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP COMMENT 'Date of creation of a marker',
  MARKER_TYPE_ID int(15) NOT NULL COMMENT 'Reference to the type of marker.',
  UNIQUE KEY FK_MARKER (BLOG_ID,MARKER_TYPE_ID)
) COMMENT='Blogs with markers for the crawler.';


/*==============================================================*/
/* Table: BLOG_SOCIAL_ACCOUNT                                   */
/*==============================================================*/

CREATE TABLE IF NOT EXISTS BLOG_SOCIAL_ACCOUNT (
  SOCIAL_NETWORKING_ACCOUNT_ID int(10) unsigned NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for a social networking account.',
  SOCIAL_NETWORK_ID int(15) NOT NULL COMMENT 'Reference to the social network on which this account is hosted.',
  SOCIAL_NETWORKING_ACCOUNT_NAME varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'The account identifier a user uses on a particular social network.',
  BLOG_ID int(10) unsigned default NULL COMMENT 'The ID of a blog associated with this account.',
  OAUTH_TOKEN varchar(255) collate utf8_unicode_ci default NULL COMMENT 'OAuth token for the social network API.',
  OAUTH_SECRET_TOKEN varchar(255) collate utf8_unicode_ci default NULL COMMENT 'OAuth secret token for the social network API.',
  PRIMARY KEY  (SOCIAL_NETWORKING_ACCOUNT_ID),
  UNIQUE KEY AK_SOCIAL_NETWORK_USER_LOCATOR (SOCIAL_NETWORK_ID,SOCIAL_NETWORKING_ACCOUNT_NAME),
  UNIQUE KEY FK_BLOG_ID (BLOG_ID, SOCIAL_NETWORK_ID)
) COMMENT 'Social account to be associated with a blog';

/*==============================================================*/
/* Table: USER_SOCIAL_ACCOUNT                                   */
/*==============================================================*/

CREATE TABLE IF NOT EXISTS USER_SOCIAL_ACCOUNT (
  SOCIAL_NETWORKING_ACCOUNT_ID int(10) unsigned NOT NULL auto_increment COMMENT 'Machine-generated unique identifier for a social networking account.',
  SOCIAL_NETWORK_ID int(15) NOT NULL COMMENT 'Reference to the social network on which this account is hosted.',
  SOCIAL_NETWORKING_ACCOUNT_NAME varchar(127) collate utf8_unicode_ci NOT NULL COMMENT 'The account identifier a user uses on a particular social network.',
  USER_ID int(10) unsigned default NULL COMMENT 'The ID of a user associated with this account.',
  OAUTH_TOKEN varchar(255) collate utf8_unicode_ci default NULL COMMENT 'OAuth token for the social network API.',
  OAUTH_SECRET_TOKEN varchar(255) collate utf8_unicode_ci default NULL COMMENT 'OAuth secret token for the social network API.',
  PRIMARY KEY  (SOCIAL_NETWORKING_ACCOUNT_ID),
  UNIQUE KEY AK_SOCIAL_NETWORK_USER_LOCATOR (SOCIAL_NETWORK_ID,SOCIAL_NETWORKING_ACCOUNT_NAME),
  UNIQUE KEY FK_USER_ID (USER_ID, SOCIAL_NETWORK_ID)
) COMMENT 'Social account to be associated with a user';



/*==============================================================*/
/* Indexes                                                      */
/*==============================================================*/

create index topLevelTopics on TOPIC  (TOPIC_ID, TOPIC_NAME, TOPIC_TOP_LEVEL_INDICATOR);
create INDEX FK_CLAIMED_AUTHORS on BLOG_AUTHOR (USER_ID);
