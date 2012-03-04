<?xml version="1.0"?>
<xsl:stylesheet
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  version="1.0">

  <xsl:output method="xml" omit-xml-declaration="yes" />

  <xsl:template match="/"><span class="Z3988">
      <xsl:attribute name="title">
<!-- TODO: handle books, etc, not just articles -->
        <xsl:text>ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Ajournal&amp;rft.jtitle=</xsl:text>
        <xsl:value-of select="//journal_metadata/full_title"/>
        <xsl:text>&amp;rft_id=info%3Adoi%2F</xsl:text><xsl:value-of select="//doi_data/doi" />
        <xsl:text>&amp;rfr_id=info%3Asid%2Fscienceseeker.org</xsl:text>
        <xsl:text>&amp;rft.atitle=</xsl:text><xsl:value-of select="//journal_article/titles/title" />
        <xsl:text>&amp;rft.issn=</xsl:text><xsl:value-of select="//journal_metadata/issn" />
        <xsl:text>&amp;rft.date=</xsl:text><xsl:value-of select="//journal_issue/publication_date/year" />
        <xsl:text>&amp;rft.volume=</xsl:text><xsl:value-of select="//journal_issue/journal_volume/volume" />
        <xsl:text>&amp;rft.issue=</xsl:text><xsl:value-of select="//journal_issue/issue" />
        <xsl:text>&amp;rft.spage=</xsl:text><xsl:value-of select="//pages/first_page" />
        <xsl:text>&amp;rft.artnum=</xsl:text><xsl:value-of select="//doi_data/resource" />
        <xsl:apply-templates select="//person_name" mode="attr"/>
        <!-- <xsl:text>&amp;rfe_dat=bpr3.included=1;bpr3.tags=Other%2CVeterinary+medicine</xsl:text> -->
      </xsl:attribute>
        <xsl:apply-templates select="//person_name" mode="text"/>
        <xsl:text> (</xsl:text>
        <xsl:value-of select="//journal_issue/publication_date/year" />
        <xsl:text>). </xsl:text>
        <xsl:value-of select="//journal_article/titles/title" />
        <span style="font-style:italic;">, <xsl:value-of select="//journal_metadata/full_title" />, <xsl:value-of select="//journal_issue/journal_volume/volume" /></span>
          <xsl:text> (</xsl:text>
          <xsl:value-of select="//journal_issue/issue" />
          <xsl:text>), </xsl:text>
          <xsl:value-of select="//pages/first_page" />
          <xsl:text> DOI: </xsl:text>
          <a rev="review">
            <xsl:attribute name="href">http://dx.doi.org/<xsl:value-of select="//doi_data/doi" /></xsl:attribute>
            <xsl:value-of select="//doi_data/doi" />
           </a>
        </span>
  </xsl:template>

  <xsl:template match="person_name" mode="attr">
     <xsl:text>&amp;rft.au=</xsl:text>
     <xsl:value-of select="surname" />
     <xsl:text>+</xsl:text>
     <xsl:value-of select="given_name" />
     <xsl:text>&amp;rft.aulast=</xsl:text>
     <xsl:value-of select="surname" />
     <xsl:text>&amp;rft.aufirst=</xsl:text>
     <xsl:value-of select="given_name" />
  </xsl:template>

  <xsl:template match="person_name" mode="text">
     <xsl:value-of select="surname" /><xsl:text> </xsl:text><xsl:value-of select="given_name" />
     <xsl:if test="position()!=last()">, </xsl:if>
  </xsl:template>
  
</xsl:stylesheet>
