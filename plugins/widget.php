<?php

/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function displayWidgetPage () {

global $sitename;
global $pages;

?>
<p>Share some of the great posts found around the web with the <? echo $sitename ?> widget. By default, it shows the latest publications, filtered by your filter choices on the sidebar. You may also configure it to filter posts in other ways -- for example, posts with <a href="<? echo $pages["generate-citations"]->getAddress(); ?>" title="Go to the citation generator">citations</a> from your site, or posts recommended by a specific <? echo $sitename ?> user.</p>
	
<p>The widget was built using the <a href="<? echo $pages["api"]->getAddress(); ?>" title="Go to API documentation page"><? echo $sitename ?> API</a>, which provides open access to the <? echo $sitename ?> database for use in applications.</p>

<div class="margin-bottom" style="overflow: auto;">
<iframe style="border: #BDBDBD solid 1px; float: left;" src="<? echo $pages["widget"]->getAddress(); ?>/default/?<? echo $_SERVER['QUERY_STRING'] ?>" frameborder="0" scrolling="no" height="400px" width="150px"></iframe>
<div style="margin-left:20px; float: left; top: 222px; position: relative;">
<h4>HTML Code</h4>
<textarea onClick="this.focus();this.select()" rows="8" cols="40" readonly="readonly"><iframe style="border: #BDBDBD solid 1px;" src="<? echo $pages["widget"]->getAddress(); ?>/default/?<? echo $_SERVER['QUERY_STRING'] ?>" frameborder="0" scrolling="no" height="400px" width="150px"></iframe></textarea>
</div>
</div>

<p>If you are interested in using our widget, here are a few tutorials for the most popular blogging services:</p>
<div class="entries">
<div class="ss-entry-wrapper">
<h3 style="margin: 0px;">Blogger / Blogspot</h3>
<div class="ss-slide-wrapper">
<br />
<p><span class="ss-bold">1.</span> Go to the <span class="ss-bold">Design</span> page on your <span class="ss-bold">Dashboard</span>.</p>
<p><img class="aligncenter" src="/images/misc/Blogger-Tutorial-1.jpg" /></p>

<p><span class="ss-bold">2.</span> Go to the <span class="ss-bold">Layout</span> section of your design page and click on <span class="ss-bold">Add a Gadget</span> where you want to put the <? echo $sitename ?> widget.</p>
<p><img class="aligncenter" src="/images/misc/Blogger-Tutorial-2.jpg" /></p>

<p><span class="ss-bold">3.</span> A pop-up will open with the list of availabe gadgets, select <span class="ss-bold">HTML/Javascript</span>.</p>
<p><img class="aligncenter" width="500" height="auto" src="/images/misc/Blogger-Tutorial-3.jpg" /></p>

<p><span class="ss-bold">4.</span> Write the title you wish your widget to have, copy the HTML code we give you on this page to the content area and click on the <span class="ss-bold">Save</span> button.</p>
<p><img class="aligncenter" src="/images/misc/Blogger-Tutorial-4.jpg" /></p>

<p><span class="ss-bold">5.</span> Back in the layout page, click on <span class="ss-bold">Save arrangement</span>.</p>
<p><img class="aligncenter" src="/images/misc/Blogger-Tutorial-5.jpg" /></p>

<p><span class="ss-bold">6.</span> It's done! You should be able to see the <? echo $sitename ?> widget on your site now.</p>
</div>
</div>
<div class="ss-entry-wrapper">
<h3 style="margin: 0px;">WordPress</h3>
<div class="ss-slide-wrapper">
<br />
<p class="italics">Note: Unfortunately, WordPress blogs hosted on the WordPress site can't embed iframes</p>
<p><span class="ss-bold">1.</span> Go to your <span class="ss-bold">Dashboard</span>.</p>
<p><img class="aligncenter" src="/images/misc/Wordpress-Tutorial-1.jpg" /></p>

<p><span class="ss-bold">2.</span> Go to <span class="ss-bold">Appearance</span> > <span class="ss-bold">Widgets</span>.</p>
<p><img class="aligncenter" src="/images/misc/Wordpress-Tutorial-2.jpg" /></p>

<p><span class="ss-bold">3.</span> Drag and drop the <span class="ss-bold">Text</span> widget to your sidebar.</p>
<p><img class="aligncenter" src="/images/misc/Wordpress-Tutorial-3.jpg" /></p>

<p><span class="ss-bold">4.</span> Write the title you wish your widget to have, copy the HTML code we give you on this page to the content area and click on the <span class="ss-bold">Save</span> button.</p>
<p><img class="aligncenter" width="500" height="auto" src="/images/misc/Wordpress-Tutorial-4.jpg" /></p>

<p><span class="ss-bold">5.</span> Done! The <? echo $sitename ?> widget should be now in your sidebar.</p>
</div>
</div>
</div>
<?php
}
?>