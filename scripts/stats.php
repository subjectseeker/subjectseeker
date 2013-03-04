<?php

include_once (dirname(__FILE__)."/../config/globals.php");
include_once (dirname(__FILE__)."/util.php");
include_once(dirname(__FILE__)."/../third-party/pchart/class/pData.class.php");
include_once(dirname(__FILE__)."/../third-party/pchart/class/pDraw.class.php");
include_once(dirname(__FILE__)."/../third-party/pchart/class/pImage.class.php");

global $cachedir;
$pChartPath = dirname(__FILE__)."/../third-party/pchart";
$fontPath = $pChartPath."/fonts/GeosansLight.ttf";
$db = ssDbConnect();

$days = array();
$dayCounts = array();
for($i = 0; $i < 8; $i++) {
	$timeString = strtotime('-'. $i .' days');
  $days[] = date("d", $timeString);
	$date = date("Y-m-d", $timeString);
	$dayCounts[] = getDayCount($date, $db);
}
array_shift($days);
array_shift($dayCounts);
$days = array_reverse($days);
$dayCounts = array_reverse($dayCounts);

/* Create and populate the pData object */ 
$MyData = new pData();
$MyData->addPoints($dayCounts,"Posts 1"); 
$MyData->setPalette("Posts 1", array('R'=>219, 'G'=>84, 'B'=>84, 'Alpha'=>100));
$MyData->setAxisName(0,"Posts"); 
$MyData->addPoints($days,"Labels"); 
$MyData->setSerieDescription("Labels","Days"); 
$MyData->setAbscissa("Labels"); 
$MyData->setAbscissaName("Days");

/* Create the pChart object */ 
$myPicture = new pImage(250,185,$MyData, TRUE); 

/* Write the chart title */  
$myPicture->setFontProperties(array("FontName"=>$fontPath,"FontSize"=>10));

/* Draw the scale and the 1st chart */ 
$myPicture->setGraphArea(40,20,250,150);
$myPicture->drawScale(array("DrawSubTicks"=>FALSE)); 
$myPicture->drawSplineChart(array("DisplayValues"=>TRUE,"DisplayColor"=>DISPLAY_AUTO, "DisplayOffset"=>10)); 
$myPicture->drawPlotChart(array("PlotBorder"=>TRUE,"BorderSize"=>0)); 
$myPicture->setShadow(FALSE);

/* Render the picture (choose the best way) */ 
$myPicture->autoOutput($cachedir."/postsoftheday.png"); 

function getDayCount($date, $db) {
	$sql = "SELECT COUNT(BLOG_POST_ID) FROM BLOG_POST WHERE BLOG_POST_STATUS_ID = 0 AND BLOG_POST_DATE_TIME >= '$date 00:00:00' AND BLOG_POST_DATE_TIME <= '$date 23:59:59'";
	//var_dump($sql);
	$result = mysql_query($sql, $db);
	$count = mysql_result($result, 0);
	return $count; 
}

?>
