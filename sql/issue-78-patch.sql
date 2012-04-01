/*==============================================================*/
/* This file is not intended to be run as part of SubjectSeeker */
/* installation.  It is only to be run on databases that        */
/* before Issue 55 was addressed, as a patch to their schema.   */
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

/*==============================================================*/
/* Table: CITATION_IDENTIFIER                                   */
/*==============================================================*/
create table CITATION_IDENTIFIER
(
   CITATION_ID          int(15) not null comment 'Reference to a citation included in a post.',
   CITATION_ID_TYPE     varchar(255) not null comment 'Type of identifier based on the source (DOI, PMID, arXiv...)',
   CITATION_ID_TEXT     text not null comment 'Identifier associated with this citation, based on the source',
   primary key (CITATION_ID)
);

alter table CITATION_IDENTIFIER comment 'Citation identifiers associated with the citations';

alter table CITATION_IDENTIFIER add constraint FK_CITATION_ID foreign key (CITATION_ID)
      references CITATION (CITATION_ID) on delete restrict on update restrict;