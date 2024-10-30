=== Blunt GA ===
Contributors: Hube2
Tags: google analytics, tracking, event tracking, link tracking, click to call tracking, download tracking
Requires at least: 2.6
Tested up to: 3.7 
Stable tag: 4.0.0
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=hube02%40earthlink%2enet&lc=US&item_name=Donate%20to%20Blunt%20GA%20WordPress%20Plugin&no_note=0&cn=Add%20special%20instructions%20to%20the%20seller%3a&no_shipping=1&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin is no longer supported. See the description.


== Description ==

I will no longer be supporting this plugin. This plugin supports Classic (or Old) GA. Univiersal Analytics is out of Beta and in a short time Google will require that everyone converts their GA accounts to UA. Everyone should be migrating upgrading their accounts and migrating to the new code.

Please see my previous announcements below. My suggestion is to switch to UA and use Google Tag Manager to do all the event tracking this plugin was doing and more.

Installs GA on site. Event tracking of outbound, email, click to call and document links. Form Tracking. Conversion Tracking. Cross site Tracking.

**Anouncement:**

**With [recent changes](http://analytics.blogspot.com/2013/10/the-rundown-new-products-and-features.html) to the [Google Tag Manager](http://www.google.com/tagmanager/ "Google Tag Manager"), this tool now basically removes the need to have a script like this one that installs GA and addes events to links, forms, and everything else these plugins do. I am currently evaluating my future plans for this plugin.**

**Important Notice: This plugin uses Classic Analytics (ga.js) and not the new version of GA Universal Analytics (analytics.js) currently in public beta. For more information [read this anouncment](http://analytics.blogspot.com/2013/03/expanding-universal-analytics-into.html "Google Analytics anouncment"). If your GA account is set up for the new version then you cannot use this plugin. I have added making this plugin compatable with the new version of GA to my ["To-Do" list](http://wordpress.org/plugins/blunt-ga/other_notes/#To-Do "To-Do List").**

Installs GA on site. Event tracking of outbound, email, click to call and document links. Form Tracking. Conversion Tracking. Cross site Tracking.

Features of Blunt GA include:

* Add Google Analytics to page
* Outbound Link Tracking
* Document Download Tracking
* Email Link Tracking
* Click-to-Call Link Tracking
* Anchor Link (#) Tracking
* Event tracking of 404 pages
* Form Submission Tracking
* Events are added using JavaScript
* Conversion Page Tracking
* Cross Site Tracking and Linking
* All events include actions label to tell you the target of the link and the page where the link was clicked
* Add prefix to URLs to aid in cross site tracking
* All events can be managed and viewed seperately in Google Analytics
* All events can be sent to GA as events or virtual page views
* Tracking can be turned on/off for logged in users based on user roles
* Tracking labels to tell you what page the link or form is located on
* Can turn off tracking on any page, see [Documentation for Developers](http://wordpress.org/plugins/blunt-ga/other_notes/#Documentation-for-Developers)
* Active Support

If you need help setting this plugin or you have any problems setting it up and getting it working, please submit a [Support Request](http://wordpress.org/support/plugin/blunt-ga "Get Help").

**What this plugin does not do:**

* Does not require authentication with Google Analytics
* Does not pull your analytics data into your WP dashboard

[All feedback and questions welcome](http://wordpress.org/support/plugin/blunt-ga "Get Support").


== Installation ==

**Important: You must disable automatic installation of Google Analytics code in any other plugins that includes the ability to do so AND/OR Remove all other GA code from your site/theme/template files. Trying to run multiple installations of Google Analytics will break your tracking and quite possibly your site.**

1. Upload the Blunt GA plugin to the plugin folder of your site
2. Activate it from the Plugins Page
3. Open the settings page and enter your Google Analytics Account # and set other settings. The link to the settings page is located under Settings in the main wp-admin menu

See documentation on settings page for complete instructions on use.


== Screenshots ==

1. Shows location of link to Blunt GA Settings page
2. Shows General Documentation Section
3. Shows Basic Settings Section
4. Shows User Settings Section
5. Shows Outbound Link Tracking Section
6. Shows Mailto Link Tracking Section
7. Shows Click to Call Link Tracking Section
8. Shows Document/File Link Tracking Section
9. Shows Anchor Link Tracking Section
10. Shows 404 Tracking Section
11. Shows Form Submission Tracking Section
12. Shows Conversion Tracking Section
13. Shows Cross Site Tracking Section
14. Shows Cross Site Linking Section
15. Shows Location of Documentation on Wide Screens


== Frequently Asked Questions ==

= Can I run this plugin together with another Google Analytics plugin? =

No, you can't. Installing GA multiple times will break tracking.

= Can I run this plugin and have GA code also manually added to the page? =

No, you can't. Installing GA multiple times will break tracking.

= Why don't I see the Google Analytics installation code when I look at the source code for my site? =

The GA code you get from Google to paste into your pages is for the most basic settings. This code must be altereded a bit for things like cross site tracking and linking and some of the other things that this plugin does. You can read about these changes in the Google Developer documentation. The JavaScript code loaded by this plugin contains the normal GA code plus code that allows for automatic alteration to it depending on your settings. This code loads GA in the same way and at the same time as the basic code, you just can't see it in your page source. To see where the code for this plugin is loaded do a search of you page source for blunt.ga.install.

= When I look at my document source why don't I see any event tracking attached to the links on the page? =

Some of the other plugins available use filters to read through the content of your page and modify links to add tracking code on the server side which is why you can see the events in your source code. This can have the following negative impacts on your site:

1. Increased page load time becuase the page is usually filtered multiple times during the request
2. Increased code ratio
3. More error prone due to Syntax errors, especially those involving single vs double quotes or some special characters within attributes that can break links and other code

This plugin uses JavaScript to add events to links. When the page is loaded (or ready) a JavaScript function is automatically fired that looks for all of the links on your page and adds events based on the URI for the specific link. This has the following benefits:

1. Page load time is not decreased by multiple filters
2. Code ratio is not increased by inline JavaScript.
3. Syntax errors due to different quoting is impossible and syntax errors do to special characters in attribute values are less likely

The only drawback to this method is that there is a very tiny chance that someone will click a link before an event is added to it. However, they'd need to be really, really fast at clicking that link. That or your page load time is extremely long. If they can click that fast or your page load time is extremely long there's a good chance the click wouldn't be counted anyway. If your page load times are extemely slow enough to cause this then you've got bigger problems to worry about than whether or not some events are counted properly.


== Documentation for Developers ==

**Disable the addition of Google Analytics code to page**

You can disable the addition of GA to any page. This feature was requested for use with things like when a page is shown in an iFrame on another site and I thought it could be useful.

To disable tracking, call the following static method:
`
<?php 
  bluntGA::disable();
?>
`
This function must be called before the [wp_enqueue_scripts](http://codex.wordpress.org/Plugin_API/Action_Reference/wp_enqueue_scripts) action fires. See the [Action Reference](http://codex.wordpress.org/Plugin_API/Action_Reference#Actions_Run_During_a_Typical_Request) in the WordPress Codex for more information on hooks and their order. Basically this function can be called anytime before the [get_header](http://codex.wordpress.org/Function_Reference/get_header) function is called. After that point you would need to use the [wp_dequeue_script](http://codex.wordpress.org/Function_Reference/wp_dequeue_script) function. The handle of the Blunt GA JavaScript is **"blunt-ga"**.


== To Do ==

The order of items in this list does not indicate any priority. The list is simply things that I would like to add and I'm not sure any of them will definately be added. The priority is set by what I need for clients over anything else. If there is some feature that you have a specific need for or would like to see added sooner rather than later post a comment in the [support forum](http://wordpress.org/support/plugin/blunt-ga). If it is not listed here already, a link to the documentation for the Google Analytics feature you're looking for would also be appreciated.

* Create a setting to have cross linked domain links not open in a new window when outbound links are set to open in a new window or vice versa, perhaps allow selecting of what cross linked domains open in a new window and which do not. Possibility: two text areas to enter two lists of domain names?
* Add Custom Varaibles [Google Doc](https://developers.google.com/analytics/devguides/collection/gajs/gaTrackingCustomVariables)
* Add search engine configuration [Google Doc](https://developers.google.com/analytics/devguides/collection/gajs/gaTrackingTraffic)
* As part of above, toggle setting for tracking search results/local page as a search engine 
* Move to Analytics.js currently in public beta [Google Doc](https://developers.google.com/analytics/devguides/collection/analyticsjs/) Allow either old or new or both GA versions
* Add ability to select what forms will be tracked vs the all or nothing approach used now, perhaps by the form ID parameter
* Add ability to disable analytics based on visitor IP address
* Add ability to track specific local links as outbound
* Add ability to not track outbound links to specific domains
* Add some type of anonymous reporting so that I can see what features are being used and where I should concentrate efforts for improvement.
* Add Opt-in support?
* Add ability to treat specific URLs as downloads, possibly with regex support.
* Improved Time on Page (ToP) & Time on Site (ToS) metrics: use event tracking to ping every X seconds, with max. Importand information [here](https://developers.google.com/analytics/devguides/collection/gajs/eventTrackerGuide#implementationConsiderations) and [here](https://developers.google.com/analytics/devguides/collection/gajs/limits-quotas)
* Add link counter for links to the same destination so that tacking of the exact link on the page can be determined
* Add additional information to events for form submissions to more accurately define the form that is being submitted


== Changelog ==

= 4.0.0 =
* Removed unused settings
* Removed unused code from JavaScript
* Modified JavaScript to add events on DOMContentLoaded in newer browsers that support it
* Modified to use correct method (wp_enqueue_style) to link admin css file on settings page)
* Modified to correctly register, localize and enqueue JavaScript, this required altering variable names in JavaScript
* Removed debugging from minified JavaScript to reduce size
* Added ability to turn on/off tracking for logged in users by user role
* Added 404 Page Tracking and ability to not track a page views on a 404 page
* Added a static function to allow disabling the addition of GA to a page, see the [Documentation for Developers](http://wordpress.org/plugins/blunt-ga/other_notes/#Documentation-for-Developers) section for more information
* Corrected an error in form event tracking that prevented events from being sent

= 3.2.0 =
* Added a missing file that caused fatal php error in 3.1.0

= 3.1.0 =
* Corrected a problem with event tracking not properly sending events to GA
* Cross Linked Domian links will not open in a new window if outbound links are set to open in a new window.

= 3.0.0 =
* Added Top Level Domain Extractor Class by Artur Barseghyan.
* Added domain setting to basic settings section.
* Altered Cross Site Tracking section to remove domain and renamed to Cross Site Prefix.
* The above changes correct the [flaw cross site tracking](http://wordpress.org/support/topic/faw-in-cross-domian-tracking?replies=1 "Support Topic") to work correctly as per the current Google Analytics documentation.
* Corrected some typos in settings documentation and readme.txt file.
* Fixed bug in Cross Site Linking where domains entered were linked even if linking was disabled.
* Removed warning about tracking conversions as virtual page views at it was no longer valid.
* Corrected a bug in conversion tracking that inserted an incorect label when tracking as a virtual page view.

= 2.0.0 =
* Modified events tracking for form submissions to follow the same format as all other events for consistancy. The action is now the forms action value and the label is the current page URI. This means that events reported in GA will be different for form submissions than they were in previous version.
* Modified event tracking for conversion page tracking to follow the same format as all other events for consistancy. The action of the event is now the conversion page URI and the label is the referring page's URI.
* Separated Cross Site Tracking and Cross Site Linking meta boxes on settings rorm & removed the requirement that cross site tracking be enabled in order to use cross site linking. This corrects the flaw in cross site linking so that one site in a cross site tracking/linking scenario can be set up as the main site.
* Added ability to use the variables %%sitename%% and %%domain%% as cross site prefix value.
* Altered default settings for new installations (outbound, mailto, click to call, document tracking enabled by defualt and altered default category names of all events)
* Corrected a minor bug in activate/update
* Corrected a bug in anchor tracking in combination with cross site tracking, full URL of page was being added instead of the hash value.
* Added less verbose documentation to settings page.
* Removed documentation from readme.txt 
* Modified CSS for settings page.
* Added donate button to settings page.


= 1.2.4 =
* Cleaned up code a bit
* Tested on WordPress version 3.6
* Updated Compatibility
* Cleaned up readme.txt to removed some typos

= 1.2.3 =
* Fixed a bug that caused full JavaScript file to be used rather than the minimized version

= 1.2.2 =
* Added minimized version of JavaScript file, reduced js size by 15K

= 1.2.1 =
* Fixed a bug that caused update to version 1.2.0 to revert all settings to defaults

= 1.2.0 =
* Added Click to Call Tracking
* Notes added to documentation about Click to Call Tracking
* Removed JavaScript Compression

= 1.1.0 = 
* Fixed a bug that caused failure if only anchor tracking was enabled
* Added ability to track anchors as conversion pages
* Turning on anchor tracking now adds anchor hash to all page views
* Notes added to documentation about changes to anchor and conversion tracking

= 1.0.0 =
* initial release as a WordPress Plugin

