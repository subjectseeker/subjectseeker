<?php
global $homeUrl;
global $imagedir;
global $imagesUrl;

$imageName = rotateImageUrl($imagedir."/logos/rotate");

?>

<div id="notification-area">
	<div class="close-parent">X</div>
	<div id="notification-content"></div>
</div>

<div id="header">
  <div id="site-logo">
    <a href="<?php  echo $homeUrl ?>">
      <img src="<?php echo $imagesUrl . "/logos/rotate/" . $imageName ?>" alt="A Random Header Image" />
    </a>
  </div>
  <div class="header-search">
  <?php
  getPlugin($modules["search-form"]);	
  ?>
  </div>
  <div class="s740">
	<?php
  getPlugin($modules["user-panel"]);	
  ?>
  </div>
</div>
<div id="navigation-bar">
	<ul id="navigation-list">
  	<?php
    foreach ($naviItems as $naviItem) {
			print "<li class=\"".$naviItem["class"]."\"><a href=\"".$naviItem["address"]."\">".$naviItem["title"]."</a></li>";
		}
		?>
  </ul>
</div>