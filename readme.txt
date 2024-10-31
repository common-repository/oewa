=== ÖWA ===
Contributors: jjchinquist
Donate link: http://service.ots.at/en/distribute-press-releases/austria/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Tags: seo, analytics, statistics, tracking, öwa
Requires at least: 4.0
Tested up to: 4.7.3
Stable tag: 1.4.4

Statistic tool for websites that target the Austrian market which are relevant for marketing, PR and
advertising companies.

== Special Thanks ==

Thank you to Marco Heinrichs at ÖWA for sharing his started plugin and his expertise.
Thank you to the APA-OTS for sponsoring time for the development and maintenance of the ÖWA WorsPress Plugin.

== Description ==

For information regarding the Österreichische Webanalyse (ÖWA) organisation, please see their mission statment and
description that is published here: [http://www.oewa.at/organisation](http://www.oewa.at/organisation).

== Disclaimer ==

Although the WordPress ÖWA Plugin is licensed under the GPLv3, the ÖWA javascript, ÖWA logo and ÖWA account data is
owned and managed by the ÖWA Verein/Association. See their [Terms and Conditions](http://www.oewa.at/) 
for more information.

The ÖWA regularly conducts audits of websites that use their service. They will penalize websites that fail
to comply to proper categorization of the website (for more information see their website). 
It is therefore important that this plugin work properly.
Please report any issues you have for the plugin, and help us to fix those issues in a timely manner. 
Neither the ÖWA nor the APA and the authors of this plugin can be held responsible for
incorrect categorization. You may also report issues to the ÖWA directly. 

== Further Reading ==

* [ÖWA Website](http://www.oewa.at/)
* [Verein](http://www.oewa.at/organisation)
* [Published Reports](http://www.oewa.at/basic/online-angebote)

== Installation ==

1. Use the administration page to download and install the ÖWA plugin.
2. Activate the ÖWA plugin through the 'Plugins' menu in WordPress.
3. Configure the plugin by going to the `ÖWA` menu that appears in your admin menu.
4. Every post may have a custom category as well (but hopefully the path patterns will be enough).
5. Visit your start page and search for the 2 JavaScript code snippets. "var OEWA" in the HEAD tag and "oewaconfig = " before the end of the BODY tag.

== Upgrade Notice ==
The first public version of the ÖWA Plugin is 1.4.3.

== Frequently Asked Questions ==

[FAQ on the ÖWA website](http://www.oewa.at/basic/faq).

== Screenshots ==

See screenshot.png

== Changelog ==

= 1.4.4 =

Release Date: March 29, 2017

* Features:
    * Tested up to WP 4.7.3
    
* Bugfixes:
    * none

= 1.4.3 =

Release Date: June 7th, 2016

* Features:
    * Prepared for public release
    
* Bugfixes:
    * Updated all links to the new ÖWA website

= 1.4.2 =

Release Date: June 6th, 2016

* Features:
    * There are no new features
    
* Bugfixes:
    * During testing some minor improvements were made to the testing form.

= 1.4.1 =

Release Date: June 5th, 2016

* Features:
    * Translations
    * Added a testing form (we do not yet have tests, so this is the next best thing)
    * Menu item moved to top level
    
* Bugfixes:
    * Several form input fields had an incorrect size value
    * Closed an unclosed DIV in the administration section

= 1.4.0 =

Release Date: April 21st, 2016

* Features:
    * Updated to ÖWA code version 2.0.1
    * New setting "I am an ÖWA Plus account"

* Bugfixes:
    * updated the add_option function calls. Deprecated parameters were removed.
    * UI text update for the ÖWA widget in the post edit form

= 1.3.1 =

Release Date: April 14th, 2016

* Features:
    * Published Plugin

* Bugfixes:

= 1.2.0 =

Release Date: April 13th, 2016

* Features:
    * Path to category mapping was introduced so sections of the
      website could receive different categories than the default category

* Bugfixes:
    * none

= 1.1.0 =

Release Date: April 11th, 2016

* Features:
    * New setting to allow or hide the ÖWA Umfrage (a survey) for marketing research
    * Move the oewa_footer.js script code into the PHP function (it must now be dynamic)
    * Delete the original oewa_footer.js file (and folder)

* Bugfixes:
    * footer_is_initialized was not set in the OEWA class footer function

= 1.0.0 =

Release Date: February 25th, 2016

* Features:
	* Administration page for setting ÖWA code parameters site wide
	* A single category may be set for the entire website (this will change)

* Bugfixes:
	* none