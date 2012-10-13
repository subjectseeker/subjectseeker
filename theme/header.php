<div id="notification-area">
<span class="alignright"><span class="close-parent remove-parent">X</span></span><br /><span id="notification-content"></span>
</div>

<div id="header">
  <div id="site-logo">
    <a href="<?php global $pages; echo $pages["home"]->getAddress(); ?>">
      <img src="<?php echo $pages["home"]->getAddress(); ?>/images/logos/rotate/rotate.php" alt="A Random Header Image" />
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