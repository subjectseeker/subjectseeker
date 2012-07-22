<?php
/*
Plugin Name: SubjectSeeker Generate Citations
Plugin URI: http://scienceseeker.org/
Description: Generate citations for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://scienceseeker.org/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssGetWidget')) {
  class ssGetWidget {
    function ssGetWidget() {
      $this->version = "0.1";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssGetWidget($args) {
        extract($args);
        $options = get_option('widget_ssGetWidget');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssGetWidget();
        echo $after_widget;
      }
      function widget_ssGetWidget_control() {
        $options = get_option('widget_ssGetWidget');
        if ( $_POST['ssGetWidget-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssGetWidget-title']));
          update_option('widget_ssGetWidget', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssGetWidget-title">Title:<input class="widefat" name="ssGetWidget-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssGetWidget-submit" name="ssGetWidget-submit" value="1" />';
      }
      register_sidebar_widget('ssGetWidget', 'widget_ssGetWidget');
      register_widget_control('ssGetWidget', 'widget_ssGetWidget_control');
    }
  }
}

$ssGetWidget = new ssGetWidget();
add_action( 'plugins_loaded', array(&$ssGetWidget, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssGetWidget, 'setupActivation' ));

function get_ssGetWidget($settings = array()) {
	global $ssGetWidget;
 getWidget();
}

// Non-widget functions

function getWidget() {
	global $widgetPage;
	global $apiUrl;
	global $citationUrl;

	print "<p>Share some of the great science posts found around the web with the ScienceSeeker widget. By default, it shows the latest publications, filtered by your filter choices on the sidebar. You may also configure it to filter posts in other ways -- for example, posts with <a href=\"$citationUrl\" title=\"Go to the citation generator\">citations</a> from your site, or posts recommended by a specific ScienceSeeker user.</p>
	
<p>The widget was built using the <a href=\"$apiUrl\" title=\"Go to API documentation page\">ScienceSeeker API</a>, which provides open access to the ScienceSeeker database for use in applications.</p>

<div class=\"margin-bottom\" style=\"overflow: auto;\">
<iframe style=\"border: #BDBDBD solid 1px; float: left;\" src=\"$widgetPage/default/?".$_SERVER['QUERY_STRING']."\" frameborder=\"0\" scrolling=\"no\" height=\"400px\" width=\"150px\"></iframe>
<div style=\"margin-left:20px; float: left; top: 249px; position: relative;\">
<h4>HTML Code</h4>
<textarea onClick=\"this.focus();this.select()\" rows=\"8\" cols=\"40\" readonly=\"readonly\"><iframe style=\"border: #BDBDBD solid 1px;\" src=\"$widgetPage/default/?".$_SERVER['QUERY_STRING']."\" frameborder=\"0\" scrolling=\"no\" height=\"400px\" width=\"150px\"></iframe></textarea>
</div>
</div>

<p>If you are interested in using our widget, here are a few tutorials for the most popular blogging services:</p>
<hr class=\"margin-bottom\" />
<h3 class=\"ss-div-button\">Blogger / Blogspot</h3>
<div class=\"ss-slide-wrapper\">
<p><span class=\"ss-bold\">1.</span> Go to the <span class=\"ss-bold\">Design</span> page on your <span class=\"ss-bold\">Dashboard</span>.</p>
<p><img class=\"aligncenter\" src=\"/images/misc/Blogger-Tutorial-1.jpg\" /></p>

<p><span class=\"ss-bold\">2.</span> Go to the <span class=\"ss-bold\">Layout</span> section of your design page and click on <span class=\"ss-bold\">Add a Gadget</span> where you want to put the ScienceSeeker widget.</p>
<p><img class=\"aligncenter\" src=\"/images/misc/Blogger-Tutorial-2.jpg\" /></p>

<p><span class=\"ss-bold\">3.</span> A pop-up will open with the list of availabe gadgets, select <span class=\"ss-bold\">HTML/Javascript</span>.</p>
<p><img class=\"aligncenter\" width=\"500\" height=\"auto\" src=\"/images/misc/Blogger-Tutorial-3.jpg\" /></p>

<p><span class=\"ss-bold\">4.</span> Write the title you wish your widget to have, copy the HTML code we give you on this page to the content area and click on the <span class=\"ss-bold\">Save</span> button.</p>
<p><img class=\"aligncenter\" src=\"/images/misc/Blogger-Tutorial-4.jpg\" /></p>

<p><span class=\"ss-bold\">5.</span> Back in the layout page, click on <span class=\"ss-bold\">Save arrangement</span>.</p>
<p><img class=\"aligncenter\" src=\"/images/misc/Blogger-Tutorial-5.jpg\" /></p>

<p><span class=\"ss-bold\">6.</span> It's done! You should be able to see the ScienceSeeker widget on your site now.</p>
</div>
<hr class=\"margin-bottom\" />
<h3 class=\"ss-div-button\">WordPress</h3>
<div class=\"ss-slide-wrapper\">
<p class=\"italics\">Note: Unfortunately, WordPress blogs hosted on the WordPress site can't embed iframes</p>
<p><span class=\"ss-bold\">1.</span> Go to your <span class=\"ss-bold\">Dashboard</span>.</p>
<p><img class=\"aligncenter\" src=\"/images/misc/Wordpress-Tutorial-1.jpg\" /></p>

<p><span class=\"ss-bold\">2.</span> Go to <span class=\"ss-bold\">Appearance</span> > <span class=\"ss-bold\">Widgets</span>.</p>
<p><img class=\"aligncenter\" src=\"/images/misc/Wordpress-Tutorial-2.jpg\" /></p>

<p><span class=\"ss-bold\">3.</span> Drag and drop the <span class=\"ss-bold\">Text</span> widget to your sidebar.</p>
<p><img class=\"aligncenter\" src=\"/images/misc/Wordpress-Tutorial-3.jpg\" /></p>

<p><span class=\"ss-bold\">4.</span> Write the title you wish your widget to have, copy the HTML code we give you on this page to the content area and click on the <span class=\"ss-bold\">Save</span> button.</p>
<p><img class=\"aligncenter\" width=\"500\" height=\"auto\" src=\"/images/misc/Wordpress-Tutorial-4.jpg\" /></p>

<p><span class=\"ss-bold\">5.</span> Done! The ScienceSeeker widget should be now in your sidebar.</p>
</div>
<hr class=\"margin-bottom\" />";
}

?>
