<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

  <xsl:param name="topics" select="''"/>

  <xsl:template match="/">
    <h2>
      <xsl:text>ScienceSeeker: Blogs</xsl:text>
      <xsl:if test="$topics">
        <xsl:text> on </xsl:text>
        <xsl:call-template name="topics-list">
          <xsl:with-param name="list" select="$topics"/>
        </xsl:call-template>
      </xsl:if>
    </h2>
    <xsl:apply-templates/>
  </xsl:template>

  <xsl:template match="message">
    <b>
      <xsl:apply-templates/>
    </b>
  </xsl:template>

  <xsl:template match="blogs">
    <ul>
      <xsl:apply-templates/>
    </ul>
  </xsl:template>

  <xsl:template match="blog">
    <li>
      <a>
        <xsl:attribute name="href">
          <xsl:value-of select="uri"/>
        </xsl:attribute>
        <xsl:apply-templates select="name"/>
      </a>
      <!-- UNCOMMENTME when we are displaying descriptions <xsl:apply-templates select="description"/> -->
      <xsl:text> [&#160;</xsl:text>
      <a>
        <xsl:attribute name="href">
          <xsl:value-of select="syndicationuri"/>
        </xsl:attribute>
        <xsl:text>Syndication</xsl:text>
      </a>
      <xsl:text>&#160;] [&#160;</xsl:text>
      <a>
        <xsl:attribute name="href">
          <xsl:text>/claimblog/?blogId=</xsl:text>
          <xsl:value-of select="id"/>
        </xsl:attribute>
        <xsl:text>Claim this blog</xsl:text>
      </a>
      <xsl:text>&#160;]</xsl:text>
    </li>
  </xsl:template>

  <xsl:template name="topics-list">
    <xsl:param name="list" select="''"/>
    <xsl:param name="comma" select="false()"/>
    <xsl:param name="and" select="false()"/>
    <xsl:variable name="find-and"
      select="$and or contains($list, '|')"/>
    <xsl:variable name="find-comma"
      select="$comma or
              ($find-and and
               contains(substring-after($list, '|'), '|'))"/>
    <xsl:choose>
      <xsl:when test="not(contains($list, '|'))">
        <xsl:if test="$and">
          <xsl:text> and </xsl:text>
        </xsl:if>
        <xsl:value-of select="$list"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="substring-before($list, '|')"/>
        <xsl:if test="$find-comma">
          <xsl:text>, </xsl:text>
        </xsl:if>
        <xsl:call-template name="topics-list">
          <xsl:with-param name="list"
             select="substring-after($list, '|')"/>
          <xsl:with-param name="comma" select="$find-comma"/>
          <xsl:with-param name="and" select="$find-and"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="description">
    <xsl:text>: </xsl:text>
    <xsl:apply-templates/>
  </xsl:template>


</xsl:stylesheet>
