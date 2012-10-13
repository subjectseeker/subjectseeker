<?php
include_once(dirname(__FILE__)."/../scripts/initialize.php");

if (count($currentPage->getLocations()) != 1) {
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php include_once("head.php"); ?>
</head>
<body>
<div id="page-wrapper">
<?php
include_once("header.php");
include_once("content.php");
include_once("footer.php");
?>
</div>
</body>
</html>
<?php
}
else {
include_once("standalone.php");
}
?>
