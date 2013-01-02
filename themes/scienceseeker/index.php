<?php
include_once(dirname(__FILE__)."/../../scripts/initialize.php");

if ($currentPage->id == "login" || $currentPage->id == "register" || $currentPage->id == "sync" || $currentPage->id == "crop") {
include_once("standalone.php");
}
else {
?>
<!DOCTYPE html>
<html>
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
?>
