=== Plugin Name ===
Contributors: ddalessa
Tags: peakhour, cdn, performance, security, caching, image optimisation, seo, ssl, speed
Requires at least: 4.6.2
Tested up to: 6.6.2
Stable tag: trunk
Requires PHP: 5.4 or later
License: GPLv2

Seamlessly integrate wordpress with Peakhour's performance and security service. Peakhour can dramatically improve your
page load times, block threats and lower origin load.

== Description ==

This plugin will automatically flush Peakhour's global CDN when editing posts,pages,media through the wordpress admin.
It also enables manual flushing based on URL patterns from within the wordpress admin.

== Installation ==

= From within wordpress =

1. Log in to your wordpress admin
2. Click on Plugins in the left menu
3. Click on the 'Add New' button at the top of the page
4. Search for 'Peakhour', we should be the first result!
5. Click on 'Install Now'
6. Activate from your installed plugin page
7. Click on Peakhour.io in the left menu

= Then in a new tab/browser window =

1. Log in to your Peakhour account at peakhour.io
2. Click on 'API Keys' in the left menu
3. Enter the name of your domain in the API key name and click 'Create'
4. Copy the generated API key and paste it into the 'Peakhour API key'
5. Go back to your wordpress window and paste the API key into the 'Peakhour API key' field
6. enter your domain name (do not enter www or http:// etc)
7. Click on 'Save Changes' at the bottom of the page
8. Click on the 'Test connection' button that will now appear in the 'Connection Settings' section

= Back in the Peakhour admin, configure cache settings =

1. Click on 'Domains' in the left menu
2. Now click on the 'Manage' button on the right of your domain
3. In the left navigation find CDN -> Settings and click
4. Under the Skip CDN on cookie setting enter 'wordpress_logged_in*', this will cause the cache to be skipped for logged in users. 
5. Tick the 'Ignore requests that invalidate'
6. Towards the bottom of the page find the 'Tag Header Name' setting and enter 'X-Cache-Tags'
7. Next look at the 'Tag Separator' setting and select 'comma'
8. Save the settings.

= Configure Caching rules =

If wordpress is not sending cache control headers, this plugin won't, then caching rules need to be set up. The following are for a standard
wordpress information site. If you have additional functionality, eg woocommerce, then additional configuration will be required.

1. Continuing on from the configure cache settings section and find EDGE -> Rules in the left menu
2. As a safeguard add in three no caching rules.
   2.1 Enter 'php' as the rule name, 'ends_with(http.request.uri.path, ".php")' as the filter, add the setting 'CDN Status' and select disabled. Save
   2.2 Enter 'wp-admin' as the rule name, 'http.request.uri.path eq "/wp-admin/"' as the filter, add the setting 'CDN Status' and select disabled. Save
3. Add in the general caching rule. Enter 'general page caching' as the rule name, 'ends_with(http.request.uri.path, "/")' as the filter, then add:
   CDN Status = enabled
   Strip Outgoing Cookies = enabled
   Strip Cookies from response = enabled
   Ignore Cache Headers = enabled
   Require Cache-Control for caching = disabled
   Browser TTL = don't cache
   CDN TTL = 30 days

   Save

You can of course make the CDN TTL longer or shorter as required.


== Screenshots ==
1. Peakhour administration Tab

== Frequently Asked Questions ==

= Do I need a Peakhour account? =

Yes you will need to sign up for the Peakhour service at https://www.peakhour.io/app/signup/,
all new domains get a free trial

= Do I need to do anything else? =

The service will work out of the box but you will probably want to start caching full pages by setting up
page rules, see our https://www.peakhour.io/support/page-rules/ guide to get started.


== Changelog ==

= 1.0.3 =
*Added*
* Option to exclude home page from post purging.


= 1.0.2 =
*Fixed*
* Manuel purge by single url


= 1.0.0 =
*Added*
* Flushing based on cache tags, people upgrading make sure you configure Cache tags.

= 0.3.2 =
* Support php 5.4

= 0.3.1 =
*Added*
* headers to show the requesting wordpress url and referring page for debugging flush requests.

= 0.3 =
*Added*
* Option to Strip 'ver=' parameters off static resources.

= 0.2 =
*Added*
* Add manual flushing options
* Ability to remove homepage from automatic flushing if your homepage doesn't display posts etc

= 0.1 =
* Flush post/page/category when editing through wordpress admin.
