/*==============================================================*/
/* This file is not intended to be run as part of SubjectSeeker */
/* installation.  It is only to be run on databases that        */
/* before Issue 56 was addressed, as a patch to their schema.   */
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
-- Table structure for table `SCAN_POST`
--

CREATE TABLE IF NOT EXISTS `SCAN_POST` (
  `BLOG_ID` int(15) NOT NULL COMMENT 'Reference to a blog that must be scanned.',
  `MARKER_DATE_TIME` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP COMMENT 'Date of creation of a marker',
  `MARKER_TYPE_ID` int(15) NOT NULL COMMENT 'Reference to the type of marker.',
  UNIQUE KEY `FK_MARKER` (`BLOG_ID`,`MARKER_TYPE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Blogs with markers for the crawler.';