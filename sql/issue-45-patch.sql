/*==============================================================*/
/* This file is not intended to be run as part of SubjectSeeker */
/* installation.  It is only to be run on databases that were installed */
/* before Issue 45 was addressed, as a patch to their schema.   */
/*==============================================================*/

/*==============================================================*/
/* Copyright © 2011Jessica P. Hekman   n.                       */
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
/* Table: CITATION                                              */
/*==============================================================*/
create table CITATION
(
   CITATION_ID          int(15) not null auto_increment comment 'A machine-generated unique identifier for a citation.',
   CITATION_TEXT        text not null comment 'The HTML text of this citation',
   primary key (CITATION_ID)
);

alter table POST_CITATION add constraint FK_POST_CITATIONS foreign key (BLOG_POST_ID)
      references BLOG_POST (BLOG_POST_ID) on delete restrict on update restrict;

alter table POST_CITATION add constraint FK_CITATION_POSTS foreign key (CITATION_ID)
      references CITATION (CITATION_ID) on delete restrict on update restrict;
