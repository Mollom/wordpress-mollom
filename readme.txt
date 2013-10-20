=== Mollom ===
Contributors: Netsensei, tha_sun
Donate link: https://mollom.com/pricing
Tags: comments, spam, social, content, moderation, captcha, mollom
Requires at least: 3.1.0
Tested up to: 3.5.1
Stable tag: 2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Mollom protects your site from spam, profanity, and unwanted posts.  Focus on public and social engagement.  Focus on things that matter.

== Description ==

[Mollom](http://mollom.com) protects your site from spam and unwanted posts.  Mollom enables you to focus on quality content, and to embrace user-contributed content and public engagement.

Mollom automatically blocks all spam, accepts the content you want, and honestly admits when it is _unsure_ — asking the author to solve a [CAPTCHA](http://en.wikipedia.org/wiki/CAPTCHA).  Obvious spam does not even enter your site; it's outright discarded instead.  To learn more, check [how Mollom works](http://mollom.com/how-mollom-works).

The Mollom service encompasses all spam filtering techniques that exist, using industry-leading content classification technologies that learn and automatically adapt, and is constantly monitored and improved by engineers — to allow you to focus on content:  Quality content.

Mollom is actively used by more than 50,000 sites, including Sony, twitter, MIT, Adobe, Warner Bros Records, LinuxJournal, NBC, and many others.  More than 4,500,000 posts are checked by Mollom _per day_.

= Features =

* Protects the comment form. (support for other core forms to follow soon)
* Checks for spam and profanity.
* Allows selected user roles to bypass the protection.
* Integrates with WordPress' built-in comment moderation pages.

Do you have multiple WordPress and other sites that need moderation?  Mollom's [Content Moderation Platform](http://mollom.com/moderation) is supported out of the box — Save time & moderate them all at once.

If you like Mollom, consider to [write a review](http://wordpress.org/support/view/plugin-reviews/mollom) and blog about it! :)

= Support =

To get the best performance out of Mollom, ensure to disable other spam filter plugins.

* Contact [Mollom Support](http://mollom.com/contact) for issues pertaining to the Mollom service; e.g., uncaught spam posts, inappropriately classified posts, etc.
* Use the [issue queue](https://github.com/Mollom/wordpress-mollom/issues) for bug reports and feature requests pertaining to the WordPress plugin.

= Development =

This plugin is maintained on [GitHub](https://github.com/Mollom/wordpress-mollom).  Contributions are welcome!


== Installation ==

1. Install and activate the plugin.
1. [Sign up on Mollom.com](https://mollom.com/pricing) to create API keys for your site.
1. Enter them on the Mollom plugin settings page on your site.

For more detailed instructions, check our complete [Tutorial for WordPress](https://mollom.com/tutorials/wordpress) and [FAQ](http://wordpress.org/plugins/mollom/faq/).

= Requirements =

* PHP 5.2.4 or later.
* Your theme **must** use the `comment_form()` API function introduced in WordPress 3.0+.

= Requirements for Content Moderation Platform =

Optionally, to enable the [Content Moderation Platform (CMP)](http://mollom.com/moderation):

* [Pretty Permalinks](http://codex.wordpress.org/Using_Permalinks#Using_.22Pretty.22_permalinks) must be enabled.
* On servers running PHP <5.4 **as CGI**, ensure the Apache `mod_rewrite` module is enabled and add the following lines to your `.htaccess` file:

        RewriteEngine On
        RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]



= Compatibility =

Mollom is an all-in-one solution.  Similar plugins conflict with its operations.

To get the best performance out of Mollom, ensure to disable all other spam filter, honeypot, and CAPTCHA plugins, including the default Akismet plugin that ships with WordPress.


== Frequently Asked Questions ==

**Important:**  Ensure you have the latest release of the Mollom plugin installed first.

= How do I get Mollom API keys? =

1. [Sign up](https://mollom.com/pricing) on Mollom.com.
1. In your [Site manager](https://mollom.com/site-manager), register the site you want API keys for.
1. Click _"View keys"_ to see your public key and private key.

= My Mollom API keys do not work? =

There are multiple possible causes with corresponding error messages; check whether the error message hints at the cause already.  The most common issues:

* **Invalid keys:**  The API keys were not copied correctly; try to copy and paste them again.
* **Time offset:**  The local time of your server/operating system is incorrect and not synchronized with Coordinated Universal Time ([UTC](http://en.wikipedia.org/wiki/UTC)); consult your hosting provider or server operator to correct the server time (not timezone) and to enable an [NTP](http://en.wikipedia.org/wiki/Network_Time_Protocol) daemon to keep it synchronized.
* **Unable to reach Mollom:**  A [firewall](http://en.wikipedia.org/wiki/Firewall_%28computing%29) on your server/computer or infrastructure may block outbound HTTP requests to Mollom servers; consult your hosting provider or server operator to allow outbound HTTP requests to *.mollom.com.
* **Try again later:**  The service might be undergoing temporary maintenance work for Mollom Free users; it should normally be back up after a few minutes.

= How can I test Mollom? =

Do **not** test Mollom without enabling the testing mode.  Doing so would negatively affect your own author reputation across all sites in the Mollom network.  To test Mollom:

1. Enable the _"Testing mode"_ option on the Mollom plugin settings page.  
   *Note: Ensure to read the [difference in behavior](https://mollom.com/faq/how-do-i-test-mollom).*
1. Log out or switch to a different user, and perform your tests.
1. Disable the testing mode once you're done with testing.

= Mollom does not stop any spam on my form? =

Do you see the link to Mollom's privacy policy on the form?  If not, then the form is not protected.  
*Note: The privacy policy link can be toggled in the plugin settings.*

= The Mollom CAPTCHA and other elements do not appear? =

Your theme does not use the [`comment_form()`](http://codex.wordpress.org/Function_Reference/comment_form) function (introduced in WordPress 3.0) to output the comment form.  Ensure the `comments.php` file of your theme contains:

    <?php comment_form(); ?>

Have a look at WordPress' default *Twenty Twelve* theme to see how it is used.

= Does Mollom support plugin XYZ? =
= Can I protect other forms? =

Out of the box, the Mollom plugin allows to protect the WordPress comment form only.  Built-in support for other WordPress core entity forms (users, posts) will follow soon.

The Mollom plugin does not provide an API for other plugins and custom forms yet, but we're happy to discuss your needs.  If you're interested, check the [issue queue](https://github.com/Mollom/wordpress-mollom/issues).

= What if one of my visitors is blocked by Mollom? =

Whenever a post is not accepted, the error message contains a link that allows users to report the incident to Mollom support staff, which is able to investigate and resolve the situation.  
*Note: False-positive reports are purposively not submitted to your own site, since actual spammers are trying to game Mollom by reporting their correctly blocked spam posts, too.*


= How do I upgrade from the old _WP-Mollom_ plugin? =

1. Deactivate, uninstall, and delete the old `wp-mollom` plugin (version 0.7.5 or older).
1. [Install](http://wordpress.org/plugins/mollom/installation/) the new Mollom plugin (version 2.0 or later).
1. Re-enter your Mollom API keys.

There is no automated upgrade path, because the plugin has been rewritten from scratch.  Re-installing the new is a matter of minutes.  We're sorry for this one-time inconvenience.


== Upgrade Notice ==

= 2.0 =
Required upgrade.  Uninstall the old wp-mollom plugin, re-install the new, and re-enter your API keys.


== Changelog ==

= 2.1 =
2013-07-21

* Fixed comment reply form is not positioned below parent comment when form is re-rendered.
* Added support for plugin translations (gettext string localizations).
* Added FAQ.
* Added option to retain unsure posts instead of showing a CAPTCHA.

= 2.0 =
2013-07-07

* Rewritten and re-architected for Mollom's new [REST API](http://mollom.com/api).

= 0.7.5 =
2009-12-20

* fixed: wrong character encoding when comment is fed to wordpress after a CAPTCHA
* fixed: url was also truncated in href if > 32 chars in the management module
* fixed: changed 2 strings against typo's
* improved: added pagination on the bottom of the management module
* changed: contact details of plugin author

= 0.7.4 =
2009-04-18

* added: vietnamese (vi) translation
* added: bulgarian (bg_BG) translation
* added: bangla (bn_BD) translation

= 0.7.3 =
2009-03-16

* fixed: multiple moderation would incorrectly state 'moderation failed' due to incorrect set boolean.
* added: german (de_DE) translation
* added: italian (it_IT) translation

= 0.7.2 =
2009-02-12

* fixed: closing a gap that allowed bypassing checkContent through spoofing $_POST['mollom_sessionid']
* fixed: if mb_convert_encoding() is not available, the CAPTCHA would generate a PHP error. Now falls back to htmlentities().
* improved: the check_trackback_content and check_comment_content are totally rewritten to make them more secure.
* added: user roles capabilities. You can now exempt roles from a check by Mollom
* added: simplified chinese (zh_CN) translation

= 0.7.1 =
2008-12-27

* fixed: all plugin panels now show in the new WP 2.7 administration interface
* fixed: non-western character sets are now handled properly in the captcha form
* fixed: handles threaded comments properly now
* fixed: handling multiple records in the manage module not correctly handled
* improved: extra - non standard- fields added to the comment form don't get dropped anymore
* improved: revamped the administration panel
* improved: various smaller code improvements
* added: the plugin is now compatible with the new plugin uninstall features in Wordpress 2.7
* added: the 'quality' of 'spaminess' of a comment is now logged and shown as an extra indicator

= 0.7.0 =
2008-11-27

* fixed: hover over statistics bar graph wouldn't yield numerical data
* added: localization/internationalisation (i8n) support. Now you can translate wp-mollom through POEdit and the likes.

= 0.6.2 =
2008-11-10

* fixed: wrong feedback qualifiers (spam, profanity, unwanted, low-quality) were transmitted to Mollom upon moderation

= 0.6.1 =
2008-09-24

* fixed: division by 0 error on line 317
* fixed: if 'unsure' but captcha was filled in correctly, HTML attributes in comment content would sometimes be eaten by kses.
* improved: the mollom function got an overhaul to reflect the september 15 version of the Mollom API documentation
* changed: mollom statistics are now hooked in edit-comments.php instead of plugins.php
* added: _mollom_retrieve_server_list() function now handles all getServerList calls

= 0.6.0 =
2008-08-24

* fixed: html is preserved in a comment when the visitor is confronted with the captcha
* fixed: handling of session id's in show_captcha() en check_captcha() follows the API flow better.
* fixed: broken bulk moderation of comments is now fixed
* fixed: the IP adress was incorrectly passed to the 'mollom.checkCaptcha' call
* fixed: the session_id is now passed correctly to _save_session() after the captcha is checked.
* improved: more verbose status messages report when using the Mollom Manage module
* improved: cleaned up some deprecated functions
* improved: handling of Mollom feedback in _mollom_send_feedback() function
* added: approve and unapprove options in the Mollom Manage module
* added: link to the originating post in the Mollom Manage module
* added: if a comment had to pass a CAPTCHA, it will be indicated in the Mollom Manage module
* added: plugin has it's own HTTP USER AGENT string which will be send with XML RPC calls to the API
* added: detailed statistics. You can find these under Plugins > Mollom

= 0.5.2 =
2008-07-20

* fixed: passing $comment instead of $_POST to show_captcha() in check_captcha()
* improved: implemented wpdb->prepare() in vunerable queries
* improved: mollom_activate() function now more robust
* changed: mollom_author_ip() reflects changes in the API documentation. This function is now 'reverse proxy aware'

= 0.5.1 =
2008-06-30

* fixed: issues with the captcha page not being rendered correctly
* added: mollom_manage_wp_queue() function which deals with Mollom feedback from the default WP moderation queue
* improved: legacy code when activating the plugin (needed for upgrading from < 0.5.0 (testversions!)

= 0.5.0 =
2008-06-26

* Added: installation/activation can contain legacy code and versioning for handling old (test)configurations
* Added: PHPDoc style documentation of functions
* Added: mollom_moderate_comment() template function. Allows moderation from your theme.
* Removed: 'moderation mode'. Moderation should only be configured through the proper wordpress interface.
* fixed: compatibility issues with the WP-OpenID plugin
* Improved: the plugin relies far less on global variables now.
* Improved: all mollom data is now saved to it's own seprerate, independent table.
* Improved: SQL revision
* Improved: error handling is now more verbose
* Improved: status messages in the configuration/moderation panels now only show when relevant
* Improved: handling of mollom servers not being available or unreachable

= 0.4 =
2008-06-03

* Changed: 'configuration' now is under WP 'settings' menu instead of 'plugins'
* Added: show_mollom_plugincount() as a template function to show off your mollom caught

= 0.3 =
2008-05-27

* Added: trackback support. If ham: passed. If unsure/spam: blocked.
* Added: 'moderation mode' mollom approved comments/trackbacks still need to be moderated
* Added: 'Restore' When the plugin is deactivated, optionally purge all mollom related data
* Changed: moderation isn't mandatory anymore, only optional. Comments aren't saved to the  database until the CAPTCHA is filled out correctly. Otherwise: never registered.
* Improved: Error handling now relies on WP Error handling (WP_Error object)

= 0.2 =
2008-05-22

* Added: bulk moderation of comments
* Added: 'policy mode' disables commenting if the Mollom service is down
* Improved: moderation interface is more userfriendly
* Improved: only unmoderated messages with a mollom session id can be moderated
* Improved: deactivation restores database to previous state. Removal of stored option values and deletion of the mollom_session_id column in $prefix_comments
* Fixed: persistent storage of the mollom session id in the database
* Fixed: no messages shown in the configuration screen triggers a PHP error

= 0.1 =
2008-05-12

* Initial release to testers
