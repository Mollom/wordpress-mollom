Mollom for Wordpress
====================

This plugin brings [Mollom](http://www.mollom.com) spam protection to the
[Wordpress](http://www.wordpress.org) platform. It's an alternative to the default
AKismet plugin which ships with Wordpress.

Mollom combines intelligent text analysis with a safe CAPTCHA test to filter out
comment spam while making sure that the user experience of your visitors is not hampered.
Since spam is blocked up front, administrators don't have to keep a regular, tedious routine
of keeping moderation queues clean.

Installation
------------

* Checkout a copy from Github
* Place the entire wp-mollom folder in your wp-content/plugins folder
* Enable the plugin in your dashboard
* Register with [mollom.com](http://www.mollom.com) to obtain a key pair for your website
* In the Dashboard, go to Settings > Mollom and configure the plugin.

Developers
----------

Currently, the plugin only interacts with the comment system. Because there is no centralized
Form API in Wordpress core, it's up to individual plugin developers to implement Mollom
functionality into their own plugins.

Additional notes
----------------

** BEWARE ** THIS IS A DEVELOPMENT SNAPSHOT.
This version of WP Mollom is not intended for production environments. It's not intended to be
fully functional. The code can even be broken or incomplete at any given time. Only use this
if you intend to contribute to the project or if you're just curious.

Send feedback or bug reports to matthias@colada.be.

Licensing
---------
This program is licensed under GPLv2

See: LICENSE.md for more details

Author
------
Author: Matthias Vandermaesen
http://www.colada.be
http://www.netsensei.nl
matthias@colada.be
