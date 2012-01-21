<?xml version="1.0"?>
<xsl:stylesheet
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
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
          <xsl:text>http://scienceseeker.org/displayfeed?type=blog&amp;filter0=topic&amp;value0=</xsl:text>
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
      <div>
        <div class="post-header">
        <xsl:apply-templates select="atom:updated" mode="time-only"/>
        <xsl:text> | </xsl:text>
          <a href="{atom:link/@href}" rel="bookmark">
            <xsl:attribute name="title">
              <xsl:text>Permanent link to </xsl:text>
              <xsl:value-of select="atom:title"/>
            </xsl:attribute>
            <span class="ss-postTitle">
              <xsl:value-of select="atom:title" disable-output-escaping="yes"/>
            </span>
          </a>
        <xsl:apply-templates select="rdf:Description[@rdf:ID='citations']" />
        </div>
        <div class="ss-div-button">
          <div class="arrow-down" title="Show Summary"></div>
       	</div>
      </div>
      <div class="ss-slide-wrapper">
        <span class="ss-summary">
          <div title="Summary" style="padding: 10px;">
          	<xsl:value-of select="atom:summary" disable-output-escaping="yes"/>
          </div>
        </span>
      </div>
      <div class="info-post">
        <span class="ss-blogTitle">
        	<xsl:apply-templates select="atom:source/atom:title"/>
        </span>
        <xsl:if test="atom:source/atom:category">
          <span class="the_tags">
            <xsl:text> </xsl:text>
            <xsl:for-each select="atom:source/atom:category">
              <xsl:if
                test="not(position() = 1)">
                <xsl:text> | </xsl:text>
              </xsl:if>
              <xsl:apply-templates select="."/>
            </xsl:for-each>
           </span>
        </xsl:if>
      </div>
    </div>
  </xsl:template>

  <xsl:template match="rdf:Description">
  <xsl:text> </xsl:text>  
  <span class="citation"><xsl:apply-templates /></span>
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
                    <xsl:text>&#xbb; Newer Entries</xsl:text>
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

  <xsl:template match="atom:source/atom:title">
    <b>
      <xsl:apply-templates/>
    </b>
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
