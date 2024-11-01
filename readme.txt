=== Plugin Name ===
Contributors: jeff@pyebrook.com
Tags: wp-e-commerce, commerce, transients, database, benchmark, utility
Requires at least: 3.9
Tested up to: 4.01
Stable tag: 4.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: http://www.pyebrook.com

Provides details on your WP-eCommerce and WordPress installation state, issues and performance.

== Description ==

Provides details on your WP-eCommerce and WordPress installation state, issues and performance.

We have provided this plugin in the hope that it will help you identify issues with your WordPress and WP-eCommerce
configuration.


= Checks for the following unfortunate conditions existing on your site: =
* Unreachable links to products, checkout, results and user profile pages.  Checks HTTP and HTTPS.
* Too many options in the WordPress options table
* Too many autoload options in the WordPress options table
* Too much autoload data in the WordPress options table
* Too many transients in the WordPress database
* Expired transients in the WordPress database
* Orphaned WordPress post meta
* Orphaned WordPress taxonomy terms
* Memcache not present
* APC not present
* Object cache not functioning
* Slow un-cached query performance
* Slow cached query performance

= Makes available to the store administrator individual actions that will: =
* Delete all WordPress Transients from the WordPress database
* Delete expired WordPress transients from the WordPress database
* Delete orphaned WordPress post meta
* Delete orphaned WordPress taxonomy meta
* Delete all files that are part of the WordPress cache
* Flush the WordPress cache
* Test the configuration of memcache object cache
* Initiate a memcache (object cache) flush


== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the WordPress Dashboard, on the tools menu select "Store Check-Up"

== Frequently Asked Questions ==

= Where can I get support =

www.pyebrook.com or info@pyebrook.com


== Screenshots ==

1. Easy to view single screen showing the status of your WP-eCommerce installation
2. Open the status screen from the WordPress Dashboard Tools Menu, Select "Store Check-Up"
3. WP-eCommerce Status, Statistics and Actions
4. WordPress Status, Statistics and Actions
5. WordPress Database Status, Statistics and Actions

== Changelog ==

= 4.0 =
* WP-eCommerce required page link checks
* Misc bug fixes
* Testing for WordPress 4.1
* Screenshots
* About page


= 3.0 =
* more database features


= 2.0 =
* more database features
* database performance benchmarking

= 1.0 =
* initial release and beta test

== Upgrade Notice ==

= 4.0 =
No issues overwriting a previous version.
