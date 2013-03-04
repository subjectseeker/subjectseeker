<?php

/*
Copyright © 2010–2012 Christopher R. Maden, Jessica Perry Hekman and Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

global $basedir;

/*
 * API
 */

class API {
	var $posts = array();
	var $sites = array();
	var $topics = array();
	var $total = "";
	var $errors = array();
	private $where = array();
	private $from = array();
	private $group = "";
	private $count = "";
	
	public function searchDb($httpQuery = NULL, $allowOverride = TRUE, $type = NULL, $useCache = TRUE, $userPriv = 0) {
		$db = ssDbConnect();
		$params = parseHttpParams($httpQuery, $allowOverride);
		if ($type) {
			$params["parameters"]["type"] = $type;
		}
		$params["parameters"]["privilege"] = $userPriv;
		
		$cache = new cache("posts-".$params["string"], TRUE, FALSE);
		if ($cache->caching == TRUE || !$useCache) {
			$queryResult = $this->generateSearchQuery($params, $db);
			
			if ($queryResult["errors"]) {
				foreach ($queryResult["errors"] as $error) {
					$this->errors[] = $error;
				}
				
				return FALSE;
			}
			
			if (!$queryResult["result"]) {
				return NULL;
			}
			
			if ($params["parameters"]["type"] == "post") {
				$posts = array();
				while ($row = mysql_fetch_array($queryResult["result"])) {
					$post["postId"] = $row["BLOG_POST_ID"];
					$post["postTitle"] = $row["BLOG_POST_TITLE"];
					$post["postUrl"] = htmlspecialchars($row["BLOG_POST_URI"]);
					$post["postSummary"] = $row["BLOG_POST_SUMMARY"];
					$post["siteId"] = $row["BLOG_ID"];
					$post["siteName"] = $row["BLOG_NAME"];
					$post["siteUrl"] = htmlspecialchars($row["BLOG_URI"]);
					$post["postAuthorId"] = $row["BLOG_AUTHOR_ID"];
					$post["postAuthorName"] = $row["BLOG_AUTHOR_ACCOUNT_NAME"];
					$post["postDate"] = $row["BLOG_POST_DATE_TIME"];
					$post["hasCitation"] = $row["BLOG_POST_HAS_CITATION"];
					
					if ($params["parameters"]["citation-in-summary"] == "true") {
						$citations = postIdToCitation($post["postId"], $db);
						if ($citations) {
							$post["postSummary"] .= "<br />";
							foreach ($citations as $citation) {
								$post["postSummary"] .= "<br />".$citation["text"];
							}
						}
					}
					if ($params["parameters"]["source-in-title"] == "true") {
						$post["postTitle"] = "[".$post["siteName"]."] ".$post["postTitle"];
					}
					
					array_push($posts, $post);
				}
				$this->posts = $posts;
				$this->total = $queryResult["total"];
				
			} elseif ($params["parameters"]["type"] == "blog") {
				$sites = array();
				while ($row = mysql_fetch_array($queryResult["result"])) {
					$site["siteId"] = $row["BLOG_ID"];
					$site["siteSummary"] = $row["BLOG_DESCRIPTION"];
					$site["siteName"] = $row["BLOG_NAME"];
					$site["siteUrl"] = htmlspecialchars($row["BLOG_URI"]);
					$site["siteFeedUrl"] = $row["BLOG_SYNDICATION_URI"];
					$site["siteAddedDate"] = $row["ADDED_DATE_TIME"];
					$site["siteCrawledDate"] = $row["CRAWLED_DATE_TIME"];
					$site["siteStatus"] = $row["BLOG_STATUS_ID"];
					
					array_push($sites, $site);
				}
				$this->sites = $sites;
				$this->total = $queryResult["total"];
				
			} elseif ($params["parameters"]["type"] == "topic") {
				$topics = array();
				while ($row = mysql_fetch_array($queryResult["result"])) {
					$topic["topicId"] = $row["TOPIC_ID"];
					$topic["topicName"] = $row["TOPIC_NAME"];
					$topic["topicLevel"] = $row["TOPIC_TOP_LEVEL_INDICATOR"];
					
					array_push($topics, $topic);
				}
				$this->topics = $topics;
				$this->total = $queryResult["total"];
			}
			
			$cacheVars["posts"] = $this->posts;
			$cacheVars["sites"] = $this->sites;
			$cacheVars["topics"] = $this->topics;
			$cacheVars["errors"] = $this->errors;
			$cacheVars["total"] = $this->total;
			
			if ($useCache)
				$cache->storeVars($cacheVars);
				
		} else {
			$cacheVars = $cache->varCache();
			$this->posts = $cacheVars["posts"];
			$this->sites = $cacheVars["sites"];
			$this->topics = $cacheVars["topics"];
			$this->errors = $cacheVars["errors"];
			$this->total = $cacheVars["total"];
		}
	}
	
	// Input: type of object to search for (blog/post/topic); list of query parameters; DB handle
	// Output: XML document containing search results
	// For more information on query parameters, see search API documentation in wiki
	private function generateSearchQuery ($params, $db) {
	
		global $numResults;
		global $maximumResults;
		// Set all the default values of the search
		$type = $params["parameters"]["type"];
		$result = array();
		$group = NULL;
		$count = NULL;
		$order = NULL;
		$limit = NULL;
		$offset = NULL;
		
		
		if ($type == "") {
			array_push ($this->errors, "No search type specified.");
			return;
		} elseif ($type === "topic") {
			$select = "SELECT topic.TOPIC_ID, topic.TOPIC_NAME, topic.TOPIC_TOP_LEVEL_INDICATOR";
			$fromList = generateTopicFrom($params);
			$whereList = generateTopicWhere ($params);
	
		} else if ($type === "blog") {
			$select = "SELECT SQL_CALC_FOUND_ROWS blog.*";
			$order = "ORDER BY blog.BLOG_NAME ASC";
			$group = "GROUP BY blog.BLOG_ID";
			$fromList = $this->generateBlogFrom($params);
			$whereList = $this->generateBlogWhere($params);
			$order = $this->generateBlogSort ($params);
	
		} else if ($type === "post") {
			$select = "SELECT SQL_CALC_FOUND_ROWS post.BLOG_POST_ID, post.BLOG_POST_URI, post.BLOG_POST_DATE_TIME, post.BLOG_POST_SUMMARY, post.BLOG_POST_TITLE, post.BLOG_POST_HAS_CITATION, blog.BLOG_ID, blog.BLOG_NAME, blog.BLOG_URI, blog.BLOG_SYNDICATION_URI, author.BLOG_AUTHOR_ID, author.BLOG_AUTHOR_ACCOUNT_NAME";
			$fromList = $this->generatePostFrom($params);
			$whereList = $this->generatePostWhere($params);
			$order = $this->generatePostSort ($params);
			$count = $this->count;
			$group = $this->group;
				
		} else {
			array_push ($this->errors, "Unknown type: $type");
		}
		
		if ($this->errors) {
			$result["errors"] = $this->errors;
			return $result;
		}
		
		$limitNumber = $numResults;
		// Construct LIMIT part of query
		if (is_numeric($params["parameters"]["n"]) && ($params["parameters"]["n"] > 0 && $params["parameters"]["n"] <= $maximumResults) ) {
			$limitNumber = (string)(int)$params["parameters"]["n"];
		}
		$limit = "LIMIT $limitNumber";
	
		// Construct OFFSET part of query, default to 0.
		$offsetNumber = 0;
		if (is_numeric($params["parameters"]["offset"]) && ($params["parameters"]["offset"] > 0 ) ) {
			$offsetNumber = (string)(int)$params["parameters"]["offset"];
		}
		$offset = "OFFSET $offsetNumber";
	
		// Construct FROM part of query
		$from = $this->constructFrom($fromList);
		
		// Construct WHERE part of query
		$where = $this->constructWhere($whereList);
		
		// construct SQL query
		$sql = "$select $from $where $group $count $order $limit $offset;";
	
		// for debugging:
		//print "<br />SQL $sql</br>";
	
		// execute SQL query
		$result["result"] = mysql_query($sql, $db);
		$total = mysql_fetch_array(mysql_query("SELECT FOUND_ROWS()", $db));
		$result["total"] = array_shift($total);
		$result["errors"] = $this->errors;
		
		return $result;
	}
	
	// Input: Array of SQL where statements
	// Output: String for SQL query
	private function constructWhere ($whereList) {
		$whereList = array_unique($whereList);
		
		$where = "";
		foreach ($whereList as $oneWhere) {
			if ($where === "") {
				$where = "WHERE ($oneWhere)";
			} else {
				$where .= " AND ($oneWhere)";
			}
		}
		$where .= " ";
		
		return $where;
	}
	
	// Input: Array of SQL from statements
	// Output: String for SQL query
	private function constructFrom ($fromList) {
		$from = "";
		foreach ($fromList as $oneFrom => $status) {
			if ($from === "") {
				$from = "FROM $oneFrom";
			} else {
				$from .= ", $oneFrom";
			}
		}
		$from .= " ";
		
		return $from;
	}
	
	// Input: list of search queries for a topic search
	// Return: string useful in FROM clause in SQL search, based on input queries
	private function generateTopicFrom ($params) {
		$this->from["TOPIC topic"] = true;
		return $fromList;
	}
	
	// Input: list of search queries for a topic search
	// Return: string useful in WHERE clause in SQL search, based on input queries
	private function generateTopicWhere ($params) {
		
		foreach ($params["filters"] as $query) {
			// Escape strings that could be included in the SQL query
			$searchValue = mysql_real_escape_string($query["value"]);
			$searchType = mysql_real_escape_string($query["modifier"]);
			
			if ($query["name"] === "toplevel") {
				if ($searchValue == "false")
					$toplevel = "0";
				elseif ($searchValue == "true" || $searchValue == NULL)
					$toplevel = "1";
				else
					array_push ($this->errors, "Unrecognized value: $searchValue");
				
				array_push($this->where, "topic.TOPIC_TOP_LEVEL_INDICATOR = '$toplevel'");
				
				if ($searchType)
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else {
				array_push ($this->errors, "Unknown filter: " . $query["name"]);
				return "";
			}
		}
		return $whereList;
	}
	
	// Input: list of search queries for a blog search
	// Return: string useful in FROM clause in SQL search, based on input queries
	private function generateBlogFrom ($params) {
		
		$this->from["BLOG blog"] = true;
		foreach ($params["filters"] as $query) {
			if ($query["name"] === "topic") {
				$this->from["BLOG blog"] = true;
				$this->from["TAG tag"] = true;
				$this->from["TOPIC t"] = true;
				
			} else if ($query["name"] === "group") {
				$this->from["TAG tag"] = true;
				
			} else if ($query["name"] === "author") {
				$this->from["BLOG_AUTHOR author"] = true;
				if ($query["modifier"] == "user-name") {
					$this->from["USER user"] = true;
				}
			} else if ($query["name"] === "has-citation") {
				$this->from["BLOG_POST post"] = true;
			}
		}
		return $this->from;
	}
	
	// Input: list of search queries for a blog search
	// Return: string useful in WHERE clause in SQL search, based on input queries
	private function generateBlogWhere ($params) {
		
		if ($params["parameters"]["privilege"] <= 0 && $params["parameters"]["show-all"] != "true")
			array_push ($this->where, "blog.BLOG_STATUS_ID = 0");
		
		$topics = NULL;
		foreach ($params["filters"] as $query) {
			
			// Escape strings that could be included in the SQL query
			$searchValue = mysql_real_escape_string($query["value"]);
			$searchType = mysql_real_escape_string($query["modifier"]);
			
			if ($query["name"] === "topic") {
				$topics[] = "t.TOPIC_NAME='$searchValue' AND blog.BLOG_ID = tag.OBJECT_ID AND tag.OBJECT_TYPE_ID = '3' AND tag.TOPIC_ID = t.TOPIC_ID";
				
				if ($searchType)
					array_push ($this->errors, "Unrecognized modifier: $searchType");
	
			} else if ($query["name"] === "citation") {
				if ($searchType == "author") {
					array_push ($this->where, "blog.BLOG_ID = post.BLOG_ID","post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","auart.ARTICLE_ID = citation.ARTICLE_ID","auart.ARTICLE_AUTHOR_ID = artau.ARTICLE_AUTHOR_ID","artau.ARTICLE_AUTHOR_FULL_NAME LIKE '%$searchValue%'");
					
				} elseif ($searchType == "article-title") {
					array_push ($this->where, "blog.BLOG_ID = post.BLOG_ID","post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","art.ARTICLE_ID = citation.ARTICLE_ID","art.ARTICLE_TITLE LIKE '%$searchValue%'");
					
				} elseif ($searchType == "journal-title") {
					array_push ($this->where, "blog.BLOG_ID = post.BLOG_ID","post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","art.ARTICLE_ID = citation.ARTICLE_ID","art.ARTICLE_JOURNAL_TITLE LIKE '%$searchValue%'");
					
				} elseif ($searchType == "id-all" || $searchType == "doi" || $searchType == "pmid" || $searchType == "arxiv" || $searchType == "other" || $searchType == NULL) {
					array_push ($this->where, "blog.BLOG_ID = post.BLOG_ID","post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","artid.ARTICLE_IDENTIFIER_TEXT = '$searchValue'","citation.ARTICLE_ID = artid.ARTICLE_ID");
					
					if ($searchType != NULL && $searchType != "id-all")
						array_push ($this->where, "artid.ARTICLE_IDENTIFIER_TYPE = '$searchType'");
						
				} else { 
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				}
	
			} elseif ($query["name"] === "has-citation") {
				if ($searchValue == "false")
					array_push ($this->where, "post.BLOG_POST_HAS_CITATION='0' AND post.BLOG_ID=blog.BLOG_ID");
				elseif ($searchValue == "true" || $searchValue == NULL)
					array_push ($this->where, "post.BLOG_POST_HAS_CITATION='1' AND post.BLOG_ID=blog.BLOG_ID");
				else
					array_push ($this->errors, "Unrecognized value: $searchValue");
				
				if ($searchType === "doi" || $searchType === "pmid" || $searchType === "arxiv" || $searchType === "other") {
					array_push ($this->where, "post.BLOG_POST_ID = pc.BLOG_POST_ID AND pc.CITATION_ID = citation.CITATION_ID AND citation.ARTICLE_ID = artid.ARTICLE_ID AND artid.ARTICLE_IDENTIFIER_TYPE = '$searchType'");
				}
				elseif ($searchType != "all" && $searchType != NULL && $searchType != "") {
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				}
				
			} else if ($query["name"] === "group") {
				if (is_numeric($searchValue)) {
					$db = ssDbConnect();
					$groupTags = getTags($searchValue, 4, 3, $db);
					$groupTopics = "";
					foreach ($groupTags as $i => $tag) {
						if ($i != 0) {
							$groupTopics .= " OR ";
						}
						$topicId = $tag["topicId"];
						$tagPrivacy = $tag["tagPrivacy"];
						$groupTopics .= "(tag.TOPIC_ID = '$topicId'";
						if ($tagPrivacy) {
							$tagUserId = $tag["userId"];
							$groupTopics .= " AND tag.USER_ID = $tagUserId";
						}
						$groupTopics .= ")";
						
						$this->group = "GROUP BY blog.BLOG_ID";
					}
					array_push ($this->where, "tag.OBJECT_TYPE_ID = '3' AND ($groupTopics) AND tag.OBJECT_ID = blog.BLOG_ID");
				
				} else{ 
					array_push ($this->errors, "Identifier value must be numeric: $searchValue");
				}
				
				if ($searchType)
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else if ($query["name"] == "identifier") {
				if (is_numeric($searchValue))
					array_push ($this->where, "blog.BLOG_ID='$searchValue'");
				else
					array_push ($this->errors, "Identifier value must be numeric: $searchValue");
				
				if ($searchType)
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else if ($query["name"] == "title") {
				if ($searchType === "all")
					array_push ($this->where, "blog.BLOG_NAME = '$searchValue'");
				elseif ($searchType === "some" || $searchType == NULL)
					array_push ($this->where, "blog.BLOG_NAME LIKE '%$searchValue%'");
				else
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else if ($query["name"] === "author") {
				array_push ($this->where, "blog.BLOG_ID = author.BLOG_ID");
				
				if ($searchType == "user-name")
					array_push ($this->where, "user.USER_NAME = '$searchValue'", "user.USER_ID = author.USER_ID");
				elseif ($searchType == "author-id")
					array_push ($this->where, "author.BLOG_AUTHOR_ID = '$searchValue'");
				elseif ($searchType == "author-name" || empty($searchType))
					array_push ($this->where, "author.BLOG_AUTHOR_ACCOUNT_NAME LIKE '%$searchValue%'");
				else
					array_push ($this->errors, "Unrecognized modifier: $searchType");
			
			} else if ($query["name"] == "summary") {
				if ($searchType === "all")
					array_push ($this->where, "blog.BLOG_DESCRIPTION = '$searchValue'");
				elseif ($searchType === "some" || $searchType == NULL)
					array_push ($this->where, "blog.BLOG_DESCRIPTION LIKE '%$searchValue%'");
				else
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else if ($query["name"] == "url") {
				if ($searchType === "all")
					array_push ($this->where, "blog.BLOG_URI = '$searchValue'");
				elseif ($searchType === "some" || $searchType == NULL)
					array_push ($this->where, "blog.BLOG_URI LIKE '%$searchValue%'");
				else
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} elseif ($query["name"] == "status") {
				// Filter only for administration tools, not meant to be used by normal users since it displayes rejected sites.
				if ($params["parameters"]["privilege"] > 0) {
					if (is_numeric($searchValue))
						array_push ($this->where, "blog.BLOG_STATUS_ID = '$searchValue'");
					else
						array_push ($this->errors, "Status value must be numeric: $searchValue");
				} else {
					array_push ($this->errors, "You don't have the privileges to use filter: " . $query["name"]);
				}
			} else {
				array_push ($this->errors, "Unrecognized filter: " . $query["name"]);
			}
		}
		if ($topics) {
			$topicsQuery = implode(" OR ", $topics);
			$topicsQuery = "t.TOPIC_TOP_LEVEL_INDICATOR = 1 AND ($topicsQuery)";
			array_push ($this->where, $topicsQuery);
		}
		if (is_numeric($params["parameters"]["min-id"])) {
			$minId = mysql_real_escape_string($params["parameters"]["min-id"]);
			array_push($this->where, "blog.BLOG_ID = '$minId'");
		}
		if (is_numeric($params["parameters"]["max-id"])) {
			$maxId = mysql_real_escape_string($params["parameters"]["max-id"]);
			array_push($this->where, "blog.BLOG_ID = '$maxId'");
		}
	
		return $this->where;
	}
	
	// Input: list of search queries for a blog search
	// Return: string useful in SORT clause in SQL search, based on input queries
	private function generateBlogSort ($params) {
		$order = "";
		$sortBy = "alphabetical";
		$orderBy = "asc";
		
		if (isset($params["parameters"]["sort"])) {
			$sortBy = $params["parameters"]["sort"];
		}
		if (isset($params["parameters"]["order"])) {
			$orderBy = $params["parameters"]["order"];
		}
		
		// Valid sort and order values, and their columns.
		$sorts = array ("id" => "blog.BLOG_ID","status" => "blog.BLOG_STATUS_ID","alphabetical" => "blog.BLOG_NAME","added-date" => "blog.ADDED_DATE_TIME","crawled-date" => "blog.CRAWLED_DATE_TIME");
		$orders = array("desc" => "DESC","asc" => "ASC");
		
		if (isset($sorts["$sortBy"]) && isset($orders["$orderBy"])) {
			$order = "ORDER BY " . $sorts["$sortBy"] . " " . $orders["$orderBy"];
		} else {
			array_push ($this->errors, "Unknown order: $sortBy $orderBy");
			return;
		}
		
		return $order;
	}
	
	// Input: list of search queries for a post search
	// Return: string useful in FROM clause in SQL search, based on input queries
	private function generatePostFrom ($params) {
	
		$this->from["BLOG_POST post"] = true;
		$this->from["BLOG blog"] = true;
		$this->from["BLOG_AUTHOR author"] = true;
	
		foreach ($params["filters"] as $query) {
			if ($query["name"] === "citation") {
				$searchType = mysql_escape_string($query["modifier"]);
				if (!$searchType)
					$searchType = "id-all";
				
				$this->from["CITATION citation"] = true;
				$this->from["POST_CITATION pc"] = true;
				if ($searchType == "id-all" || $searchType == "doi" || $searchType == "pmid" || $searchType == "arxiv") {
					$this->from["ARTICLE_IDENTIFIER artid"] = true;
				}
				if ($searchType == "article-title" || $searchType == "journal-title") {
					$this->from["ARTICLE art"] = true;
				}
				if ($searchType == "author") {
					$this->from["ARTICLE_AUTHOR artau"] = true;
					$this->from["ARTICLE_AUTHOR_LINK auart"] = true;
				}
			} else if ($query["name"] === "has-citation") {
				if ($query["modifier"]) {
					$this->from["CITATION citation"] = true;
					$this->from["POST_CITATION pc"] = true;
					$this->from["ARTICLE_IDENTIFIER artid"] = true;
				}
				
			} else if ($query["name"] === "group") {
				$this->from["TAG tag"] = true;
				
			} else if ($query["name"] === "topic") {
				$this->from["TAG tag"] = true;
				$this->from["TOPIC t"] = true;
				
			} else if ($query["name"] === "blog") {
				if ($query["modifier"] == "topic") { 
					$this->from["TAG tag"] = true;
					$this->from["TOPIC t"] = true;
				}
				
			} else if ($query["name"] === "author") {
				if ($query["modifier"] == "user-name") {
					$this->from["USER user"] = true;
				}
				
			} else if ($query["name"] === "recommender-status") {
				$this->from["RECOMMENDATION rec"] = true;
				$this->from["USER user"] = true;
				
			} else if ($query["name"] === "recommended-by") {
				$this->from["RECOMMENDATION rec"] = true;
				$this->from["USER user"] = true;
				
			} else if ($query["name"] === "min-recommendations") {
				$this->from["RECOMMENDATION rec"] = true;
				
			} else if ($query["name"] === "is-recommended") {
				if ($query["value"] !== "false")
					$this->from["RECOMMENDATION rec"] = true;
			}
		}
	
		return $this->from;
	
	}
	
	// Input: list of search queries for a post search
	// Return: string useful in WHERE clause in SQL search, based on input queries
	private function generatePostWhere ($params) {
		
		array_push ($this->where, "post.BLOG_POST_STATUS_ID = 0", "blog.BLOG_STATUS_ID = 0", "blog.BLOG_ID = post.BLOG_ID", "blog.BLOG_ID = author.BLOG_ID", "post.BLOG_AUTHOR_ID = author.BLOG_AUTHOR_ID");
		
		$topics = NULL;
		$blogTopics = NULL;
		foreach ($params["filters"] as $query) {
			
			// Escape strings that could be included in the SQL query
			$searchValue = mysql_real_escape_string($query["value"]);
			$searchType = mysql_real_escape_string($query["modifier"]);
			
			if ($query["name"] == "blog") {
				if ($searchType === "title-some") {	
					array_push ($this->where, "blog.BLOG_NAME LIKE '%$searchValue%'");
					
				} elseif ($searchType === "title-all") {
					array_push ($this->where, "blog.BLOG_NAME = '$searchValue'"); 
					
				} elseif ($searchType === "identifier") {
					if (is_numeric($searchValue))
						array_push ($this->where, "blog.BLOG_ID = '$searchValue'");
					else
						array_push ($this->errors, "Identifier value must be numeric: $searchValue");
				} elseif ($searchType === "topic") {
					$blogTopics[] = "t.TOPIC_NAME='$searchValue' AND blog.BLOG_ID = tag.OBJECT_ID AND tag.OBJECT_TYPE_ID = '3' AND tag.TOPIC_ID = t.TOPIC_ID";
				} else {
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				}
				
			} else if ($query["name"] === "group") {
				if (is_numeric($searchValue)) {
					$groupId = $searchValue;
					
					$db = ssDbConnect();
					$groupTags = getTags($groupId, 4, 3, $db);
					$groupTopics = "";
					foreach ($groupTags as $i => $tag) {
						if ($i != 0) {
							$groupTopics .= " OR ";
						}
						$topicId = $tag["topicId"];
						$tagPrivacy = $tag["tagPrivacy"];
						$groupTopics .= "(tag.TOPIC_ID = '$topicId'";
						if ($tagPrivacy) {
							$tagUserId = $tag["userId"];
							$groupTopics .= " AND tag.USER_ID = $tagUserId";
						}
						$groupTopics .= ")";
						
						$this->group = "GROUP BY post.BLOG_POST_ID";
					}
					$group = getGroup($groupId, $db);
					if ($group["groupMatchedPosts"]) {
						$whereArray[] = "(tag.OBJECT_TYPE_ID = '1' AND tag.OBJECT_ID = post.BLOG_POST_ID AND ($groupTopics))";
					}
					if ($group["groupMatchedSitePosts"]) {
						$whereArray[] = "(tag.OBJECT_TYPE_ID = '3' AND tag.OBJECT_ID = post.BLOG_ID AND ($groupTopics))";
					}
					
					array_push ($this->where, implode(" OR ", $whereArray));
				
				} else{ 
					array_push ($this->errors, "Identifier value must be numeric: $searchValue");
				}
				
				if ($searchType)
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else if ($query["name"] === "author") {
				if ($searchType == "user-name") {
					array_push ($this->where, "user.USER_ID = author.USER_ID");
					if($searchValue)
						array_push ($this->where, "user.USER_NAME = '$searchValue'");
				}
				elseif ($searchType == "author-id") {
					if($searchValue)
						array_push ($this->where, "author.BLOG_AUTHOR_ID = '$searchValue'");
					else
						array_push ($this->where, "author.BLOG_AUTHOR_ACCOUNT_NAME <> 'Unknown'");
				}
				elseif ($searchType == "author-name" || empty($searchType)) {
					if($searchValue)
						array_push ($this->where, "author.BLOG_AUTHOR_ACCOUNT_NAME LIKE '%$searchValue%'");
					else
						array_push ($this->where, "author.BLOG_AUTHOR_ACCOUNT_NAME <> 'Unknown'");
				}
				else {
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				}
			
			} else if ($query["name"] === "identifier") {
				if (is_numeric($searchValue))
					array_push ($this->where, "post.BLOG_POST_ID=$searchValue");
				else
					array_push ($this->errors, "Identifier value must be numeric: $searchValue");
				
				if ($searchType)
					array_push ($this->errors, "Unrecognized modifier: $searchType");
			
			} else if ($query["name"] === "topic") {
				$topics[] = "t.TOPIC_NAME='$searchValue' AND t.TOPIC_ID=tag.TOPIC_ID AND post.BLOG_POST_ID = tag.OBJECT_ID AND tag.OBJECT_TYPE_ID = '1'";
				
				if ($searchType)
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else if ($query["name"] === "citation") {
				if ($searchType == "author") {
					array_push ($this->where, "post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","auart.ARTICLE_ID = citation.ARTICLE_ID","auart.ARTICLE_AUTHOR_ID = artau.ARTICLE_AUTHOR_ID","artau.ARTICLE_AUTHOR_FULL_NAME LIKE '%$searchValue%'");
					
				} elseif ($searchType == "article-title") {
					array_push ($this->where, "post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","art.ARTICLE_ID = citation.ARTICLE_ID","art.ARTICLE_TITLE LIKE '%$searchValue%'");
					
				} elseif ($searchType == "journal-title") {
					array_push ($this->where, "post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","art.ARTICLE_ID = citation.ARTICLE_ID","art.ARTICLE_JOURNAL_TITLE LIKE '%$searchValue%'");
					
				} elseif ($searchType == "id-all" || $searchType == "doi" || $searchType == "pmid" || $searchType == "arxiv" || $searchType == "other" || $searchType == NULL) {
					array_push ($this->where, "post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","artid.ARTICLE_IDENTIFIER_TEXT = '$searchValue'","citation.ARTICLE_ID = artid.ARTICLE_ID");
					if ($searchType != NULL && $searchType != "id-all") {
						array_push ($this->where, "artid.ARTICLE_IDENTIFIER_TYPE = '$searchType'");
					}
				} else { 
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				}
				$this->group = "GROUP BY post.BLOG_POST_ID";
				
			} else if ($query["name"] === "has-citation") {
				if ($searchValue == "false")
					array_push ($this->where, "post.BLOG_POST_HAS_CITATION='0'");
				elseif ($searchValue == "true" || $searchValue == NULL)
					array_push ($this->where, "post.BLOG_POST_HAS_CITATION='1'");
				else
					array_push ($this->errors, "Unrecognized value: $searchValue");
				
				if ($searchType === "doi" || $searchType === "pmid" || $searchType === "arxiv" || $searchType === "other") {
					$this->group = "GROUP BY post.BLOG_POST_ID";
					array_push ($this->where, "post.BLOG_POST_ID = pc.BLOG_POST_ID","pc.CITATION_ID = citation.CITATION_ID","citation.ARTICLE_ID = artid.ARTICLE_ID","artid.ARTICLE_IDENTIFIER_TYPE = '$searchType'");
				} elseif ($searchType != "all" && $searchType != NULL && $searchType != "") {
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				}
				
			} else if ($query["name"] === "recommender-status") {
				if ($searchValue == "editor")
					array_push ($this->where,"user.USER_PRIVILEGE_ID > '0'");
				elseif ($searchValue == "user" || $searchValue == NULL)
					array_push ($this->where,"user.USER_PRIVILEGE_ID = '0'");
				else
					array_push ($this->errors, "Unrecognized value: $searchValue");
				
				array_push ($this->where, "post.BLOG_POST_ID = rec.OBJECT_ID","user.USER_ID = rec.USER_ID","rec.OBJECT_TYPE_ID = '1'");
				$this->group = "GROUP BY post.BLOG_POST_ID";
				
				if ($searchType)
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else if ($query["name"] === "recommended-by") {
				array_push ($this->where, "OBJECT_TYPE_ID = '1'","post.BLOG_POST_ID = rec.OBJECT_ID","user.USER_NAME = '$searchValue'","rec.USER_ID = user.USER_ID");
				
				if ($searchType)
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else if ($query["name"] == "title") {
				if ($searchType === "all")
					array_push ($this->where, "post.BLOG_POST_TITLE = '$searchValue'");
				elseif ($searchType === "some" || $searchType == NULL)
					array_push ($this->where, "post.BLOG_POST_TITLE LIKE '%$searchValue%'");
				else
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else if ($query["name"] == "summary") {
				if ($searchType === "all")
					array_push ($this->where, "post.BLOG_POST_SUMMARY = '$searchValue'");
				elseif ($searchType === "some" || $searchType == NULL)
					array_push ($this->where, "post.BLOG_POST_SUMMARY LIKE '%$searchValue%'");
				else
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else if ($query["name"] == "url") {
				if ($searchType === "all")
					array_push ($this->where, "post.BLOG_POST_URI = 'searchValue'");
				elseif ($searchType === "some" || $searchType == NULL)
					array_push ($this->where, "post.BLOG_POST_URI LIKE '%$searchValue%'");
				else
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else if ($query["name"] === "min-recommendations") {
				if (is_numeric($searchValue))
					array_push ($this->where, "rec.OBJECT_TYPE_ID = '1'","post.BLOG_POST_ID = rec.OBJECT_ID");
				else
					array_push ($this->errors, "Minimum recommendations value must be numeric: $searchValue");
				
				$this->count = "HAVING COUNT(rec.OBJECT_ID) >= '$searchValue'";
				$this->group = "GROUP BY post.BLOG_POST_ID";
				
				if ($searchType)
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else if ($query["name"] === "is-recommended") {
				if ($searchValue == "false") {
					array_push ($this->where, "NOT EXISTS (SELECT rec.OBJECT_ID FROM RECOMMENDATION rec WHERE post.BLOG_POST_ID = rec.OBJECT_ID)");
					
				} elseif ($searchValue == "true" || $searchValue == NULL) {
					array_push ($this->where, "post.BLOG_POST_ID = rec.OBJECT_ID");
					$this->group = "GROUP BY post.BLOG_POST_ID";
				} else {
					array_push ($this->errors, "Unrecognized value: $searchValue");
				}
				
				if ($searchType)
					array_push ($this->errors, "Unrecognized modifier: $searchType");
				
			} else {
				array_push ($this->errors, "Unrecognized filter: " . $query["name"]);
			}
		}
		if ($topics) {
			$topicsQuery = implode(" OR ", $topics);
			array_push ($this->where, $topicsQuery);
		}
		if ($blogTopics) {
			if ($topics) 
				array_push ($this->errors, "Search by blog level topics and post level topics at the same time is not supported.");
			
			$blogTopicsQuery = implode(" OR ", $blogTopics);
			$blogTopicsQuery = "t.TOPIC_TOP_LEVEL_INDICATOR = 1 AND ($blogTopicsQuery)";
			array_push ($this->where, $blogTopicsQuery);
		}
		if ($params["parameters"]["show-all"] != "true") {
			array_push($this->where, "post.BLOG_POST_DATE_TIME < NOW()");
		}
		if ($params["parameters"]["min-date"]) {
			$minDate = dateStringToSql($params["parameters"]["min-date"]);
			array_push($this->where, "post.BLOG_POST_DATE_TIME >= '$minDate'");
		}
		if ($params["parameters"]["max-date"]) {
			$maxDate = dateStringToSql($params["parameters"]["max-date"]);
			array_push($this->where, "post.BLOG_POST_DATE_TIME <= '$maxDate'");
		}
		if (is_numeric($params["parameters"]["min-id"])) {
			$minId = mysql_real_escape_string($params["parameters"]["min-id"]);
			array_push($this->where, "post.BLOG_POST_ID >= '$minId'");
		}
		if (is_numeric($params["parameters"]["max-id"])) {
			$maxId = mysql_real_escape_string($params["parameters"]["max-id"]);
			array_push($this->where, "post.BLOG_POST_ID <= '$maxId'");
		}
		
		return $this->where;
	}
	
	// Input: list of search queries for a post search
	// Return: string useful in SORT clause in SQL search, based on input queries
	private function generatePostSort($params) {
		$order = "";
		$sortBy = "publication-date";
		$orderBy = "desc";
		if ($params["parameters"]["sort"]) {
			$sortBy = $params["parameters"]["sort"];
		}
		if ($params["parameters"]["order"]) {
			$orderBy = $params["parameters"]["order"];
		}
		
		// Valid sort and order values, and their columns.
		$sorts = array ("id" => "post.BLOG_POST_ID","alphabetical" => "post.BLOG_POST_TITLE","publication-date" => "post.BLOG_POST_DATE_TIME","added-date" => "post.BLOG_POST_INGEST_DATE_TIME","recommendation-date" => "rec.REC_DATE_TIME","recommendation-count" => "COUNT(rec.REC_DATE_TIME)");
		$orders = array("desc" => "DESC","asc" => "ASC");
		
		if (isset($sorts["$sortBy"]) && isset($orders["$orderBy"])) {
			if ($sortBy == "recommendation-date" || $sortBy == "recommendation-count") {
				$this->group = "GROUP BY post.BLOG_POST_ID";
				unset($this->from["RECOMMENDATION rec"]);
				$this->from["(SELECT * FROM RECOMMENDATION ORDER BY REC_DATE_TIME DESC) rec"] = true;
				array_push ($this->where, "post.BLOG_POST_ID = rec.OBJECT_ID AND OBJECT_TYPE_ID = 1");
			}
			$order = "ORDER BY " . $sorts["$sortBy"] . " " . $orders["$orderBy"];
			
		} else {
			array_push ($this->errors, "Unknown order: $sortBy $orderBy");
		}
		
		return $order;
	}
	
}

// SSFilter object contains search query parameters
// See SS Query API documentation on SS wiki for more information
class SSFilter {
	// Name of filter: topic, title, summary, url, citation, has-citation, recommended-by, recommender-status, min-recommendations
	public $name;

	// Value of filter
	public $value;

	// Modifier for filter (only applicable to title, summary, url filters)
	public $modifier;

	// Type of ID to use (only applicable to citation filter)
	public $idtype;
}

?>