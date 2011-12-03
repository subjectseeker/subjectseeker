/*==============================================================*/
/* This file is not intended to be run as part of SubjectSeeker */
/* installation.  It is only to be run on databases that        */
/* before Issue 55 was addressed, as a patch to their schema.   */
/*==============================================================*/

/*==============================================================*/
/* Copyright © 2011 Jessica P Hekman.                    */
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
/* Table: RECOMMENDATION                                        */
/*==============================================================*/
create table RECOMMENDATION
(
   PERSONA_ID             int(15) not null comment 'Reference to the persona who recommended this post.',
   BLOG_POST_ID         int(15) not null comment 'Reference to a post recommended by this persona.',
   REC_DATE_TIME datetime not null comment 'The date and time at which this recommendation was made.',
   REC_COMMENT text not null comment 'Comment from the persona, associated with this recommendation.',
   primary key (PERSONA_ID, BLOG_POST_ID)
);

alter table RECOMMENDATION comment 'Recommendations of particular posts.';

alter table RECOMMENDATION add constraint FK_RECOMMENDATIONS foreign key (BLOG_POST_ID)
      references BLOG_POST (BLOG_POST_ID) on delete restrict on update restrict;

alter table RECOMMENDATION add constraint FK_CITATION_POSTS foreign key (PERSONA_ID)
      references PERSONA (PERSONA_ID) on delete restrict on update restrict;

