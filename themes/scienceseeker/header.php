<?php
function rotateImageUrl($directory) {
	/*
	By Matt Mullenweg > http://photomatt.net
	Inspired by Dan Benjamin > http://hiveware.com/imagerotator.php
	Latest version always at:
	
	http://photomatt.net/scripts/randomimage
	
	*/// Make this the relative path to the images, like "../img" or "random/images/".
	// If the images are in the same directory, leave it blank.
	$folder = $directory;
	
	// Space seperated list of extensions, you probably won't have to change this.
	$exts = 'jpg jpeg png gif';
	
	$files = array(); $i = -1; // Initialize some variables
	if ('' == $folder) $folder = './';
	
	$handle = opendir($folder);
	$exts = explode(' ', $exts);
	while (false !== ($file = readdir($handle))) {
		foreach($exts as $ext) { // for each extension check the extension
			if (preg_match('/\.'.$ext.'$/i', $file, $test)) { // faster than ereg, case insensitive
				$files[] = $file; // it's good
				++$i;
			}
		}
	}
	closedir($handle); // We're not using it anymore
	mt_srand((double)microtime()*1000000); // seed for PHP < 4.2
	$rand = mt_rand(0, $i); // $i was incremented as we went along
	
	return $files[$rand]; // Voila!
}

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
	<ul id="navigation-list">
  	<?php
    foreach ($naviItems as $naviItem) {
			print "<li class=\"".$naviItem["class"]."\"><a href=\"".$naviItem["address"]."\">".$naviItem["title"]."</a></li>";
		}
		?>
  </ul>
</div>