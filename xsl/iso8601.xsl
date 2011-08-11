<?xml version="1.0"?>
<xsl:transform xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  version="1.0">

  <!-- format-date presents the date as an English string. -->
  <xsl:template name="format-date">
    <xsl:param name="year"/>
    <xsl:param name="month"/>
    <xsl:param name="day"/>
    <xsl:param name="hour" select="'0'"/>
    <xsl:param name="minute" select="'0'"/>
    <xsl:param name="second" select="'0'"/>
    <xsl:param name="TZ" select="'0'"/>
    <xsl:param name="format" select="'d Month yyyy'"/>
    <!-- only date templates are implemented; the full default should
         be: -->
    <!-- <xsl:param name="format"
      select="'d Month yyyy at HH:MM:SS TZ'"/> -->

    <!-- Determine the English name of the month. -->
    <xsl:variable name="month-string">
      <xsl:choose>
        <xsl:when test="$month = '01'">
          <xsl:text>January</xsl:text>
        </xsl:when>
        <xsl:when test="$month = '02'">
          <xsl:text>February</xsl:text>
        </xsl:when>
        <xsl:when test="$month = '03'">
          <xsl:text>March</xsl:text>
        </xsl:when>
        <xsl:when test="$month = '04'">
          <xsl:text>April</xsl:text>
        </xsl:when>
        <xsl:when test="$month = '05'">
          <xsl:text>May</xsl:text>
        </xsl:when>
        <xsl:when test="$month = '06'">
          <xsl:text>June</xsl:text>
        </xsl:when>
        <xsl:when test="$month = '07'">
          <xsl:text>July</xsl:text>
        </xsl:when>
        <xsl:when test="$month = '08'">
          <xsl:text>August</xsl:text>
        </xsl:when>
        <xsl:when test="$month = '09'">
          <xsl:text>September</xsl:text>
        </xsl:when>
        <xsl:when test="$month = '10'">
          <xsl:text>October</xsl:text>
        </xsl:when>
        <xsl:when test="$month = '11'">
          <xsl:text>November</xsl:text>
        </xsl:when>
        <xsl:when test="$month = '12'">
          <xsl:text>December</xsl:text>
        </xsl:when>
      </xsl:choose>
    </xsl:variable>

    <!-- Replace any year templates. -->
    <xsl:variable name="output-with-year">
      <xsl:choose>
        <xsl:when test="contains($format, 'yyyy')">
          <xsl:value-of
            select="concat(substring-before($format, 'yyyy'),
                           $year,
                           substring-after($format, 'yyyy'))"/>
        </xsl:when>
        <xsl:when test="contains($format, 'yy')">
          <xsl:value-of
            select="concat(substring-before($format, 'yy'),
                           substring($year, 3),
                           substring-after($format, 'yy'))"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$format"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:variable name="output-with-day">
      <xsl:choose>
        <xsl:when test="contains($output-with-year, 'dd')">
          <xsl:value-of
            select="concat(substring-before($output-with-year, 'dd'),
                           $day,
                           substring-after($output-with-year,
                                           'dd'))"/>
        </xsl:when>
        <xsl:when test="contains($output-with-year, 'd')">
          <xsl:value-of
            select="concat(substring-before($output-with-year, 'd'),
                           string(number($day)),
                           substring-after($output-with-year, 'd'))"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$output-with-year"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <!-- Replace any month templates; do this last, as month names
         contain letters, which may lead to further template
         confusion. -->
    <xsl:variable name="output-with-month">
      <xsl:choose>
        <xsl:when test="contains($output-with-day, 'mm')">
          <xsl:value-of
            select="concat(substring-before($output-with-day, 'mm'),
                           $month,
                           substring-after($output-with-day,
                                           'mm'))"/>
        </xsl:when>
        <xsl:when test="contains($output-with-day, 'm')">
          <xsl:value-of
            select="concat(substring-before($output-with-day, 'm'),
                           string(number($month)),
                           substring-after($output-with-day, 'm'))"/>
        </xsl:when>
        <xsl:when test="contains($output-with-day, 'Month')">
          <xsl:value-of
            select="concat(substring-before($output-with-day,
                                            'Month'),
                           $month-string,
                           substring-after($output-with-day,
                                           'Month'))"/>
        </xsl:when>
        <xsl:when test="contains($output-with-day, 'Mon')">
          <xsl:value-of
            select="concat(substring-before($output-with-day, 'Mon'),
                           substring($month-string, 0, 3),
                           substring-after($output-with-day,
                                           'Mon'))"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$output-with-day"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:value-of select="$output-with-month"/>

    <!-- If there is a time component, add it. -->
    <xsl:if test="$hour">
      <xsl:text> at </xsl:text>

      <!-- Use 24-hour time format; no mucking about with AM and
           PM. -->
      <xsl:value-of select="format-number($hour,'00')"/>
      <xsl:text>:</xsl:text>
      <xsl:value-of select="format-number($minute,'00')"/>
      <xsl:text>:</xsl:text>

      <!-- Use fractional seconds if necessary. -->
      <xsl:value-of select="format-number($second,'00.###')"/>

      <!-- Present the time zone as "GMT" plus or minus an offset. -->
      <xsl:text> GMT</xsl:text>
      <xsl:choose>
        <xsl:when test="$TZ &gt; 0">
          <xsl:text>+</xsl:text>

          <!-- Split the time zone offset back into hours and
               minutes. -->
          <xsl:value-of select="format-number(floor($TZ),'00')"/>
          <xsl:text>:</xsl:text>
          <xsl:value-of select="format-number(($TZ * 60) mod 60,
                        '00')"/>
        </xsl:when>
        <xsl:when test="$TZ &lt; 0">
          <!-- Take the absolute value of the time zone offset. -->
          <xsl:variable name="newTZ" select="-$TZ"/>
          <xsl:text>-</xsl:text>

          <!-- Split the time zone offset back into hours and
               minutes. -->
          <xsl:value-of select="format-number(floor($newTZ),'00')"/>
          <xsl:text>:</xsl:text>
          <xsl:value-of select="format-number(($newTZ * 60) mod 60,
                        '00')"/>
        </xsl:when>
      </xsl:choose>
    </xsl:if>
  </xsl:template>

  <!-- process-date takes one or two dates in unparsed or partially
       parsed form, and a command.  It can currently format the date
       in English or subtract two dates, after the date strings have
       been parsed. -->
  <!-- There is no error checking.  This template assumes that the
       date strings are of the form noted in the DTD, a subset of ISO
       8601. -->
  <xsl:template name="process-date">
    <!-- Parsed structures for the first date. -->
    <xsl:param name="year"/>
    <xsl:param name="month"/>
    <xsl:param name="day"/>
    <xsl:param name="hour"/>
    <xsl:param name="minute"/>
    <xsl:param name="second"/>
    <xsl:param name="TZ"/>
    <!-- The unparsed first string or a remnant thereof. -->
    <xsl:param name="string"/>

    <!-- Parsed structures for the second date. -->
    <xsl:param name="year2"/>
    <xsl:param name="month2"/>
    <xsl:param name="day2"/>
    <xsl:param name="hour2"/>
    <xsl:param name="minute2"/>
    <xsl:param name="second2"/>
    <xsl:param name="TZ2"/>
    <!-- The unparsed second string or a remnant thereof. -->
    <xsl:param name="string2"/>

    <!-- The command string; currently only 'f' and 's' are
         recognized. -->
    <xsl:param name="command"/>

    <!-- The format; used for formatting dates (command 'f'). -->
    <xsl:param name="format"/>

    <xsl:choose>
      <!-- The first string is not completely parsed. -->
      <xsl:when test="$string">
        <xsl:choose>
          <!-- The first year has not yet been found. -->
          <xsl:when test="not($year)">
            <xsl:call-template name="process-date">
              <!-- The year is the first token before a hyphen. -->
              <xsl:with-param name="year"
                              select="substring-before($string,'-')"/>
              <!-- We still need to parse the rest of the string. -->
              <xsl:with-param name="string"
                              select="substring-after($string,'-')"/>
              <!-- Everything else is unchanged. -->
              <xsl:with-param name="year2" select="$year2"/>
              <xsl:with-param name="month2" select="$month2"/>
              <xsl:with-param name="day2" select="$day2"/>
              <xsl:with-param name="hour2" select="$hour2"/>
              <xsl:with-param name="minute2" select="$minute2"/>
              <xsl:with-param name="second2" select="$second2"/>
              <xsl:with-param name="TZ2" select="$TZ2"/>
              <xsl:with-param name="string2" select="$string2"/>
              <xsl:with-param name="command" select="$command"/>
              <xsl:with-param name="format" select="$format"/>
            </xsl:call-template>
          </xsl:when>
          <!-- If we have the year, do we have the month? -->
          <xsl:when test="not($month)">
            <xsl:call-template name="process-date">
              <xsl:with-param name="year" select="$year"/>
              <!-- The month is the first remaining token before a
                   hyphen. -->
              <xsl:with-param name="month"
                              select="substring-before($string,'-')"/>
              <!-- And we still need to parse the rest. -->
              <xsl:with-param name="string"
                              select="substring-after($string,'-')"/>
              <xsl:with-param name="year2" select="$year2"/>
              <xsl:with-param name="month2" select="$month2"/>
              <xsl:with-param name="day2" select="$day2"/>
              <xsl:with-param name="hour2" select="$hour2"/>
              <xsl:with-param name="minute2" select="$minute2"/>
              <xsl:with-param name="second2" select="$second2"/>
              <xsl:with-param name="TZ2" select="$TZ2"/>
              <xsl:with-param name="string2" select="$string2"/>
              <xsl:with-param name="command" select="$command"/>
              <xsl:with-param name="format" select="$format"/>
            </xsl:call-template>
          </xsl:when>
          <!-- We have the year and month, but no day. -->
          <xsl:when test="not($day)">
            <xsl:choose>
              <!-- A time is present in the string. -->
              <xsl:when test="contains($string,'T')">
                <xsl:call-template name="process-date">
                  <xsl:with-param name="year" select="$year"/>
                  <xsl:with-param name="month" select="$month"/>
                  <!-- The day is the part before the T. -->
                  <xsl:with-param name="day"
                                  select="substring-before($string,'T')"/>
                  <!-- We need to parse the rest. -->
                  <xsl:with-param name="string"
                                  select="substring-after($string,'T')"/>
                  <xsl:with-param name="year2" select="$year2"/>
                  <xsl:with-param name="month2" select="$month2"/>
                  <xsl:with-param name="day2" select="$day2"/>
                  <xsl:with-param name="hour2" select="$hour2"/>
                  <xsl:with-param name="minute2" select="$minute2"/>
                  <xsl:with-param name="second2" select="$second2"/>
                  <xsl:with-param name="TZ2" select="$TZ2"/>
                  <xsl:with-param name="string2" select="$string2"/>
                  <xsl:with-param name="command" select="$command"/>
                  <xsl:with-param name="format" select="$format"/>
                </xsl:call-template>
              </xsl:when>
              <!-- The entire remaining string is the day. -->
              <xsl:otherwise>
                <xsl:call-template name="process-date">
                  <xsl:with-param name="year" select="$year"/>
                  <xsl:with-param name="month" select="$month"/>
                  <xsl:with-param name="day" select="$string"/>
                  <xsl:with-param name="year2" select="$year2"/>
                  <xsl:with-param name="month2" select="$month2"/>
                  <xsl:with-param name="day2" select="$day2"/>
                  <xsl:with-param name="hour2" select="$hour2"/>
                  <xsl:with-param name="minute2" select="$minute2"/>
                  <xsl:with-param name="second2" select="$second2"/>
                  <xsl:with-param name="TZ2" select="$TZ2"/>
                  <xsl:with-param name="string2" select="$string2"/>
                  <xsl:with-param name="command" select="$command"/>
                  <xsl:with-param name="format" select="$format"/>
                </xsl:call-template>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:when>
          <!-- There's still a string, but no hour. -->
          <xsl:when test="not($hour)">
            <xsl:call-template name="process-date">
              <xsl:with-param name="year" select="$year"/>
              <xsl:with-param name="month" select="$month"/>
              <xsl:with-param name="day" select="$day"/>
              <!-- Then the hour is the first token before a
                   colon. -->
              <xsl:with-param name="hour"
                              select="substring-before($string,':')"/>
              <!-- And we'll parse the rest. -->
              <xsl:with-param name="string"
                              select="substring-after($string,':')"/>
              <xsl:with-param name="year2" select="$year2"/>
              <xsl:with-param name="month2" select="$month2"/>
              <xsl:with-param name="day2" select="$day2"/>
              <xsl:with-param name="hour2" select="$hour2"/>
              <xsl:with-param name="minute2" select="$minute2"/>
              <xsl:with-param name="second2" select="$second2"/>
              <xsl:with-param name="TZ2" select="$TZ2"/>
              <xsl:with-param name="string2" select="$string2"/>
              <xsl:with-param name="command" select="$command"/>
              <xsl:with-param name="format" select="$format"/>
            </xsl:call-template>
          </xsl:when>
          <!-- A string, but no minute. -->
          <xsl:when test="not($minute)">
            <xsl:choose>
              <!-- The time zone is GMT. -->
              <xsl:when test="contains($string,'Z')">
                <xsl:choose>
                  <!-- There are seconds specified. -->
                  <xsl:when test="contains($string,':')">
                    <xsl:call-template name="process-date">
                      <xsl:with-param name="year" select="$year"/>
                      <xsl:with-param name="month" select="$month"/>
                      <xsl:with-param name="day" select="$day"/>
                      <xsl:with-param name="hour" select="$hour"/>
                      <!-- The minutes are the part before the
                           colon. -->
                      <xsl:with-param name="minute"
                                      select="substring-before(
                                                $string,':')"/>
                      <!-- The seconds are between the colon and the
                           Z. -->
                      <xsl:with-param name="second"
                                      select="substring-after(
                                                substring-before($string,
                                                  'Z'),
                                                ':')"/>
                      <!-- The time zone is zero-offset. -->
                      <xsl:with-param name="TZ" select="'0'"/>
                      <xsl:with-param name="year2" select="$year2"/>
                      <xsl:with-param name="month2" select="$month2"/>
                      <xsl:with-param name="day2" select="$day2"/>
                      <xsl:with-param name="hour2" select="$hour2"/>
                      <xsl:with-param name="minute2"
                                      select="$minute2"/>
                      <xsl:with-param name="second2"
                                      select="$second2"/>
                      <xsl:with-param name="TZ2" select="$TZ2"/>
                      <xsl:with-param name="string2"
                                      select="$string2"/>
                      <xsl:with-param name="command"
                                      select="$command"/>
                      <xsl:with-param name="format" select="$format"/>
                    </xsl:call-template>
                  </xsl:when>
                  <!-- No seconds. -->
                  <xsl:otherwise>
                    <xsl:call-template name="process-date">
                      <xsl:with-param name="year" select="$year"/>
                      <xsl:with-param name="month" select="$month"/>
                      <xsl:with-param name="day" select="$day"/>
                      <xsl:with-param name="hour" select="$hour"/>
                      <!-- The minutes are before the Z. -->
                      <xsl:with-param name="minute"
                                     select="substring-before(
                                               $string,'Z')"/>
                      <!-- No seconds. -->
                      <xsl:with-param name="second" select="'0'"/>
                      <!-- And the time zone is zero-offset. -->
                      <xsl:with-param name="TZ" select="'0'"/>
                      <xsl:with-param name="year2" select="$year2"/>
                      <xsl:with-param name="month2" select="$month2"/>
                      <xsl:with-param name="day2" select="$day2"/>
                      <xsl:with-param name="hour2" select="$hour2"/>
                      <xsl:with-param name="minute2"
                                      select="$minute2"/>
                      <xsl:with-param name="second2"
                                      select="$second2"/>
                      <xsl:with-param name="TZ2" select="$TZ2"/>
                      <xsl:with-param name="string2"
                                      select="$string2"/>
                      <xsl:with-param name="command"
                                      select="$command"/>
                      <xsl:with-param name="format" select="$format"/>
                    </xsl:call-template>
                  </xsl:otherwise>
                </xsl:choose>
              </xsl:when>
              <!-- Not GMT, but a negative offset. -->
              <xsl:when test="contains($string,'-')">
                <!-- $pre-TZ-string has the minutes and maybe
                     seconds. -->
                <xsl:variable name="pre-TZ-string"
                              select="substring-before($string,
                                                       '-')"/>

                <xsl:choose>
                  <!-- There are seconds. -->
                  <xsl:when test="contains($pre-TZ-string,':')">
                    <xsl:call-template name="process-date">
                      <xsl:with-param name="year" select="$year"/>
                      <xsl:with-param name="month" select="$month"/>
                      <xsl:with-param name="day" select="$day"/>
                      <xsl:with-param name="hour" select="$hour"/>
                      <!-- The minutes are the part before the
                           colon. -->
                      <xsl:with-param name="minute"
                                      select="substring-before(
                                                $string,':')"/>
                      <!-- The seconds are after the colon and before
                           the time zone. -->
                      <xsl:with-param name="second"
                                      select="substring-after(
                                                $pre-TZ-string,':')"/>
                      <!-- Send the time zone though one more
                           pass. -->
                      <xsl:with-param name="string"
                                      select="concat('-',
                                                substring-after(
                                                  $string,'-'))"/>
                      <xsl:with-param name="year2" select="$year2"/>
                      <xsl:with-param name="month2" select="$month2"/>
                      <xsl:with-param name="day2" select="$day2"/>
                      <xsl:with-param name="hour2" select="$hour2"/>
                      <xsl:with-param name="minute2"
                                      select="$minute2"/>
                      <xsl:with-param name="second2"
                                      select="$second2"/>
                      <xsl:with-param name="TZ2" select="$TZ2"/>
                      <xsl:with-param name="string2"
                                      select="$string2"/>
                      <xsl:with-param name="command"
                                      select="$command"/>
                      <xsl:with-param name="format" select="$format"/>
                    </xsl:call-template>
                  </xsl:when>
                  <!-- There are no seconds. -->
                  <xsl:otherwise>
                    <xsl:call-template name="process-date">
                      <xsl:with-param name="year" select="$year"/>
                      <xsl:with-param name="month" select="$month"/>
                      <xsl:with-param name="day" select="$day"/>
                      <xsl:with-param name="hour" select="$hour"/>
                      <!-- The minutes are the pre-time-zone
                           string. -->
                      <xsl:with-param name="minute"
                                      select="$pre-TZ-string"/>
                      <!-- There are no seconds. -->
                      <xsl:with-param name="second" select="'0'"/>
                      <!-- The time zone, as an offset in hours, is
                           minus the part of the time zone before the
                           colon (hours) minus the part after the
                           colon (minutes) divided by sixty. -->
                      <xsl:with-param name="TZ"
                                      select="-number(
                                                substring-before(
                                                  substring-after(
                                                    $string,'-'),
                                                  ':')) -
                                              (number(
                                                 substring-after(
                                                   $string,':'))
                                               div 60)"/>
                      <xsl:with-param name="year2" select="$year2"/>
                      <xsl:with-param name="month2" select="$month2"/>
                      <xsl:with-param name="day2" select="$day2"/>
                      <xsl:with-param name="hour2" select="$hour2"/>
                      <xsl:with-param name="minute2"
                                      select="$minute2"/>
                      <xsl:with-param name="second2"
                                      select="$second2"/>
                      <xsl:with-param name="TZ2" select="$TZ2"/>
                      <xsl:with-param name="string2"
                                      select="$string2"/>
                      <xsl:with-param name="command"
                                      select="$command"/>
                      <xsl:with-param name="format" select="$format"/>
                    </xsl:call-template>
                  </xsl:otherwise>
                </xsl:choose>
              </xsl:when>
              <!-- A positive time-zone offset. -->
              <xsl:when test="contains($string,'+')">
                <!-- $pre-TZ-string has the minutes and maybe
                     seconds. -->
                <xsl:variable name="pre-TZ-string"
                              select="substring-before($string,
                                                       '+')"/>
                <xsl:choose>
                  <!-- There are seconds. -->
                  <xsl:when test="contains($pre-TZ-string,':')">
                    <xsl:call-template name="process-date">
                      <xsl:with-param name="year" select="$year"/>
                      <xsl:with-param name="month" select="$month"/>
                      <xsl:with-param name="day" select="$day"/>
                      <xsl:with-param name="hour" select="$hour"/>
                      <!-- The minutes are the part before the
                           colon. -->
                      <xsl:with-param name="minute"
                                      select="substring-before(
                                                $string,':')"/>
                      <!-- The seconds are after the colon and before
                           the time zone. -->
                      <xsl:with-param name="second"
                                      select="substring-after(
                                                $pre-TZ-string,':')"/>
                      <!-- Send the time zone though one more
                           pass. -->
                      <xsl:with-param name="string"
                                      select="concat('+',
                                                substring-after(
                                                  $string,'+'))"/>
                      <xsl:with-param name="year2" select="$year2"/>
                      <xsl:with-param name="month2" select="$month2"/>
                      <xsl:with-param name="day2" select="$day2"/>
                      <xsl:with-param name="hour2" select="$hour2"/>
                      <xsl:with-param name="minute2"
                                      select="$minute2"/>
                      <xsl:with-param name="second2"
                                      select="$second2"/>
                      <xsl:with-param name="TZ2" select="$TZ2"/>
                      <xsl:with-param name="string2"
                                      select="$string2"/>
                      <xsl:with-param name="command"
                                      select="$command"/>
                      <xsl:with-param name="format" select="$format"/>
                    </xsl:call-template>
                  </xsl:when>
                  <!-- There are no seconds. -->
                  <xsl:otherwise>
                    <xsl:call-template name="process-date">
                      <xsl:with-param name="year" select="$year"/>
                      <xsl:with-param name="month" select="$month"/>
                      <xsl:with-param name="day" select="$day"/>
                      <xsl:with-param name="hour" select="$hour"/>
                      <!-- The minutes are the pre-time-zone
                           string. -->
                      <xsl:with-param name="minute"
                                      select="$pre-TZ-string"/>
                      <!-- There are no seconds. -->
                      <xsl:with-param name="second" select="'0'"/>
                      <!-- The time zone, as an offset in hours, is
                           minus the part of the time zone before the
                           colon (hours) minus the part after the
                           colon (minutes) divided by sixty. -->
                      <xsl:with-param name="TZ"
                                      select="number(substring-before(
                                                substring-after(
                                                  $string,'+'),
                                                ':')) +
                                              (number(
                                                 substring-after(
                                                   $string,':'))
                                               div 60)"/>
                      <xsl:with-param name="year2" select="$year2"/>
                      <xsl:with-param name="month2" select="$month2"/>
                      <xsl:with-param name="day2" select="$day2"/>
                      <xsl:with-param name="hour2" select="$hour2"/>
                      <xsl:with-param name="minute2"
                                      select="$minute2"/>
                      <xsl:with-param name="second2"
                                      select="$second2"/>
                      <xsl:with-param name="TZ2" select="$TZ2"/>
                      <xsl:with-param name="string2"
                                      select="$string2"/>
                      <xsl:with-param name="command"
                                      select="$command"/>
                      <xsl:with-param name="format" select="$format"/>
                    </xsl:call-template>
                  </xsl:otherwise>
                </xsl:choose>
              </xsl:when>
            </xsl:choose>
          </xsl:when>
          <!-- We came around again to finish the time zone -->
          <xsl:when test="not($TZ)">
            <xsl:choose>
              <!-- The time zone offset is negative -->
              <xsl:when test="contains($string,'-')">
                <xsl:call-template name="process-date">
                  <xsl:with-param name="year" select="$year"/>
                  <xsl:with-param name="month" select="$month"/>
                  <xsl:with-param name="day" select="$day"/>
                  <xsl:with-param name="hour" select="$hour"/>
                  <xsl:with-param name="minute" select="$minute"/>
                  <xsl:with-param name="second" select="$second"/>
                  <!-- The time zone is always minutes and
                       seconds. -->
                  <xsl:with-param name="TZ"
                                  select="-number(substring-before(
                                            substring-after(
                                              $string,'-'),
                                            ':')) -
                                          (number(
                                             substring-after(
                                               $string,':'))
                                           div 60)"/>
                  <xsl:with-param name="year2" select="$year2"/>
                  <xsl:with-param name="month2" select="$month2"/>
                  <xsl:with-param name="day2" select="$day2"/>
                  <xsl:with-param name="hour2" select="$hour2"/>
                  <xsl:with-param name="minute2" select="$minute2"/>
                  <xsl:with-param name="second2" select="$second2"/>
                  <xsl:with-param name="TZ2" select="$TZ2"/>
                  <xsl:with-param name="string2" select="$string2"/>
                  <xsl:with-param name="command" select="$command"/>
                  <xsl:with-param name="format" select="$format"/>
                </xsl:call-template>
              </xsl:when>
              <!-- The time one offset is positive. -->
              <xsl:when test="contains($string,'+')">
                <xsl:call-template name="process-date">
                  <xsl:with-param name="year" select="$year"/>
                  <xsl:with-param name="month" select="$month"/>
                  <xsl:with-param name="day" select="$day"/>
                  <xsl:with-param name="hour" select="$hour"/>
                  <xsl:with-param name="minute" select="$minute"/>
                  <xsl:with-param name="second" select="$second"/>
                  <!-- The time zone is always minutes and
                       seconds. -->
                  <xsl:with-param name="TZ"
                                  select="number(substring-before(
                                            substring-after(
                                              $string,'+'),
                                            ':')) +
                                          (number(
                                             substring-after(
                                               $string,':'))
                                           div 60)"/>
                  <xsl:with-param name="year2" select="$year2"/>
                  <xsl:with-param name="month2" select="$month2"/>
                  <xsl:with-param name="day2" select="$day2"/>
                  <xsl:with-param name="hour2" select="$hour2"/>
                  <xsl:with-param name="minute2" select="$minute2"/>
                  <xsl:with-param name="second2" select="$second2"/>
                  <xsl:with-param name="TZ2" select="$TZ2"/>
                  <xsl:with-param name="string2" select="$string2"/>
                  <xsl:with-param name="command" select="$command"/>
                  <xsl:with-param name="format" select="$format"/>
                </xsl:call-template>
              </xsl:when>
            </xsl:choose>
          </xsl:when>
        </xsl:choose>
      </xsl:when>

      <!-- The first string has been completely processed. -->
      <xsl:otherwise>
        <xsl:choose>
          <!-- The command is to format the first string and ignore
               the second. -->
          <xsl:when test="$command = 'f'">
            <xsl:call-template name="format-date">
              <!-- Pass the parsed date to the formatter. -->
              <xsl:with-param name="year" select="$year"/>
              <xsl:with-param name="month" select="$month"/>
              <xsl:with-param name="day" select="$day"/>
              <xsl:with-param name="hour" select="$hour"/>
              <xsl:with-param name="minute" select="$minute"/>
              <xsl:with-param name="second" select="$second"/>
              <xsl:with-param name="TZ" select="$TZ"/>
              <xsl:with-param name="format" select="$format"/>
            </xsl:call-template>
          </xsl:when>
          <!-- There's a second string that needs parsing. -->
          <xsl:when test="$string2">
            <xsl:call-template name="process-date">
              <!-- Flip the first and second dates and re-run the date
                   parser. -->
              <xsl:with-param name="string" select="$string2"/>
              <xsl:with-param name="year2" select="$year"/>
              <xsl:with-param name="month2" select="$month"/>
              <xsl:with-param name="day2" select="$day"/>
              <xsl:with-param name="hour2" select="$hour"/>
              <xsl:with-param name="minute2" select="$minute"/>
              <xsl:with-param name="second2" select="$second"/>
              <xsl:with-param name="TZ2" select="$TZ"/>
              <xsl:with-param name="command" select="$command"/>
              <xsl:with-param name="format" select="$format"/>
            </xsl:call-template>
          </xsl:when>
          <!-- Both strings have been processed and the command is to
               subtract them. -->
          <xsl:when test="$command = 's'">
            <xsl:call-template name="subtract-date">
              <!-- Remember that parsing the second date flipped them
                   around.  The first date was the second string.
                   We'll subtract 1 from 2. -->
              <xsl:with-param name="year" select="$year"/>
              <xsl:with-param name="month" select="$month"/>
              <xsl:with-param name="day" select="$day"/>
              <xsl:with-param name="hour" select="$hour"/>
              <xsl:with-param name="minute" select="$minute"/>
              <xsl:with-param name="second" select="$second"/>
              <xsl:with-param name="TZ" select="$TZ"/>
              <xsl:with-param name="year2" select="$year2"/>
              <xsl:with-param name="month2" select="$month2"/>
              <xsl:with-param name="day2" select="$day2"/>
              <xsl:with-param name="hour2" select="$hour2"/>
              <xsl:with-param name="minute2" select="$minute2"/>
              <xsl:with-param name="second2" select="$second2"/>
              <xsl:with-param name="TZ2" select="$TZ2"/>
            </xsl:call-template>
          </xsl:when>
          <!-- No command was given.  Return the unformatted string
               (dd mm yyyy). -->
          <xsl:otherwise>
            <xsl:value-of select="$day"/>
            <xsl:text> </xsl:text>
            <xsl:value-of select="$month"/>
            <xsl:text> </xsl:text>
            <xsl:value-of select="$year"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <!-- subtract-date subtracts the second date structure from the
       first and returns the difference in hours. -->
  <!-- This template recurses.  First it eliminates any difference in
       time, then it rewinds the second date one month at a time,
       adding to a running total of hours.  When the two dates are in
       the same month, it exits with the total difference. -->
  <xsl:template name="subtract-date">
    <xsl:param name="year" select="'0'"/>
    <xsl:param name="month" select="'0'"/>
    <xsl:param name="day" select="'0'"/>
    <xsl:param name="hour" select="'0'"/>
    <xsl:param name="minute" select="'0'"/>
    <xsl:param name="second" select="'0'"/>
    <xsl:param name="TZ" select="'0'"/>
    <xsl:param name="year2" select="'0'"/>
    <xsl:param name="month2" select="'0'"/>
    <xsl:param name="day2" select="'0'"/>
    <xsl:param name="hour2" select="'0'"/>
    <xsl:param name="minute2" select="'0'"/>
    <xsl:param name="second2" select="'0'"/>
    <xsl:param name="TZ2" select="'0'"/>
    <!-- $hour-diff is a running total of the difference between the
         two times, in hours. -->
    <xsl:param name="hour-diff" select="'0'"/>

    <xsl:choose>
      <!-- The times of day are different. -->
      <xsl:when test="$second != $second2 or
                      $minute != $minute2 or
                      $hour != $hour2">
        <!-- Call the template again with the time subtracted. -->
        <xsl:call-template name="subtract-date">
          <xsl:with-param name="year" select="$year"/>
          <xsl:with-param name="month" select="$month"/>
          <xsl:with-param name="day" select="$day"/>
          <xsl:with-param name="year2" select="$year2"/>
          <xsl:with-param name="month2" select="$month2"/>
          <xsl:with-param name="day2" select="$day2"/>
          <!-- The difference in hours based on the times alone; may
               be negative. -->
          <xsl:with-param name="hour-diff"
                          select="$hour2 - $hour +
                                  (($minute2 - $minute +
                                    (($second2 - $second)
                                     div 60.0))
                                   div 60.0)"/>
        </xsl:call-template>
      </xsl:when>
      <!-- There's an existing hour difference, and it's negative. -->
      <xsl:when test="$hour-diff &lt; 0">
        <xsl:call-template name="subtract-date">
          <xsl:with-param name="year" select="$year"/>
          <xsl:with-param name="month" select="$month"/>
          <xsl:with-param name="day" select="$day"/>
          <xsl:with-param name="year2" select="$year2"/>
          <xsl:with-param name="month2" select="$month2"/>
          <!-- Take away one day (may make the day 0)... -->
          <xsl:with-param name="day2" select="$day2 - 1"/>
          <!-- ... and add 24 to the hours.  Carrying! -->
          <xsl:with-param name="hour-diff" select="$hour-diff + 24"/>
        </xsl:call-template>
      </xsl:when>
      <!-- The dates are in the same month. -->
      <xsl:when test="$day != $day2 and $month = $month2
                      and $year = $year2">
        <!-- Exit with the difference in days plus any time
             difference. -->
        <xsl:value-of select="($day2 - $day) * 24 + $hour-diff"/>
      </xsl:when>
      <!-- The dates are in different months. -->
      <xsl:when test="$month != $month2 or $year != $year2">
        <xsl:choose>
          <!-- The second month is January. -->
          <xsl:when test="$month2 = 1">
            <xsl:call-template name="subtract-date">
              <xsl:with-param name="year" select="$year"/>
              <xsl:with-param name="month" select="$month"/>
              <xsl:with-param name="day" select="$day"/>
              <!-- Set the second date to 31 December of the preceding
                   year. -->
              <xsl:with-param name="year2" select="$year2 - 1"/>
              <xsl:with-param name="month2" select="12"/>
              <xsl:with-param name="day2" select="31"/>
              <!-- Add the number of days in January to the
                   subtotal. -->
              <xsl:with-param name="hour-diff"
                              select="$hour-diff + ($day2 * 24)"/>
            </xsl:call-template>
          </xsl:when>
          <!-- The second month is March. -->
          <xsl:when test="$month2 = 3">
            <xsl:choose>
              <!-- It's a leap year! -->
              <xsl:when test="$year mod 4 = 0 and
                               not($year mod 100 = 0 and
                                   not($year mod 400 = 0))">
                <xsl:call-template name="subtract-date">
                  <xsl:with-param name="year" select="$year"/>
                  <xsl:with-param name="month" select="$month"/>
                  <xsl:with-param name="day" select="$day"/>
                  <xsl:with-param name="year2" select="$year2"/>
                  <!-- Set the second date to 29 February. -->
                  <xsl:with-param name="month2" select="$month2 - 1"/>
                  <xsl:with-param name="day2" select="29"/>
                  <!-- Add the number of days in March to the
                       subtotal. -->
                  <xsl:with-param name="hour-diff"
                                  select="$hour-diff + ($day2 * 24)"/>
                </xsl:call-template>
              </xsl:when>
              <!-- Just a boring year. -->
              <xsl:otherwise>
                <xsl:call-template name="subtract-date">
                  <xsl:with-param name="year" select="$year"/>
                  <xsl:with-param name="month" select="$month"/>
                  <xsl:with-param name="day" select="$day"/>
                  <xsl:with-param name="year2" select="$year2"/>
                  <!-- Set the second date to 28 February. -->
                  <xsl:with-param name="month2" select="$month2 - 1"/>
                  <xsl:with-param name="day2" select="28"/>
                  <!-- Add the number of days in March to the
                       subtotal. -->
                  <xsl:with-param name="hour-diff"
                                  select="$hour-diff + ($day2 * 24)"/>
                </xsl:call-template>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:when>
          <!-- The second month is May, July, October, or December -->
          <!-- Thirty days hath September,
               April, June, and November. -->
          <xsl:when test="$month2 = 5 or $month2 = 7 or $month2 = 10
                          or $month2 = 12">
            <xsl:call-template name="subtract-date">
              <xsl:with-param name="year" select="$year"/>
              <xsl:with-param name="month" select="$month"/>
              <xsl:with-param name="day" select="$day"/>
              <xsl:with-param name="year2" select="$year2"/>
              <!-- Set the second date to the 30th of the previous
                   month. -->
              <xsl:with-param name="month2" select="$month2 - 1"/>
              <xsl:with-param name="day2" select="30"/>
              <!-- Add the number of days in the partial month to the
                   subtotal. -->
              <xsl:with-param name="hour-diff"
                              select="$hour-diff + ($day2 * 24)"/>
            </xsl:call-template>
          </xsl:when>
          <!-- All the rest have thirty-one.
               How does that poem end? -->
          <xsl:otherwise>
            <xsl:call-template name="subtract-date">
              <xsl:with-param name="year" select="$year"/>
              <xsl:with-param name="month" select="$month"/>
              <xsl:with-param name="day" select="$day"/>
              <xsl:with-param name="year2" select="$year2"/>
              <!-- Set the second date to the 31st of the previous
                   month. -->
              <xsl:with-param name="month2" select="$month2 - 1"/>
              <xsl:with-param name="day2" select="31"/>
              <!-- Add the number of days in the partial month to the
                   subtotal. -->
              <xsl:with-param name="hour-diff"
                              select="$hour-diff + ($day2 * 24)"/>
            </xsl:call-template>
          </xsl:otherwise>
          <!-- Thirty days hath September;
               All the rest I can't remember.
               There's a calendar there on the wall;
               Why are you asking me at all? -->
        </xsl:choose>
      </xsl:when>
      <!-- The dates are the same. -->
      <xsl:otherwise>
        <xsl:value-of select="$hour-diff"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

</xsl:transform>
