<?php

global $sitename;
global $pagesConfig;

$pages = array();
$modules = array();
$sections = array("[page-layouts]", "[page-info]", "[navigation-items]", "[sidebar-default]", "[modules]", "[module-titles]");
$pageTitles;
$pageAddresses;
$moduleTitles;


// Open config file for reading
$handle = fopen ($configFile, "r");
if (! $handle) {
	exit("Can't open $configFile\n");
}

// Build list of modules
$section = null;
$line = 0;
while (($data = fgetcsv($handle, 4096, ",", '"')) !== false) {
	$line++;
	
	$firstValue = $data[0];
	if (substr($firstValue, 0, 1) === "#" || strlen($firstValue) == 0) {
		continue;
	}

	// What section of the config file are we in?
	if (substr($firstValue, 0, 1) === "[") {
		if (in_array($firstValue, $sections)) {
			$section = str_replace(array("[","]"), "", $firstValue);
		} else {
			exit ("Unable to parse $configFile line: $firstValue ($line)\n");
		}
	} else {
		// Construct list of objects with info about pages
		if ($section === "page-layouts") {
			// page-id location-on-page module-id
			$pageId = array_shift($data);
			$pageLocation = array_shift ($data);
			$tabIndex = array_shift ($data);
			$tabName = array_shift ($data);
			$tabDisplay = array_shift ($data);
			$modules = $data;
			addPage($pageId, $pageLocation, $tabIndex, $tabName, $tabDisplay, $modules, $pages);
		}
		
		// Set page info.
		elseif ($section === "page-info") {
			// page-id address title
			list ($pageId, $pageAddress, $pageTitle) = $data;
			if (isset ($pages[$pageId])) {
				$pages[$pageId]->setTitle($pageTitle);
				$pages[$pageId]->setAddress($pageAddress);
			} else {
				print "Error: info for unknown page $pageId, line $line\n";
			}
		}

		// Get items of the navigation bar.
		elseif ($section === "navigation-items") {
			// title class address
			$naviItem["address"] = array_shift($data);
			$naviItem["class"] = array_shift($data);
			$naviItem["title"] = array_shift($data);
			$naviItems[] = $naviItem;
		}

		// Construct list of objects with info about modules
		elseif ($section == "modules") {
			// module-id file-name php-function parameters...
			$module;
			// first two args are id and function name
			$moduleId = array_shift($data);
			$module["id"] = $moduleId;
			$module["fileName"] = array_shift($data);
			$module["functionName"] = array_shift($data);
			$module["functionArgs"] = $data;
			$modules[$moduleId] = $module;
		}

		// Add module title to module object
		elseif ($section === "module-titles") {
			// module-id title
			$moduleId = $data[0];
			$modules[$moduleId]["title"] = $data[1];
		}

		else {
			exit ("Unknown section in pages config file: $section\n");
		}
	}
}

// Handle file reading errors
if (!feof($handle)) {
	echo "Error: unexpected fail reading $configFile\n";
}
fclose($handle);

/*
// Sample output
foreach ($pages as $page) {
	print "Page title: " . $page->getTitle() . " at " . $page->getAddress() . "\n";
	foreach ($page->getLocations() as $location => $moduleList) {
		print "On $location: \n";
		foreach ($moduleList as $moduleId) {
			$module = $modules[$moduleId];
			print "	" . $module["functionName"] . " (" . implode(", ", $module["functionArgs"]) . ")\n";
		}
	}
}
*/

//
// FUNCTIONS
//

// Input: page ID, location to place this module on page (right, center...), module ID
// Output: page object, populated with ID and this location/module pair
function addPage($pageId, $location, $tabIndex, $tabName, $tabDisplay, &$moduleIds, &$pages) {

	$page;
	if (isset($pages[$pageId])) {
		$page = $pages[$pageId];
	} else {
		$page = new Page;
		$pages[$pageId] = $page;
	}

	$page->setId($pageId);

	$page->appendLocation($location, $moduleIds, $tabName, $tabIndex, $tabDisplay);
	
	return $page;
}

function displayModules ($moduleList, $noPrint = FALSE) {
	global $modules;
	$output = NULL;
	foreach ($moduleList as $moduleId) {
		$module = $modules[$moduleId];
		if ($module["functionName"] == "localFile") {
			localFile($module);
		}
		// Standalone plugins need to return their output instead of printing directly.
		// TO DO: Determine if this should be done with all plugins and if that uses more RAM.
		else {
			$output .= getPlugin($module, $noPrint);
		}
	}
	return $output;
}

function localFile($module) {
	echo "<div id=\"ss-".$module["id"]."\" class=\"page-block\">";
	if (isset($module["title"])) {
		echo "<h1>".$module["title"]."</h1>";
	}
	include_once(dirname(__FILE__)."/../local/".$module["fileName"]);
	echo "</div>";
}

function getPlugin($module, $noPrint = FALSE) {
	include_once(dirname(__FILE__)."/../plugins/".$module["fileName"]);
	if ($noPrint == TRUE) {
		$output = "<div id=\"ss-".$module["id"]."\" class=\"page-block\">";
		if (isset($module["title"])) {
			$output .= "<h1>".$module["title"]."</h1>";
		}
		$output .= call_user_func_array($module["functionName"], $module["functionArgs"]);
		$output .= "</div>";

		return $output;
	}
	else {
		echo "<div id=\"ss-".$module["id"]."\" class=\"page-block\">";
		if (isset($module["title"])) {
			echo "<h1>".$module["title"]."</h1>";
		}
		call_user_func_array($module["functionName"], $module["functionArgs"]);
		echo "</div>";
	}
}

//
// CLASSES
//

class Page {
	public $id;
	public $locations;
	public $title;
	public $address;

	function __construct() {
		$this->locations = array();
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getTitle() {
		return $this->title;
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getAddress($https = FALSE) {
		global $homeUrl;
		global $httpsEnabled;
		if ($https == TRUE && $httpsEnabled == "true") {
			return str_replace("http:", "https:", $homeUrl . $this->address);
		}
		return $homeUrl . $this->address;
	}

	public function setAddress($address) {
		$this->address = $address;
	}

	public function getLocations($location = NULL) {
		if (empty($location)) {
			return $this->locations;
		} elseif(isset($this->locations["$location"])) {
			return $this->locations["$location"];
		}
	}

	public function appendLocation($location, $moduleIds, $tabName, $tabIndex, $tabDisplay) {
		$this->locations[$location][$tabIndex]["name"] = $tabName;
		$this->locations[$location][$tabIndex]["display"] = $tabDisplay;
		$this->locations[$location][$tabIndex]["modules"] = $moduleIds;
	}
}

?>
