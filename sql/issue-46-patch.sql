/*==============================================================*/
/* This file is not intended to be run as part of SubjectSeeker */
/* installation.  It is only to be run on databases that        */
/* before Issue 30 was addressed, as a patch to their schema.   */
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

create table TOPIC_SOURCE
(
   TOPIC_SOURCE_ID              int(15) not null comment 'Numeric ID of a topic source',
   TOPIC_SOURCE_NAME            varchar(255) not null comment 'Textual description of a source.',
   primary key (TOPIC_SOURCE_ID)
);
alter table TOPIC_SOURCE comment 'The source of a topic, e.g., Post (the post itself) or ScienceSeeker (specified on ScienceSeeker site)';


ALTER TABLE POST_TOPIC ADD TOPIC_SOURCE		int(15) not null comment 'Reference to the source of this topic';



