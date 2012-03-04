<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

  <xsl:template match="/">
    <ul>
    	<li><input id="filter" class="categories" type="checkbox" name="category" value="citation" /> Citations</li>
      <li><input id="filter" class="categories" type="checkbox" name="category" value="editorsPicks" /> Editors' Picks</li>
			<br />
      <xsl:apply-templates select="//topic[@toplevel='true']"/>
    </ul>
  </xsl:template>

  <xsl:template match="topic">
    <li>
    	<input id="category" class="categories" type="checkbox" name="category">
        <xsl:attribute name="value">
            <xsl:apply-templates />
        </xsl:attribute>
      </input>
      <xsl:text> </xsl:text>
      <a>
        <xsl:attribute name="href">
          <xsl:text>/blogs/?type=blog&amp;filter0=topic&amp;value0=</xsl:text>
          <xsl:apply-templates />
        </xsl:attribute>
        <xsl:apply-templates />
      </a>
    </li>
  </xsl:template>
</xsl:stylesheet>

