=== Mollom ===
Contributors: Netsensei, tha_sun
Donate link: http://mollom.com
Tags: comments, spam, social, content, moderation, captcha, mollom
Requires at least: 3.1.0
Tested up to: 3.5.1
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Mollom protects your site from spam, profanity, and unwanted posts.  Focus on public and social engagement.  Focus on things that matter.

== Description ==

[Mollom](http://mollom.com) protects you from spam and unwanted posts.  Mollom enables you to focus on quality content, and to embrace social, user-contributed content and public engagement.

Mollom blocks all bad spam, accepts the good user-contributed content, and honestly admits when it is _unsure_ — asking the author to solve a [CAPTCHA](http://en.wikipedia.org/wiki/CAPTCHA) to be sure.  To learn more, check [how Mollom works](http://mollom.com/how-mollom-works).

Obvious spam does not even enter your site; it's outright discarded instead.  You should not have to deal with spam.  Mollom supplies a cutting-edge content classification technology that learns and automatically adapts — to allow you to focus on content:  Quality content.

You have multiple WordPress blogs and potentially other sites that need your attention and moderation?  This plugin integrates with Mollom's [Content Moderation Platform](http://mollom.com/moderation) out of the box — Moderate them all at once.  Focus on important topics instead.

Note:  Mollom is an all-in-one solution.  To get the best performance out of Mollom, ensure to disable all other spam protection plugins.


== Installation ==

1. Install and activate the plugin.
1. [Sign up](https://mollom.com/pricing) to create Mollom API keys for your site.
1. Enter them on the settings page of the Mollom plugin on your site.


= Requirements =

* PHP 5.2.4 or later.
* Your theme **must** use the `comment_form()` API function introduced in WordPress 3.0+.

= Requirements for Content Moderation Platform =

Optionally, to enable the [Content Moderation Platform (CMP)](http://mollom.com/moderation) integration:

* [Pretty Permalinks](http://codex.wordpress.org/Using_Permalinks#Using_.22Pretty.22_permalinks) must be enabled.
* On servers running PHP <5.4 as CGI, ensure the Apache `mod_rewrite` module is enabled and add the following lines to your `.htaccess` file:

        RewriteEngine On
        RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]


== Upgrade Notice ==

= 2.0 =
Required upgrade.  Uninstall the old wp-mollom plugin, re-install the new, and re-enter your API keys.


== Changelog ==

= 2.0 =

* Rewritten and re-architected for Mollom's new REST API.

