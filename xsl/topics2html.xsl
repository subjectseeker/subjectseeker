<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

  <xsl:template match="/">
    <ul>
      <xsl:apply-templates select="//topic[@toplevel='true']"/>
    </ul>
  </xsl:template>

  <xsl:template match="topic">
    <li>
      <a>
        <xsl:attribute name="href">
          <xsl:text>/blogs/?type=blog&amp;filter0=topic&amp;value0=</xsl:text>
          <xsl:apply-templates />
        </xsl:attribute>
        <xsl:apply-templates />
      </a>
      <xsl:text> </xsl:text>
      <a>
      <xsl:attribute name="href">
        <xsl:text>/displayfeed/?type=blog&amp;filter0=topic&amp;value0=</xsl:text>
        <xsl:apply-templates />
      </xsl:attribute>
      <span class="postsub">Posts</span>
    </a>
    <xsl:text> </xsl:text>
    <a>
      <xsl:attribute name="href">
        <xsl:text>/subjectseeker/ss-serializer.php?type=blog&amp;filter0=topic&amp;value0=</xsl:text>
        <xsl:apply-templates />
      </xsl:attribute>
      <span class="postsub">Subscribe</span>
    </a>
    </li>
  </xsl:template>
</xsl:stylesheet>

