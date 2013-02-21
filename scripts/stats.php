<?php

include_once (dirname(__FILE__)."/../config/globals.php");
include_once (dirname(__FILE__)."/util.php");
include_once(dirname(__FILE__)."/../third-party/pchart/class/pData.class.php");
include_once(dirname(__FILE__)."/../third-party/pchart/class/pDraw.class.php");
include_once(dirname(__FILE__)."/../third-party/pchart/class/pImage.class.php");

$pChartPath = dirname(__FILE__)."/../third-party/pchart";
$fontPath = $pChartPath."/fonts/GeosansLight.ttf";
$db = ssDbConnect();

$days = array();
$dayCounts = array();
for($i = 0; $i < 5; $i++) {
	$timeString = strtotime('-'. $i .' days');
  $days[] = date("M d", $timeString);
	$date = date("Y-m-d", $timeString);
	$dayCounts[] = getDayCount($date, $db);
}
$days = array_reverse($days);
$dayCounts = array_reverse($dayCounts);

/* Create and populate the pData object */ 
$MyData = new pData();
//$MyData->addPoints(array(-4,VOID,VOID,12,8,3),"Probe 1"); 
$MyData->addPoints($dayCounts,"Posts 1"); 
$MyData->setPalette("Posts 1", array('R'=>219, 'G'=>84, 'B'=>84, 'Alpha'=>100));
//$MyData->setSerieTicks("Posts 1",2); 
//$MyData->setSerieWeight("Posts 1",1);
//$MyData->addPoints(array(2,7,5,18,19,22),"Probe 3"); 
//$MyData->setSerieTicks("Probe 2",4); 
$MyData->setAxisName(0,"Posts"); 
//$MyData->setAxisUnit(0,"�C"); 
$MyData->addPoints($days,"Labels"); 
$MyData->setSerieDescription("Labels","Days"); 
$MyData->setAbscissa("Labels"); 

/* Create the pChart object */ 
$myPicture = new pImage(250,170,$MyData, TRUE); 

/* Draw the background */ 
//$Settings = array("R"=>170, "G"=>183, "B"=>87, "Dash"=>1, "DashR"=>190, "DashG"=>203, "DashB"=>107); 
//$myPicture->drawFilledRectangle(0,0,700,230,$Settings); 

/* Overlay with a gradient */ 
//$Settings = array("StartR"=>219, "StartG"=>231, "StartB"=>139, "EndR"=>1, "EndG"=>138, "EndB"=>68, "Alpha"=>50); 
//$myPicture->drawGradientArea(0,0,700,230,DIRECTION_VERTICAL,$Settings); 
//$myPicture->drawGradientArea(0,0,700,20,DIRECTION_VERTICAL,array("StartR"=>0,"StartG"=>0,"StartB"=>0,"EndR"=>50,"EndG"=>50,"EndB"=>50,"Alpha"=>80)); 

/* Add a border to the picture */ 
//$myPicture->drawRectangle(0,0,699,229,array("R"=>0,"G"=>0,"B"=>0)); 

/* Write the picture title */  
//$myPicture->setFontProperties(array("FontName"=>$fontPath,"FontSize"=>12)); 
//$myPicture->drawText(10,13,"drawSplineChart() - draw a spline chart",array("R"=>255,"G"=>255,"B"=>255)); 

/* Write the chart title */  
$myPicture->setFontProperties(array("FontName"=>$fontPath,"FontSize"=>10)); 
//$myPicture->drawText(120,25,"Science on the Web",array("FontSize"=>20,"Align"=>TEXT_ALIGN_BOTTOMMIDDLE)); 

/* Draw the scale and the 1st chart */ 
$myPicture->setGraphArea(40,20,250,150); 
//$myPicture->drawFilledRectangle(60,60,450,190,array("R"=>255,"G"=>255,"B"=>255,"Surrounding"=>-200,"Alpha"=>10)); 
$myPicture->drawScale(array("DrawSubTicks"=>FALSE)); 
//$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>30)); 
$myPicture->setFontProperties(array("FontName"=>$fontPath,"FontSize"=>10)); 
$myPicture->drawSplineChart(array("DisplayValues"=>TRUE,"DisplayColor"=>DISPLAY_AUTO, "DisplayOffset"=>10)); 
$myPicture->drawPlotChart(array("PlotBorder"=>TRUE,"BorderSize"=>1)); 
$myPicture->setShadow(FALSE); 

/* Draw the scale and the 2nd chart */ 
/*$myPicture->setGraphArea(500,60,670,190); 
$myPicture->drawFilledRectangle(500,60,670,190,array("R"=>255,"G"=>255,"B"=>255,"Surrounding"=>-200,"Alpha"=>10)); 
$myPicture->drawScale(array("Pos"=>SCALE_POS_TOPBOTTOM,"DrawSubTicks"=>TRUE)); 
$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10)); 
$myPicture->drawSplineChart(); 
$myPicture->setShadow(FALSE);*/

/* Write the chart legend */ 
//$myPicture->drawLegend(510,205,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL)); 

/* Render the picture (choose the best way) */ 
$myPicture->autoOutput($pChartPath."/examples/pictures/example.drawSplineChart.png"); 

function getDayCount($date, $db) {
	$sql = "SELECT COUNT(BLOG_POST_ID) FROM BLOG_POST WHERE BLOG_POST_STATUS_ID = 0 AND BLOG_POST_DATE_TIME >= '$date 00:00:00' AND BLOG_POST_DATE_TIME <= '$date 23:59:59'";
	//var_dump($sql);
	$result = mysql_query($sql, $db);
	$count = mysql_result($result, 0);
	return $count; 
}

?>
