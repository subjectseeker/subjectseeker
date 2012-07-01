/*==============================================================*/
/* This file is not intended to be run as part of SubjectSeeker */
/* installation.  It is only to be run on databases that        */
/* before Issue 94 was addressed, as a patch to their schema.   */
/*==============================================================*/

/*==============================================================*/
/* Copyright © 2012 Jessica Hekman.                             */
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

/* SQL improvements for speed and readability suggested by Len Vishnevsky */

ALTER TABLE BLOG MODIFY COLUMN
   BLOG_ID              INTEGER UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Machine-generated unique identifier for this blog.';

ALTER TABLE BLOG MODIFY COLUMN
   BLOG_STATUS_ID       TINYINT UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Reference to the moderation status associated with this blog.';

ALTER TABLE BLOG MODIFY COLUMN BLOG_URI text NOT NULL COMMENT 'The URI intended for visiting this blog in normal usage.';

ALTER TABLE BLOG MODIFY COLUMN BLOG_SYNDICATION_URI text NOT NULL COMMENT 'The URI via which a syndication feed for this blog can be accessed.';

ALTER TABLE BLOG DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE TOPIC MODIFY COLUMN TOPIC_ID INTEGER UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Machine-generated unique identifier for this topic.';

ALTER TABLE TOPIC MODIFY COLUMN TOPIC_TOP_LEVEL_INDICATOR BOOL NOT NULL DEFAULT '0' comment 'Indicator whether this topic should be presented as a root for interactive topic navigation, independent of whether or not its more general topics are known.';
ALTER TABLE TOPIC DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT 'A string or tag representing a subject of interest to aggregate';
