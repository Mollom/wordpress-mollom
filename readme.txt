=== Mollom ===
Contributors: netsensei, tha_sun
Donate link: http://mollom.com
Tags: comments, spam, social, content, moderation, captcha
Requires at least: 3.1.0
Tested up to: 3.5.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Protects you from spam and unwanted posts.

== Description ==

[Mollom](http://mollom.com) protects you from spam and enables you to focus on content quality.

Mollom blocks all bad spam, accepts the good user-contributed content, and honestly admits when it is _unsure_ -- asking the author to solve a [CAPTCHA](http://en.wikipedia.org/wiki/CAPTCHA) to be sure.  To learn more, check [How Mollom works](http://mollom.com/how-mollom-works).

== Installation ==

1. _Activate_ the plugin.
1. [Sign up](https://mollom.com/pricing) and create API keys.
1. Enter them on the [Mollom plugin settings page](/wp-admin/options-general.php?page=mollom).

= Requirements =

* PHP 5.2.4 or later
* Your theme **must** use the `comment_form()` API function of WP core 3.0+.

= [Content Moderation Platform](http://mollom.com/moderation) integration requirements =

* On servers running PHP <5.4, and PHP as CGI (not Apache module), inbound HTTP request headers are not made available to PHP.  Ensure the Apache `mod_rewrite` module is enabled and add the following lines to your `.htaccess` file:

        RewriteEngine On
        RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]


== Upgrade Notice ==

= 2.0 =
Required upgrade.  Uninstall the old version, re-install the new, and re-enter your API keys.


== Changelog ==

= 2.0 =

* Rewritten and re-architected for Mollom's new REST API.

