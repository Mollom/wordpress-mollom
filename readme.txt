=== Mollom ===
Contributors: Netsensei, tha_sun
Donate link: https://mollom.com/pricing
Tags: comments, spam, social, content, moderation, captcha, mollom
Requires at least: 3.1.0
Tested up to: 3.5.1
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Mollom protects your site from spam, profanity, and unwanted posts.  Focus on public and social engagement.  Focus on things that matter.

== Description ==

[Mollom](http://mollom.com) protects your site from spam and unwanted posts.  Mollom enables you to focus on quality content, and to embrace user-contributed content and public engagement.

Mollom automatically blocks all spam, accepts the content you want, and honestly admits when it is _unsure_ — asking the author to solve a [CAPTCHA](http://en.wikipedia.org/wiki/CAPTCHA).  Obvious spam does not even enter your site; it's outright discarded instead.  To learn more, check [how Mollom works](http://mollom.com/how-mollom-works).

The Mollom service encompasses all spam filtering techniques that exist, using industry-leading content classification technologies that learn and automatically adapt, and is constantly monitored and improved by engineers — to allow you to focus on content:  Quality content.

Mollom is actively used by more than 50,000 sites, including Sony, twitter, MIT, Adobe, Warner Bros Records, LinuxJournal, NBC, and many others.  More than 4,500,000 posts are checked by Mollom _per day_.

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

**Important:**  In case of any issues, ensure you have the latest stable release of the plugin installed first.

= My Mollom API keys do not work? =

There are multiple possible causes with corresponding error messages; ensure to check whether the message contains a hint at the cause already.  The most common issues:

* *Invalid:*  
  Most likely, the entered API keys were not copied correctly.
* *HTTP requests:*  
  Most likely, a firewall on your server/computer or in your infrastructure happens to block outbound requests to *.mollom.com.  You may need to contact your hosting/server administrator to resolve the issue.
* *Try again later:*  
  API key verification might be undergoing temporary maintenance work for Mollom Free users.

If all fails, [Mollom Support](https://mollom.com/contact) might be able to help.

= How can I test Mollom? =

Do **not** test Mollom without enabling the testing mode.  Doing so would negatively affect your own author reputation across all sites in the Mollom network.  To test Mollom:

1. Enable the _"Testing mode"_ option on the Mollom plugin settings page.  
   *Note: Ensure to read the [difference in behavior](https://mollom.com/faq/how-do-i-test-mollom).*
1. Log out or switch to a different user, and perform your tests.
1. Disable the testing mode once you're done with testing.

= Mollom does not stop any spam on my form? =

Do you see the link to Mollom's privacy policy on the form?  If not, then the form is not protected.  
*Note: The privacy policy link can be toggled in the plugin settings.*

= Can I protect other forms? =

Out of the box, the Mollom plugin allows to protect the WordPress comment form only.  Built-in support for other WordPress core entity forms (users, posts) will follow soon.

The Mollom plugin does not provide an API for other plugins and custom forms yet, but we're happy to discuss your needs.  If you're interested, check the [issue queue](https://github.com/Mollom/wordpress-mollom/issues).


== Upgrade Notice ==

= 2.0 =
Required upgrade.  Uninstall the old wp-mollom plugin, re-install the new, and re-enter your API keys.


== Changelog ==

= 2.0 =

* Rewritten and re-architected for Mollom's new [REST API](http://mollom.com/api).

