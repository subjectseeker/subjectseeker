<div id="content-wrapper">
	<?php
	$rightSidebar = $currentPage->getLocations("right");
	if (empty($rightSidebar)) {
	?>
  <div id="main-content" style="max-width: none; width: 96%;">
	<?php
	} else {
	?>
	<div id="main-content">
  <?php
	}
	global $currentPage;
	foreach ($currentPage->getLocations("center") as $tab) {
		displayModules($tab["modules"]);
	}
	?>
  </div>
	<?php
	if (!empty($rightSidebar)) {
	?>
	<div id="sidebar">
  	<div class="tabs">
			<?php
			$tabButtons = "";
			foreach ($currentPage->getLocations("right") as $tab) {
				if ($tab["display"] == "true") {
					$button = "<div class='tab-button-pressed'>".$tab["name"]."</div>";
				} else {
					$button = "<div class='tab-button'>".$tab["name"]."</div>";
				}
				$tabButtons .= $button;
			}
			?>
			<div class="tab-buttons">
        <?php echo $tabButtons; ?>
      </div>
			<?php
			foreach ($currentPage->getLocations("right") as $tab) {				
				if ($tab["display"] == "true") {
					echo "<div class='tab-item' style='display: block'>";
				} else {
					echo "<div class='tab-item'>";
				}
				displayModules($tab["modules"]);
				echo "</div>";
			}
			?>
  	</div>
  </div>
	<?php
	}
	?>
</div>