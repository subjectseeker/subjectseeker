<?xml version="1.0"?>
<xsl:stylesheet
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  version="1.0">

  <xsl:output method="xml" omit-xml-declaration="yes" />

  <xsl:template match="/"><span class="Z3988">
      <xsl:attribute name="title">
<!-- TODO: handle books, etc, not just articles -->
        <xsl:text>ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Ajournal&amp;rft.jtitle=</xsl:text>
        <xsl:call-template name="url-encode"><xsl:with-param name="str" select="//journal_metadata/full_title"/></xsl:call-template>
        <xsl:text>&amp;rft_id=info%3Adoi%2F</xsl:text><xsl:value-of select="//doi_data/doi" />
        <xsl:text>&amp;rfr_id=info%3Asid%2Fscienceseeker.org</xsl:text>
        <xsl:text>&amp;rft.atitle=</xsl:text><xsl:call-template name="url-encode"><xsl:with-param name="str" select="//journal_article/titles/title" /></xsl:call-template>
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
        <span style="font-style:italic;"><xsl:value-of select="//journal_metadata/full_title" />, <xsl:value-of select="//journal_issue/journal_volume/volume" /></span>
          <xsl:text>(</xsl:text>
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
     <xsl:call-template name="url-encode"><xsl:with-param name="str" select="surname" /></xsl:call-template>
     <xsl:text>+</xsl:text>
     <xsl:call-template name="url-encode"><xsl:with-param name="str" select="given_name" /></xsl:call-template>
  </xsl:template>

  <xsl:template match="person_name" mode="text">
     <xsl:value-of select="surname" />, <xsl:value-of select="given_name" />
     <xsl:if test="position()!=last()">&amp;</xsl:if>
     <xsl:if test="position()=last()-1">&amp;</xsl:if>
  </xsl:template>

<!-- URL encoding -->

  <!-- ISO-8859-1 based URL-encoding demo
       Written by Mike J. Brown, mike@skew.org.
       Updated 2002-05-20.

       No license; use freely, but credit me if reproducing in print.

       Also see http://skew.org/xml/misc/URI-i18n/ for a discussion of
       non-ASCII characters in URIs.
  -->

  <xsl:variable name="ascii"> !"#$%&amp;'()*+,-./0123456789:;&lt;=&gt;?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~</xsl:variable>
  <xsl:variable name="latin1"> ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ</xsl:variable>

  <!-- Characters that usually don't need to be escaped -->
  <xsl:variable name="safe">!'()*-.0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz~</xsl:variable>
  <xsl:variable name="hex">0123456789ABCDEF</xsl:variable>

  <xsl:template name="url-encode">
    <xsl:param name="str"/>   
    <xsl:if test="$str">
      <xsl:variable name="first-char" select="substring($str,1,1)"/>
      <xsl:choose>
        <xsl:when test="contains($safe,$first-char)">
          <xsl:value-of select="$first-char"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:variable name="codepoint">
            <xsl:choose>
              <xsl:when test="contains($ascii,$first-char)">
                <xsl:value-of select="string-length(substring-before($ascii,$first-char)) + 32"/>
              </xsl:when>
              <xsl:when test="contains($latin1,$first-char)">
                <xsl:value-of select="string-length(substring-before($latin1,$first-char)) + 160"/>
              </xsl:when>
              <xsl:otherwise>
                <xsl:message terminate="no">Warning: string contains a character that is out of range! Substituting "?".</xsl:message>
                <xsl:text>63</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
        <xsl:variable name="hex-digit1" select="substring($hex,floor($codepoint div 16) + 1,1)"/>
        <xsl:variable name="hex-digit2" select="substring($hex,$codepoint mod 16 + 1,1)"/>
        <xsl:value-of select="concat('%',$hex-digit1,$hex-digit2)"/>
        </xsl:otherwise>
      </xsl:choose>
      <xsl:if test="string-length($str) &gt; 1">
        <xsl:call-template name="url-encode">
          <xsl:with-param name="str" select="substring($str,2)"/>
        </xsl:call-template>
      </xsl:if>
    </xsl:if>
  </xsl:template>

</xsl:stylesheet>
