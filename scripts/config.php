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
while (($buffer = fgets($handle, 4096)) !== false) {
	$line++;
	$buffer = trim ($buffer);

	if (substr($buffer, 0, 1) === "#" || strlen($buffer) == 0) {
		continue;
	}

	// What section of the config file are we in?
	if (substr ($buffer, 0, 1) === "[") {
		if (in_array($buffer, $sections)) {
			$section = str_replace(array("[","]"), "", $buffer);
		} else {
			exit ("Unable to parse $configFile line: $buffer ($line)\n");
		}

	} else {

		// Construct list of objects with info about pages
		if ($section === "page-layouts") {
			// page-id location-on-page module-id
			$args = preg_split("/\s+/", $buffer);
			$pageId = array_shift ($args);
			$pageLocation = array_shift ($args);
			addPage($pageId, $pageLocation, $args, $pages);
		} elseif ($section === "page-info") {
			// page-id address title
			list ($pageId, $pageAddress, $pageTitle) = preg_split ("/\s+/", $buffer, 3);
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
			$components = preg_split("/\s+/", $buffer, 3);
			$naviItem["address"] = $components[0];
			$naviItem["class"] = $components[1];
			$naviItem["title"] = $components[2];
			$naviItems[] = $naviItem;
		}
		
		// Get items of the navigation bar.
		elseif ($section === "sidebar-default") {
			// title class address
			list ($pageId, $pageSidebar) = preg_split ("/\s+/", $buffer, 2);
			$pages[$pageId]->setSidebar($pageSidebar);
		}

		// Construct list of objects with info about modules
		elseif ($section == "modules") {
			// module-id file-name php-function parameters...
			$args = preg_split("/\s+/", $buffer);
			$module;
			// first two args are id and function name
			$module["id"] = array_shift($args);
			$moduleId = $module["id"];
			$module["fileName"] = array_shift($args);
			$module["functionName"] = array_shift($args);
			$module["functionArgs"] = $args;
			$modules[$moduleId] = $module;
		}

		// Add module title to module object
		elseif ($section === "module-titles") {
			// module-id title
			$components = preg_split ("/\s+/", $buffer, 2);
			$moduleId = $components[0];
			$modules[$moduleId]["title"] = $components[1];
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
function addPage($pageId, $location, &$moduleIds, &$pages) {

	$page;
	if (isset($pages[$pageId])) {
		$page = $pages[$pageId];
	} else {
		$page = new Page;
		$pages[$pageId] = $page;
	}

	$page->setId($pageId);

	foreach ($moduleIds as $moduleId) {
		$page->appendLocation($location, $moduleId);
	}
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
		elseif ($noPrint == TRUE) {
			$output .= getPlugin($module, TRUE);
		}
		else {
			getPlugin($module);
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
		} else {
			return $this->locations["$location"];
		}
	}
	
	public function setSidebar($sidebar) {
		$this->sidebar = $sidebar;
	}

	public function appendLocation($location, $moduleId) {
		if (!isset($this->locations[$location])) {
			$this->locations[$location] = array();
		}
		array_push ($this->locations[$location], $moduleId);
	}
}

?>
