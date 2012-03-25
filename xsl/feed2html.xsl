<?xml version="1.0"?>
<xsl:stylesheet
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:php="http://php.net/xsl"
  version="1.0"
  exclude-result-prefixes="atom">

  <xsl:include href="iso8601.xsl"/>

  <xsl:output method="html"/>

  <xsl:param name="baseurl" select="'?'"/>
  <xsl:param name="pagesize" select="30"/>
  <xsl:param name="offset" select="0"/>

  <xsl:key name="post-date" match="atom:entry"
    use="substring-before(atom:updated, 'T')"/>

  <xsl:template match="/">
    <xsl:apply-templates/>
  </xsl:template>

  <xsl:template match="atom:author">
    <b>
      <xsl:apply-templates select="atom:name"/>
    </b>
  </xsl:template>

  <xsl:template match="atom:category">
    <a>
      <xsl:if test="parent::atom:source">
        <xsl:attribute name="href">
        	<xsl:value-of select="$baseurl"/>
          <xsl:text>?type=post&amp;filter0=topic&amp;value0=</xsl:text>
          <xsl:value-of select="@term"/>
        </xsl:attribute>
      </xsl:if>
      <xsl:attribute name="title">
        <xsl:if test="not(parent::atom:source)">
          <xsl:text>Soon: </xsl:text>
        </xsl:if>
        <xsl:text>View all posts in </xsl:text>
        <xsl:value-of select="@term"/>
      </xsl:attribute>
      <xsl:value-of select="@term"/>
    </a>
  </xsl:template>

  <xsl:template match="atom:entry">
    <div class="ss-entry-wrapper">
    <div class="data-carrier">
      <xsl:attribute name="id">
        <xsl:value-of select="atom:id"/>
      </xsl:attribute>
      <xsl:attribute name="data-personaId">
        <xsl:value-of select="atom:userpersona"/>
      </xsl:attribute>
        <!-- this generates all tags, blog- and post-specific -->
        <!--
        <xsl:if test="atom:category | atom:source/atom:category">
          <span class="the_tags">
            <xsl:apply-templates select="atom:source/atom:category"/>
            <xsl:for-each select="atom:category">
              <xsl:if
                test="../atom:source/atom:category or
                      not(position() = 1)">
                <xsl:text> | </xsl:text>
              </xsl:if>
              <xsl:apply-templates select="."/>
            </xsl:for-each>
          </span>
          <xsl:text> </xsl:text>
        </xsl:if>
        -->
        <!-- temporarily, we only generate the blog category -->
        <span style="height:0px; width:100%;float: right;"></span>
        <div class="post-extras alignleft">
          <div class="recommendation-wrapper">
            <xsl:if test="atom:recstatus">
              <div class="recommend" id="remove" title="Remove recommendation and note" style="background-image: url(/images/icons/ss-sprite.png); height: 18px; background-position: center -19px; background-repeat: no-repeat;"></div>
            </xsl:if>
            <xsl:if test="not(atom:recstatus)">
              <div class="recommend" id="recommend" title="Recommend" style="background-image: url(/images/icons/ss-sprite.png); height: 18px; background-position: center 0px; background-repeat: no-repeat;"></div>
            </xsl:if>
            <xsl:value-of select="atom:recommendations" />
          </div>
        </div>
        <div style="display: inline-block; width: 94%;">
          <div class="post-header">
            <xsl:apply-templates select="atom:updated" mode="time-only"/>
            <xsl:text> | </xsl:text>
            <span class="ss-postTitle">
              <a href="{atom:link/@href}" target="_blank" rel="bookmark">
                <xsl:attribute name="title">
                  <xsl:text>Permanent link to </xsl:text>
                  <xsl:value-of select="atom:title"/>
                </xsl:attribute>
                <xsl:value-of select="atom:title" disable-output-escaping="yes"/>
                <xsl:if test="atom:title = ''">
                  <xsl:value-of select="atom:link/@href" />
                </xsl:if>
              </a>
            </span>
            <!-- <xsl:apply-templates select="rdf:Description[@rdf:ID='citations']" /> -->
          </div>
          <div class="ss-div-button">
            <div class="arrow-down" title="Show Extra Info"></div>
          </div>
          <div id="post-info" class="ss-slide-wrapper">
            <div id="padding-content" title="Summary">
              <xsl:value-of select="atom:summary" disable-output-escaping="yes"/>
            </div>
            <div class="comments-list-wrapper">
            </div>
            <div class="rec-comment">
              <div class="ss-div-2">
                <div class="text-area">
                  <form method="POST" enctype="multipart/form-data">
                    <span class="subtle-text">Leave a note!</span>
                    <div class="ss-div-2">
                    <textarea class="textArea" name="comment" rows="3" cols="59"></textarea>
                    <span class="alignright"><span class="charsLeft">120</span> characters left.</span>
                    </div>
                    <input id="submit-comment" class="submit-comment ss-button" type="button" data-step="store" value="Submit" />
                  </form>
                  <br />
                </div>
                <div class="comment-notification">
                </div>
                <xsl:if test="atom:userpriv > 0">
                  <div class="toggle-button">Related Image</div>
                  <div class="ss-slide-wrapper">
                    <div class="ss-div-2" id="filter-panel">
                      <p>Please submit your comment before submiting an image.</p>
                      <form method="POST" action="/edit-image/" enctype="multipart/form-data">
                        <input type="hidden" name="postId">
                        <xsl:attribute name="value">
                        <xsl:value-of select="atom:id"/>
                        </xsl:attribute>
                        </input>
                        <div>
                          <div class="alignleft">
                            <h4>Maximum Size</h4>
                            <span class="subtle-text">1 MB</span>
                          </div>
                          <div class="alignleft" style="margin-left: 40px;">
                            <h4>Minimum Width/Height</h4>
                            <span class="subtle-text">580px x 200px</span>
                          </div>
                        </div>
                        <br style="clear: both;" />
                        <div class="ss-div-2"><input type="file" name="image" /> <input class="ss-button" type="submit" value="Upload" /></div>
                      </form>
                    </div>
                  </div>
                </xsl:if>
              </div>
            </div>
          </div>
          <div class="info-post">
            <span class="ss-blogTitle">
              <a href="{atom:source/atom:link[@rel='alternate']/@href}" target="_blank" rel="alternate">
                <xsl:value-of select="atom:source/atom:title" />
              </a>
            </span>
              <span class="alignright">
                <xsl:if test="atom:source/atom:category">
                  <span class="the_tags">
                    <xsl:text>   </xsl:text>
                    <xsl:for-each select="atom:source/atom:category">
                      <xsl:if
                        test="not(position() = 1)">
                        <xsl:text> | </xsl:text>
                      </xsl:if>
                      <xsl:apply-templates select="."/>
                    </xsl:for-each>
                  </span>
                </xsl:if>
                <xsl:text> - </xsl:text>
                <span class="comment-button">
                  <xsl:attribute name="data-number">
                    <xsl:value-of select="atom:commentcount" />
                  </xsl:attribute>
                  <xsl:value-of select="atom:commentcount" /><xsl:text> Note</xsl:text>
                  <xsl:if test="atom:commentcount != '1'">
                    <xsl:text>s</xsl:text>
                  </xsl:if>
                </span>
              </span>
          </div>
        </div>
        <xsl:if test="rdf:Description">
          <div class="badges">
            <xsl:if test="rdf:Description[@rdf:ID='citations']">
              <span class="citation-mark"></span>
            </xsl:if>
            <xsl:if test="rdf:Description[@rdf:ID='editorRecommended']">
              <span class="editors-mark"></span>
            </xsl:if>
            <div id="etiquettes" class="ss-slide-wrapper">
              <xsl:if test="rdf:Description[@rdf:ID='citations']">
                <div class="citation-mark-content" title="Post citing a peer-reviewed source">
                <span>Citation</span>
                </div>
              </xsl:if>
              <xsl:if test="rdf:Description[@rdf:ID='editorRecommended']">
                <div class="editors-mark-content" title="Recommended by our editors">
                <span>Editors' Pick</span>
                </div>
              </xsl:if>
            </div>
          </div>
        </xsl:if>
      </div>
    </div>
  </xsl:template>

  <xsl:template match="rdf:Description">
    <xsl:text> </xsl:text>  
    <span class="citation-mark" title="Post citing a peer-reviewed source"></span>
  </xsl:template>

  <xsl:template match="atom:feed">
    <xsl:choose>
      <xsl:when test="atom:entry">
        <xsl:for-each
          select="atom:entry[count(. |
                                   key('post-date',
                                       substring-before(atom:updated,
                                                        'T'))[1]) = 1]">
          <xsl:sort select="atom:updated" order="descending"/>
          <xsl:apply-templates select="." mode="date-group"/>
        </xsl:for-each>
        <div id="nextprev">
          <div class="alignleft">
            <h4>
              <a title="Next {$pagesize} posts"
                href="{$baseurl}&amp;offset={$offset+$pagesize}&amp;n={$pagesize}">
                <b>
                  <xsl:text>&#xab; Older Entries</xsl:text>
                </b>
              </a>
            </h4>
          </div>
          <div class="alignright">
            <xsl:if test="$offset > 0">
              <h4>
                <a title="Previous {$pagesize} posts">
                  <xsl:attribute name="href">
                    <xsl:value-of select="$baseurl"/>
                    <xsl:text>&amp;offset=</xsl:text>
                    <xsl:choose>
                      <xsl:when test="0 >= $offset - $pagesize">
                        <xsl:text>0</xsl:text>
                      </xsl:when>
                      <xsl:otherwise>
                        <xsl:value-of select="$offset - $pagesize"/>
                      </xsl:otherwise>
                    </xsl:choose>
                    <xsl:text>&amp;n=</xsl:text>
                    <xsl:value-of select="$pagesize"/>
                  </xsl:attribute>
                  <b>
                    <xsl:text>Newer Entries &#xbb;</xsl:text>
                  </b>
                </a>
              </h4>
            </xsl:if>
          </div>
        </div>
      </xsl:when>
      <xsl:otherwise>
        <div class="post">
          <xsl:text>There are no posts in this feed.</xsl:text>
        </div>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="atom:entry" mode="date-group">
    <xsl:variable name="date"
      select="substring-before(atom:updated, 'T')"/>
    <div>
      <h3>
        <xsl:call-template name="process-date">
          <xsl:with-param name="string" select="$date"/>
          <xsl:with-param name="command" select="'f'"/>
          <xsl:with-param name="format" select="'Month d, yyyy'"/>
        </xsl:call-template>
      </h3>
      <xsl:apply-templates select="key('post-date', $date)"/>
    </div>
  </xsl:template>

  <xsl:template match="atom:updated" mode="time-only">
    <xsl:variable name="time" select="substring-after(., 'T')"/>
    <xsl:variable name="hour"
      select="number(substring-before($time, ':'))"/>
    <xsl:variable name="minute">
      <xsl:choose>
        <xsl:when test="contains(substring-after($time, ':'), ':')">
          <xsl:value-of
            select="substring-before(substring-after($time, ':'),
                                     ':')"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="substring-after($time, ':')"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="$hour > 12">
        <xsl:value-of select="$hour - 12"/>
        <xsl:text>:</xsl:text>
        <xsl:value-of select="$minute"/>
        <xsl:text>&#xa0;PM</xsl:text>
      </xsl:when>
      <xsl:when test="$hour = 12">
        <xsl:text>12:</xsl:text>
        <xsl:value-of select="$minute"/>
        <xsl:text>&#xa0;PM</xsl:text>
      </xsl:when>
      <xsl:when test="$hour = 0">
        <xsl:text>12:</xsl:text>
        <xsl:value-of select="$minute"/>
        <xsl:text>&#xa0;AM</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$hour"/>
        <xsl:text>:</xsl:text>
        <xsl:value-of select="$minute"/>
        <xsl:text>&#xa0;AM</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

</xsl:stylesheet>
