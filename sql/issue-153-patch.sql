/*==============================================================*/
/* This file is not intended to be run as part of SubjectSeeker */
/* installation.  It is only to be run on databases that        */
/* before Issue 153 was addressed, as a patch to their schema.   */
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
-- Table structure for table `NOTIFICATION`
--

DROP TABLE IF EXISTS `NOTIFICATION`;
CREATE TABLE IF NOT EXISTS `NOTIFICATION` (
  `NOTIFICATION_ID` int(11) NOT NULL auto_increment COMMENT 'Unique identifier of a notification',
  `OBJECT_ID` int(11) NOT NULL COMMENT 'Reference to an object.',
  `OBJECT_TYPE_ID` int(11) NOT NULL COMMENT 'Reference to a type of object.',
  `USER_ID` int(11) default NULL COMMENT 'Reference to a user.',
  `NOTIFICATION_STATUS_ID` tinyint(4) NOT NULL COMMENT 'Reference to the status of a notification.',
  `NOTIFICATION_TYPE_ID` int(11) NOT NULL COMMENT 'Reference to a type of notification.',
  `NOTIFICATION_DATE_TIME` datetime NOT NULL COMMENT 'Date and time of creation of a notification.',
  PRIMARY KEY  (`NOTIFICATION_ID`),
  UNIQUE KEY `OBJECT_ID` (`OBJECT_ID`,`OBJECT_TYPE_ID`,`USER_ID`,`NOTIFICATION_TYPE_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Notifications to be sent to users.';


ALTER TABLE USER_PREFERENCE ADD `EMAIL_FOLLOWS` tinyint(1) NOT NULL COMMENT 'Allow emails of new follows.';

UPDATE USER_PREFERENCE SET EMAIL_FOLLOWS = '1';