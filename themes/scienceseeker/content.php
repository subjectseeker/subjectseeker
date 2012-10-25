<div id="content-wrapper">
  <div id="main-content">
    <?php
		global $currentPage;
    displayModules ($currentPage->getLocations("center"));
		?>
  </div>
	<div id="sidebar">
  	<div class="tabs">
			<?php
      if (!empty($currentPage->sidebar) && $currentPage->sidebar == "right-2") {
      ?>
      <div class="tab-buttons">
        <div class="tab-button">Sidebar</div><div class="tab-button-pressed">Filters</div>
      </div>
      <div class="tab-item">
        <?php
        displayModules ($currentPage->getLocations("right"));
        ?>
      </div>
      <div class="tab-item" style="display: block">
        <?php
        displayModules ($currentPage->getLocations("right-2"));
        ?>
      </div>
      <?php
      }
      else {
      ?>
      <div class="tab-buttons">
        <div class="tab-button-pressed">Sidebar</div><div class="tab-button">Filters</div>
      </div>
      <div class="tab-item" style="display: block">
        <?php
        displayModules ($currentPage->getLocations("right"));
        ?>
      </div>
      <div class="tab-item">
        <?php
        displayModules ($currentPage->getLocations("right-2"));
        ?>
      </div>
      <?php
      }
      ?>
  	</div>
  </div>
</div>