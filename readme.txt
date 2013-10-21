=== Mollom ===
Contributors: Netsensei, tha_sun
Donate link: https://mollom.com/pricing
Tags: comments, spam, social, content, moderation, captcha, mollom
Requires at least: 3.1.0
Tested up to: 3.6
Stable tag: 2.2
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

= 2.2 =
2013-10-22

* Fixed CAPTCHA and honeypot form elements are not presented correctly in all themes.
* Added user registration form protection.

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

* Fixed bogus character encoding for comments after CAPTCHA.
* Added pagination to moderation pages.

= 0.7.4 =
2009-04-18

* Added translations: Vietnamese (vi), Bulgarian (bg_BG), Bangla (bn_BD)

= 0.7.3 =
2009-03-16

* Fixed bogus error message when bulk-moderating comments.
* Added translations: German (de_DE), Italian (it_IT)

= 0.7.2 =
2009-02-12

* Fixed access bypass via HTTP POST payload.
* Fixed PHP fatal error when mb extension is not available.
* Added option to allow selected user roles to bypass Mollom protection.
* Added translations: Simplified Chinese (zh_CN)

= 0.7.1 =
2008-12-27

* Updated for WP 2.7 compatibility.
* Fixed Unicode support, threaded comments, bulk-moderation.
* Added new administration panel.
* Added quality and spam classification indicators to moderation panel.

= 0.7.0 =
2008-11-27

* Fixed statistics graph interaction.
* Added plugin localization support.

= 0.6.2 =
2008-11-10

* Fixed bogus feedback data is sent to Mollom.

= 0.6.1 =
2008-09-24

* Fixed faulty manipulation of user input after solving a CAPTCHA.
* Fixed Mollom server list handling.

= 0.6.0 =
2008-08-24

* Fixed user input and data handling for CAPTCHAs.
* Fixed bulk moderation of comments.
* Added approve/unapprove actions and more indicators to moderation panel.
* Added statistics.

= 0.5.2 =
2008-07-20

* Fixed CAPTCHA form handling.
* Fixed SQL query statement security.
* Fixed author IP addresses do not account for reverse-proxies.

= 0.5.1 =
2008-06-30

* Fixed CAPTCHA page rendering.
* Added integration with default WP comment moderation actions.

= 0.5.0 =
2008-06-26

* Fixed compatibility with WP-OpenID plugin.
* Fixed Mollom server fail-over handling.
* Added own database table for recording metadata.

= 0.4 =
2008-06-03

* Changed configuration page location from Plugins to Settings.
* Added theme template function to output Mollom statistics.

= 0.3 =
2008-05-27

* Changed comments to not be accepted when they are classified as spam.
* Added trackback support.
* Added purge of all Mollom data upon plugin deactivation. (optional)

= 0.2 =
2008-05-22

* Fixed storage of Mollom data in database.
* Added bulk moderation for comments.
* Added fallback mode option to configure behavior in case the Mollom service is down or unreachable.

= 0.1 =
2008-05-12

* Initial release.
