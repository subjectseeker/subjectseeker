<?php
global $homeUrl;
global $imagedir;
global $imagesUrl;

$imageName = rotateImageUrl($imagedir."/logos/rotate");

?>

<div id="notification-area">
<span class="alignright"><span class="close-parent remove-parent">X</span></span><br /><span id="notification-content"></span>
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
	<div id="site-logo">
    <a href="http://scienceonline.com/">
      <img src="<?php echo $imagesUrl . "/logos/rotate/" . $imageName ?>" alt="ScienceOnline Logo" />
    </a>
  </div>
	<ul id="navigation-list">
  	<?php
    foreach ($naviItems as $naviItem) {
			print "<li class=\"".$naviItem["class"]."\"><a href=\"".$naviItem["address"]."\">".$naviItem["title"]."</a></li>";
		}
		?>
  </ul>
</div>