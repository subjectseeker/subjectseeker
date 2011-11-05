/*==============================================================*/
/* DBMS name:      MySQL 5.0                                    */
/* Created on:     12/20/2010 4:25:27 PM                        */
/*==============================================================*/

/*==============================================================*/
/* Copyright © 2010–2011 Christopher R. Maden and Jessica Perry */
/* Hekman.                                                      */
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

drop table if exists LANGUAGE;

drop table if exists PERSONA;

drop table if exists PERSONA_ADMINISTRATOR_NOTE;

drop table if exists POST_TOPIC;

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

/*==============================================================*/
/* Table: ADMINISTRATOR_NOTE                                    */
/*==============================================================*/
create table ADMINISTRATOR_NOTE
(
   ADMINISTRATOR_NOTE_ID int(15) not null auto_increment comment 'Machine-generated unique identifier for a note.',
   ADMINISTRATOR_NOTE_CONTENT text not null comment 'The free-text content of a note.',
   primary key (ADMINISTRATOR_NOTE_ID)
);

alter table ADMINISTRATOR_NOTE comment 'A system internal note associated with a particular system o';

/*==============================================================*/
/* Table: BLOG                                                  */
/*==============================================================*/
create table BLOG
(
   BLOG_ID              int(15) not null auto_increment comment 'Machine-generated unique identifier for this blog.',
   BLOG_STATUS_ID       int(15) not null comment 'Reference to the moderation status associated with this blog.',
   BLOG_NAME            varchar(255) not null comment 'Human-readable display name of this blog.',
   BLOG_URI             varchar(2083) not null comment 'The URI intended for visiting this blog in normal usage.',
   BLOG_SYNDICATION_URI varchar(2083) not null comment 'The URI via which a syndication feed for this blog can be accessed.',
   BLOG_DESCRIPTION     text comment 'The free-text human-readable description of the nature and intent of the blog.',
   ADDED_DATE_TIME      datetime not null comment 'The date and time when this blog was added to the aggregator.',
   CRAWLED_DATE_TIME    datetime comment 'The date and time on which this blog was last checked by the aggregator.',
   primary key (BLOG_ID)
);

alter table BLOG comment 'A Web log or feed therefrom intended for aggregation by the ';

/*==============================================================*/
/* Table: BLOG_ADMINISTRATOR_NOTE                               */
/*==============================================================*/
create table BLOG_ADMINISTRATOR_NOTE
(
   ADMINISTRATOR_NOTE_ID int(15) not null comment 'Reference to a note associated with this blog.',
   BLOG_ID              int(15) not null comment 'Reference to a blog annotated by this note.',
   primary key (ADMINISTRATOR_NOTE_ID, BLOG_ID)
);

alter table BLOG_ADMINISTRATOR_NOTE comment 'Association of an administrator note with a particular blog.';

/*==============================================================*/
/* Table: BLOG_AUTHOR                                           */
/*==============================================================*/
create table BLOG_AUTHOR
(
   BLOG_AUTHOR_ID       int(15) not null auto_increment comment 'Machine-generated unique identifier for this blog author.',
   BLOG_ID              int(15) not null comment 'Reference to the blog with which this author is associated.',
   PERSONA_ID           int(15) comment 'Reference to a persona which has claimed this blog.',
   BLOG_AUTHOR_ACCOUNT_NAME varchar(255) not null comment 'The identifier used for this author on the blog to which they contribute: the user name or display name, as appropriate for the blog in question.',
   primary key (BLOG_AUTHOR_ID),
   unique key AK_BLOG_AUTHOR_ACCOUNT (BLOG_ID, BLOG_AUTHOR_ACCOUNT_NAME)
);

alter table BLOG_AUTHOR comment 'A known contributor to a blog of interest, who may or may no';

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
);

alter table BLOG_GROUP comment 'A collection of blogs characterized by network affiliation, ';

/*==============================================================*/
/* Table: BLOG_GROUPING                                         */
/*==============================================================*/
create table BLOG_GROUPING
(
   BLOG_GROUP_ID        int(15) not null comment 'Reference to the group into which the blog is being grouped.',
   BLOG_ID              int(15) not null comment 'Reference to the blog being grouped.',
   primary key (BLOG_GROUP_ID, BLOG_ID)
);

alter table BLOG_GROUPING comment 'Association of blogs into groups.';

/*==============================================================*/
/* Table: BLOG_GROUP_ADMINISTRATOR_NOTE                         */
/*==============================================================*/
create table BLOG_GROUP_ADMINISTRATOR_NOTE
(
   ADMINISTRATOR_NOTE_ID int(15) not null comment 'Reference to a note associated with this blog group.',
   BLOG_GROUP_ID        int(15) not null comment 'Reference to a blog group annotated by this note.',
   primary key (ADMINISTRATOR_NOTE_ID, BLOG_GROUP_ID)
);

alter table BLOG_GROUP_ADMINISTRATOR_NOTE comment 'Association of an administrator note with a particular blog ';

/*==============================================================*/
/* Table: BLOG_LANGUAGE                                         */
/*==============================================================*/
create table BLOG_LANGUAGE
(
   LANGUAGE_ID          int(15) not null comment 'Reference to the language in which a blog is written.',
   BLOG_ID              int(15) not null comment 'Reference to a blog written in this language.',
   primary key (LANGUAGE_ID, BLOG_ID)
);

alter table BLOG_LANGUAGE comment 'Languages used by blogs of interest.';

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
);

alter table BLOG_POST comment 'A post to a blog of interest to the system.';

/*==============================================================*/
/* Table: BLOG_POST_ADMINISTRATOR_NOTE                          */
/*==============================================================*/
create table BLOG_POST_ADMINISTRATOR_NOTE
(
   ADMINISTRATOR_NOTE_ID int(15) not null comment 'Reference to a note associated with this blog post.',
   BLOG_POST_ID         int(15) not null comment 'Reference to a blog post annotated by this note.',
   primary key (ADMINISTRATOR_NOTE_ID, BLOG_POST_ID)
);

alter table BLOG_POST_ADMINISTRATOR_NOTE comment 'Association of an administrator note with a particular blog ';

/*==============================================================*/
/* Table: BLOG_POST_STATUS                                      */
/*==============================================================*/
create table BLOG_POST_STATUS
(
   BLOG_POST_STATUS_ID  int(15) not null comment 'Machine-readable unique identifier for a blog post status level.',
   BLOG_POST_STATUS_DESCRIPTION varchar(127) not null comment 'Human-readable description of a blog post status level.',
   primary key (BLOG_POST_STATUS_ID),
   unique key AK_BLOG_POST_STATUS_DESCRIPTION (BLOG_POST_STATUS_DESCRIPTION)
);

alter table BLOG_POST_STATUS comment 'Approval status of a blog post, e.g., active, under review.';

/*==============================================================*/
/* Table: BLOG_STATUS                                           */
/*==============================================================*/
create table BLOG_STATUS
(
   BLOG_STATUS_ID       int(15) not null comment 'Machine-generated unique identifier for a blog status level.',
   BLOG_STATUS_DESCRIPTION varchar(127) not null comment 'Human-readable description of a blog status level.',
   primary key (BLOG_STATUS_ID),
   unique key AK_BLOG_STATUS_DESCRIPTION (BLOG_STATUS_DESCRIPTION)
);

alter table BLOG_STATUS comment 'The approval status of a blog, e.g., submitted, under review';

/*==============================================================*/
/* Table: CITATION                                              */
/*==============================================================*/
create table CITATION
(
   CITATION_ID          int(15) not null auto_increment comment 'A machine-generated unique identifier for a citation.',
   CITATION_TEXT        text not null comment 'The HTML text of this citation',
   primary key (CITATION_ID)
);

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
);

alter table LANGUAGE comment 'A human language (actually a locale), as defined in IETF BCP';

/*==============================================================*/
/* Table: PERSONA                                               */
/*==============================================================*/
create table PERSONA
(
   PERSONA_ID           int(15) not null auto_increment comment 'Machine-generated unique identifier for this persona.',
   USER_ID              int(15) comment 'Reference to the user account which owns this persona.',
   DISPLAY_NAME         varchar(255) not null comment 'The human-readable name associated with the persona.',
   BIOGRAPHY            text comment 'Arbitrarily long free text describing a public persona.',
   BLOG_AUTHOR_AVATAR_LOCATOR varchar(255) comment 'The system identifier for a graphical image associated with a persona on the aggregation system.',
   primary key (PERSONA_ID)
);

alter table PERSONA comment 'A public identity of an aggregation system user and/or of on';

/*==============================================================*/
/* Table: PERSONA_ADMINISTRATOR_NOTE                            */
/*==============================================================*/
create table PERSONA_ADMINISTRATOR_NOTE
(
   ADMINISTRATOR_NOTE_ID int(15) not null comment 'Reference to a note associated with this persona.',
   PERSONA_ID           int(15) not null comment 'Reference to a person annotated by this note.',
   primary key (PERSONA_ID, ADMINISTRATOR_NOTE_ID)
);

alter table PERSONA_ADMINISTRATOR_NOTE comment 'Association of an administrator note with a particular perso';

/*==============================================================*/
/* Table: POST_TOPIC                                            */
/*==============================================================*/
create table POST_TOPIC
(
   TOPIC_ID             int(15) not null comment 'Reference to a topic covered by a post.',
   BLOG_POST_ID         int(15) not null comment 'Reference to a post covering a topic.',
   primary key (TOPIC_ID, BLOG_POST_ID)
);

alter table POST_TOPIC comment 'Subjects of a particular post on a blog of interest.';

/*==============================================================*/
/* Table: POST_CITATION                                         */
/*==============================================================*/
create table POST_CITATION
(
   CITATION_ID             int(15) not null comment 'Reference to a citation included in a post.',
   BLOG_POST_ID         int(15) not null comment 'Reference to a post including a citation.',
   primary key (CITATION_ID, BLOG_POST_ID)
);

alter table POST_CITATION comment 'Blog posts containing citations.';

/*==============================================================*/
/* Table: PRIMARY_BLOG_TOPIC                                    */
/*==============================================================*/
create table PRIMARY_BLOG_TOPIC
(
   TOPIC_ID             int(15) not null comment 'Reference to a topic primarily covered by a blog.',
   BLOG_ID              int(15) not null comment 'Reference to a blog primarily covering a topic.',
   primary key (TOPIC_ID, BLOG_ID)
);

alter table PRIMARY_BLOG_TOPIC comment 'The main subjects of a blog of interest.';

/*==============================================================*/
/* Table: SECONDARY_BLOG_TOPIC                                  */
/*==============================================================*/
create table SECONDARY_BLOG_TOPIC
(
   TOPIC_ID             int(15) not null comment 'Reference to a topic secondarily covered by a blog.',
   BLOG_ID              int(15) not null comment 'Reference to a blog secondarily covering a topic.',
   primary key (TOPIC_ID, BLOG_ID)
);

alter table SECONDARY_BLOG_TOPIC comment 'Subjects of a blog of interest, other than the primary topic';

/*==============================================================*/
/* Table: SOCIAL_NETWORK                                        */
/*==============================================================*/
create table SOCIAL_NETWORK
(
   SOCIAL_NETWORK_ID    int(15) not null auto_increment comment 'Machine-generated unique identifier for a social network.',
   SOCIAL_NETWORK_NAME  varchar(127) not null comment 'Human-readable name of a social network.',
   primary key (SOCIAL_NETWORK_ID),
   unique key AK_SOCIAL_NETWORK_NAME (SOCIAL_NETWORK_NAME)
);

alter table SOCIAL_NETWORK comment 'A social network system with well-defined unique user IDs, t';

/*==============================================================*/
/* Table: SOCIAL_NETWORKING_ACCOUNT                             */
/*==============================================================*/
create table SOCIAL_NETWORKING_ACCOUNT
(
   SOCIAL_NETWORKING_ACCOUNT_ID int(15) not null auto_increment comment 'Machine-generated unique identifier for a social networking account.',
   PERSONA_ID           int(15) not null comment 'Reference to a persona associated with this account.',
   SOCIAL_NETWORK_ID    int(15) not null comment 'Reference to the social network on which this account is hosted.',
   SOCIAL_NETWORKING_ACCOUNT_NAME varchar(127) not null comment 'The account identifier a persona uses on a particular social network.',
   primary key (SOCIAL_NETWORKING_ACCOUNT_ID),
   unique key AK_SOCIAL_NETWORK_USER_LOCATOR (SOCIAL_NETWORK_ID, SOCIAL_NETWORKING_ACCOUNT_NAME)
);

alter table SOCIAL_NETWORKING_ACCOUNT comment 'An social networking account known to be associated with a p';

/*==============================================================*/
/* Table: TOPIC                                                 */
/*==============================================================*/
create table TOPIC
(
   TOPIC_ID             int(15) not null auto_increment comment 'Machine-generated unique identifier for this topic.',
   TOPIC_NAME           varchar(255) not null comment 'The human-readable but language-independent string that carries the entire meaning of this topic.',
   TOPIC_TOP_LEVEL_INDICATOR bool not null comment 'Indicator whether this topic should be presented as a root for interactive topic navigation, independent of whether or not its more general topics are known.',
   primary key (TOPIC_ID),
   unique key AK_TOPIC_NAME (TOPIC_NAME)
);

alter table TOPIC comment 'A string or tag representing a subject of interest to aggreg';

/*==============================================================*/
/* Table: TOPIC_ADMINISTRATOR_NOTE                              */
/*==============================================================*/
create table TOPIC_ADMINISTRATOR_NOTE
(
   TOPIC_ID             int(15) not null comment 'Reference to a topic annotated by this note.',
   ADMINISTRATOR_NOTE_ID int(15) not null comment 'Reference to a note associated with this topic.',
   primary key (TOPIC_ID, ADMINISTRATOR_NOTE_ID)
);

alter table TOPIC_ADMINISTRATOR_NOTE comment 'Association of an administrator note with a particular topic';

/*==============================================================*/
/* Table: TOPIC_SIMILARITY                                      */
/*==============================================================*/
create table TOPIC_SIMILARITY
(
   TOPIC_ID_1           int(15) not null comment 'Reference to the first half of a similarity assertion.',
   TOPIC_ID_2           int(15) not null comment 'Reference to the second half of a similarity assertion.',
   primary key (TOPIC_ID_1, TOPIC_ID_2)
);

alter table TOPIC_SIMILARITY comment 'Assertion of equivalence between topics.';

/*==============================================================*/
/* Table: TOPIC_SPECIFICITY                                     */
/*==============================================================*/
create table TOPIC_SPECIFICITY
(
   SPECIFIC_TOPIC_ID    int(15) not null comment 'Reference to the more specific topic being generalized.',
   GENERAL_TOPIC_ID     int(15) not null comment 'Reference to the more general topic being specialized.',
   primary key (SPECIFIC_TOPIC_ID, GENERAL_TOPIC_ID)
);

alter table TOPIC_SPECIFICITY comment 'Assertion of more or less specific subject coverage of topic';

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
   primary key (USER_ID),
   unique key AK_USER_NAME (USER_NAME)
);

alter table USER comment 'A user of the aggregation system, equating to a single login';

/*==============================================================*/
/* Table: USER_ADMINISTRATOR_NOTE                               */
/*==============================================================*/
create table USER_ADMINISTRATOR_NOTE
(
   USER_ID              int(15) not null comment 'Reference to a user account annotated by this note.',
   ADMINISTRATOR_NOTE_ID int(15) not null comment 'Reference to a note associated with this user account.',
   primary key (ADMINISTRATOR_NOTE_ID, USER_ID)
);

alter table USER_ADMINISTRATOR_NOTE comment 'Association of an administrator note with a particular user ';

/*==============================================================*/
/* Table: USER_PRIVILEGE                                        */
/*==============================================================*/
create table USER_PRIVILEGE
(
   USER_PRIVILEGE_ID    int(15) not null comment 'Machine-generated unique identifier for a particular user privilege level.',
   USER_PRIVILEGE_DESCRIPTION varchar(127) not null comment 'Human-readable description of a user privilege level.',
   primary key (USER_PRIVILEGE_ID),
   unique key AK_USER_PRIVILEGE_DESCRIPTION (USER_PRIVILEGE_DESCRIPTION)
);

alter table USER_PRIVILEGE comment 'Privilege status for a user of the aggregation system, e.g. ';

/*==============================================================*/
/* Table: USER_STATUS                                           */
/*==============================================================*/
create table USER_STATUS
(
   USER_STATUS_ID       int(15) not null comment 'Machine-generated unique identifier for a user status.',
   USER_STATUS_DESCRIPTION varchar(127) not null comment 'Human-readable description of a user status level.',
   primary key (USER_STATUS_ID),
   unique key AK_USER_STATUS_DESCRIPTION (USER_STATUS_DESCRIPTION)
);

create table CLAIM_BLOG
(
   CLAIM_ID              int(15) not null auto_increment comment 'Machine-generated unique identifier for this claim.',
   BLOG_ID              int(15) not null comment 'Reference to a blog which is pending being claimed.',
   USER_ID              int(15) not null comment 'Reference to a user who wishes to claim a blog.',
   CLAIM_TOKEN          varchar(64) not null comment 'Token for use in claiming a blog.',
   CLAIM_STATUS_ID      int(15) not null comment 'ID of status of this claim',
   CLAIM_DATE_TIME datetime not null comment 'The date and time at which this claim request was made.',
   primary key (CLAIM_ID)
);

create table CLAIM_BLOG_STATUS
(
   CLAIM_BLOG_STATUS_ID              int(15) not null comment 'Numeric ID of a status.',
   CLAIM_BLOG_STATUS_DESCRIPTION     int(15) not null comment 'Textual description of a status.',
   primary key (CLAIM_BLOG_STATUS_ID)
);

alter table USER_STATUS comment 'The approval status of a user account, e.g., active, under r';

alter table BLOG add constraint FK_BLOG_STATUS foreign key (BLOG_STATUS_ID)
      references BLOG_STATUS (BLOG_STATUS_ID) on delete restrict on update restrict;

alter table BLOG_ADMINISTRATOR_NOTE add constraint FK_BLOG_NOTES foreign key (BLOG_ID)
      references BLOG (BLOG_ID) on delete restrict on update restrict;

alter table BLOG_ADMINISTRATOR_NOTE add constraint FK_NOTE_BLOGS foreign key (ADMINISTRATOR_NOTE_ID)
      references ADMINISTRATOR_NOTE (ADMINISTRATOR_NOTE_ID) on delete restrict on update restrict;

alter table BLOG_AUTHOR add constraint FK_BLOG_CONTRIBUTOR foreign key (BLOG_ID)
      references BLOG (BLOG_ID) on delete restrict on update restrict;

alter table BLOG_AUTHOR add constraint FK_CLAIMED_AUTHORS foreign key (PERSONA_ID)
      references PERSONA (PERSONA_ID) on delete restrict on update restrict;

alter table BLOG_GROUPING add constraint FK_BLOG_GROUPS foreign key (BLOG_ID)
      references BLOG (BLOG_ID) on delete restrict on update restrict;

alter table BLOG_GROUPING add constraint FK_GROUP_BLOGS foreign key (BLOG_GROUP_ID)
      references BLOG_GROUP (BLOG_GROUP_ID) on delete restrict on update restrict;

alter table BLOG_GROUP_ADMINISTRATOR_NOTE add constraint FK_BLOG_GROUP_NOTES foreign key (BLOG_GROUP_ID)
      references BLOG_GROUP (BLOG_GROUP_ID) on delete restrict on update restrict;

alter table BLOG_GROUP_ADMINISTRATOR_NOTE add constraint FK_NOTE_BLOG_GROUPS foreign key (ADMINISTRATOR_NOTE_ID)
      references ADMINISTRATOR_NOTE (ADMINISTRATOR_NOTE_ID) on delete restrict on update restrict;

alter table BLOG_LANGUAGE add constraint FK_BLOG_LANGUAGES foreign key (BLOG_ID)
      references BLOG (BLOG_ID) on delete restrict on update restrict;

alter table BLOG_LANGUAGE add constraint FK_LANGUAGE_BLOGS foreign key (LANGUAGE_ID)
      references LANGUAGE (LANGUAGE_ID) on delete restrict on update restrict;

alter table BLOG_POST add constraint FK_BLOG_POSTS foreign key (BLOG_ID)
      references BLOG (BLOG_ID) on delete restrict on update restrict;

alter table BLOG_POST add constraint FK_BLOG_POST_LANGUAGE foreign key (LANGUAGE_ID)
      references LANGUAGE (LANGUAGE_ID) on delete restrict on update restrict;

alter table BLOG_POST add constraint FK_BLOG_POST_STATUS foreign key (BLOG_POST_STATUS_ID)
      references BLOG_POST_STATUS (BLOG_POST_STATUS_ID) on delete restrict on update restrict;

alter table BLOG_POST add constraint FK_POST_AUTHOR foreign key (BLOG_AUTHOR_ID)
      references BLOG_AUTHOR (BLOG_AUTHOR_ID) on delete restrict on update restrict;

alter table BLOG_POST_ADMINISTRATOR_NOTE add constraint FK_BLOG_POST_NOTES foreign key (BLOG_POST_ID)
      references BLOG_POST (BLOG_POST_ID) on delete restrict on update restrict;

alter table BLOG_POST_ADMINISTRATOR_NOTE add constraint FK_NOTE_BLOG_POSTS foreign key (ADMINISTRATOR_NOTE_ID)
      references ADMINISTRATOR_NOTE (ADMINISTRATOR_NOTE_ID) on delete restrict on update restrict;

alter table PERSONA add constraint FK_USER_PERSONAE foreign key (USER_ID)
      references USER (USER_ID) on delete restrict on update restrict;

alter table PERSONA_ADMINISTRATOR_NOTE add constraint FK_NOTE_PERSONAE foreign key (ADMINISTRATOR_NOTE_ID)
      references ADMINISTRATOR_NOTE (ADMINISTRATOR_NOTE_ID) on delete restrict on update restrict;

alter table PERSONA_ADMINISTRATOR_NOTE add constraint FK_PERSONA_NOTES foreign key (PERSONA_ID)
      references PERSONA (PERSONA_ID) on delete restrict on update restrict;

alter table POST_TOPIC add constraint FK_POST_TOPICS foreign key (BLOG_POST_ID)
      references BLOG_POST (BLOG_POST_ID) on delete restrict on update restrict;

alter table POST_TOPIC add constraint FK_TOPIC_POSTS foreign key (TOPIC_ID)
      references TOPIC (TOPIC_ID) on delete restrict on update restrict;

alter table POST_CITATION add constraint FK_POST_CITATIONS foreign key (BLOG_POST_ID)
      references BLOG_POST (BLOG_POST_ID) on delete restrict on update restrict;

alter table POST_CITATION add constraint FK_CITATION_POSTS foreign key (CITATION_ID)
      references CITATION (CITATION_ID) on delete restrict on update restrict;

alter table PRIMARY_BLOG_TOPIC add constraint FK_BLOG_PRIMARY_TOPICS foreign key (BLOG_ID)
      references BLOG (BLOG_ID) on delete restrict on update restrict;

alter table PRIMARY_BLOG_TOPIC add constraint FK_TOPIC_PRIMARY_BLOGS foreign key (TOPIC_ID)
      references TOPIC (TOPIC_ID) on delete restrict on update restrict;

alter table SECONDARY_BLOG_TOPIC add constraint FK_BLOG_SECONDARY_TOPICS foreign key (BLOG_ID)
      references BLOG (BLOG_ID) on delete restrict on update restrict;

alter table SECONDARY_BLOG_TOPIC add constraint FK_TOPIC_SECONDARY_BLOGS foreign key (TOPIC_ID)
      references TOPIC (TOPIC_ID) on delete restrict on update restrict;

alter table SOCIAL_NETWORKING_ACCOUNT add constraint FK_ACCOUNT_SOCIAL_NETWORK foreign key (SOCIAL_NETWORK_ID)
      references SOCIAL_NETWORK (SOCIAL_NETWORK_ID) on delete restrict on update restrict;

alter table SOCIAL_NETWORKING_ACCOUNT add constraint FK_SOCIAL_USER foreign key (PERSONA_ID)
      references PERSONA (PERSONA_ID) on delete restrict on update restrict;

alter table TOPIC_ADMINISTRATOR_NOTE add constraint FK_NOTE_TOPICS foreign key (ADMINISTRATOR_NOTE_ID)
      references ADMINISTRATOR_NOTE (ADMINISTRATOR_NOTE_ID) on delete restrict on update restrict;

alter table TOPIC_ADMINISTRATOR_NOTE add constraint FK_TOPIC_NOTES foreign key (TOPIC_ID)
      references TOPIC (TOPIC_ID) on delete restrict on update restrict;

alter table TOPIC_SIMILARITY add constraint FK_TOPIC_SIMILARITY_1 foreign key (TOPIC_ID_1)
      references TOPIC (TOPIC_ID) on delete restrict on update restrict;

alter table TOPIC_SIMILARITY add constraint FK_TOPIC_SIMILARITY_2 foreign key (TOPIC_ID_2)
      references TOPIC (TOPIC_ID) on delete restrict on update restrict;

alter table TOPIC_SPECIFICITY add constraint FK_TOPIC_GENERALIZATION foreign key (SPECIFIC_TOPIC_ID)
      references TOPIC (TOPIC_ID) on delete restrict on update restrict;

alter table TOPIC_SPECIFICITY add constraint FK_TOPIC_SPECIALIZATION foreign key (GENERAL_TOPIC_ID)
      references TOPIC (TOPIC_ID) on delete restrict on update restrict;

alter table USER add constraint FK_USER_PRIVILEGE_LEVEL foreign key (USER_PRIVILEGE_ID)
      references USER_PRIVILEGE (USER_PRIVILEGE_ID) on delete restrict on update restrict;

alter table USER add constraint FK_USER_STATUS foreign key (USER_STATUS_ID)
      references USER_STATUS (USER_STATUS_ID) on delete restrict on update restrict;

alter table USER_ADMINISTRATOR_NOTE add constraint FK_NOTE_USERS foreign key (ADMINISTRATOR_NOTE_ID)
      references ADMINISTRATOR_NOTE (ADMINISTRATOR_NOTE_ID) on delete restrict on update restrict;

alter table USER_ADMINISTRATOR_NOTE add constraint FK_USER_NOTES foreign key (USER_ID)
      references USER (USER_ID) on delete restrict on update restrict;

alter table CLAIM_BLOG add constraint FK_CLAIM_STATUS_ID foreign key (CLAIM_STATUS_ID)
      references CLAIM_BLOG_STATUS (CLAIM_BLOG_STATUS_ID) on delete restrict on update restrict;
