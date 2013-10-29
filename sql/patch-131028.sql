/* Patch to improve efficiency of schema and speed up slow queries.
   Written by Len Vishnevsky. */

ALTER TABLE BLOG_POST DROP KEY FK_BLOG_POST_STATUS, ADD KEY `BLOG_POST-BLOG_POST_STATUS_ID-BLOG_POST_DATE_TIME` (BLOG_POST_STATUS_ID, BLOG_POST_DATE_TIME);

ALTER TABLE TAG DROP KEY TOPIC_ID, DROP KEY FK_OBJECTS;

-- Depending on how you use the topLevelTopics index, it might make sense to make this change. It will only have one copy of the TOPIC_ID
-- index instead of two, and the index lookup will be faster. Also, I moved the tinying ahead of the varchar, I think that might make
-- it faster. This is not urgent, though.
-- ALTER TABLE TOPIC DROP PRIMARY KEY, ADD PRIMARY KEY (TOPIC_ID, TOPIC_TOP_LEVEL_INDICATOR, TOPIC_NAME);
