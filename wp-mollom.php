<?php

/*
  Plugin Name: Mollom
  Plugin URI: http://wordpress.org/extend/plugins/wp-mollom/
  Description: Protect your site from spam and unwanted posts with <a href="http://mollom.com">Mollom</a>.
  Author: Matthias Vandermaesen
  Version: 2.x-dev
  Author URI: http://www.colada.be
  Email: matthias@colada.be
 */

/*
  Copyright 2008, 2009, 2010, 2011 Matthias Vandermaesen (email : matthias@colada.be)
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.
  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

/**
 * define this version of the plugin
 */
define('MOLLOM_PLUGIN_VERSION', '2.x-dev');

/**
 *  define WP Mollom's i18n namespace
 */
define('MOLLOM_I18N', 'wp-mollom');

/** define a few paths
 *
 */
define('MOLLOM_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * define WP Mollom table where mollom data per comment gets stored
 */
define( 'MOLLOM_TABLE', 'mollom' );

/**
 * define WP Mollom table where mollom cache data gets stored
 */
define( 'MOLLOM_CACHE_TABLE', 'mollom_cache' );

/**
 * Define the version of the mollom tables
 */
define( 'MOLLOM_TABLE_VERSION', '2000');

/**
 *  Define the life time a cached form.
 */
define( 'MOLLOM_FORM_ID_LIFE_TIME', 300);

/**
 * Seconds that must have passed by for the same author to post again.
 */
define( 'MOLLOM_CAPTCHA_RATE_LIMIT', 15);

/**
 * Form protection mode: no protection
 */
define( 'MOLLOM_MODE_DISABLED', 0);

/**
 * Form protection mode: text analysis with CAPTCHA fallback
 */
define ( 'MOLLOM_MODE_ANALYSIS', 1);

/**
 * Form protection mode: CAPTCHA only protection
 */
define( 'MOLLOM_MODE_CAPTCHA', 2);

/**
 * Common functions are stored in common.inc file. These are made available
 * throughout the entire plugin.
 */
require_once(MOLLOM_PLUGIN_PATH . '/includes/common.inc');

/**
 * Factory class.
 * 
 * WP Mollom has a componentized architecture since not all the functionality
 * needs to be loaded everytime a request is made. When we are on the frontend,
 * the content checkinking class is loaded, if we are in the backend, only the
 * administration class with moderation, configuration,... functionality gets
 * loaded.
 */
class WPMollomFactory {
  static private $instance = NULL;

  public static function get_instance() {
    mollom_include('WPMollomBase.class.inc');
    if (!self::$instance) {
      if (is_admin()) {
        mollom_include('WPMollomAdmin.class.inc');
        self::$instance = new WPMollomAdmin();
      } else {
        mollom_include('WPMollomContent.class.inc');
        self::$instance = new WPMollomContent();
      }
    }

    return self::$instance;
  }

  
  /**
   * Callback.
   *
   * Called on activation of the plugin. This hook will install and register the
   * Mollom tables in the database.
   */
  public static function activate() {
    // Table definition for MOLLOM_TABLE
    $mollom_tbl_definition = "
    `comment_ID` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
    `content_ID` VARCHAR( 128 ) NOT NULL DEFAULT '',
    `captcha_ID` VARCHAR( 128 ) NOT NULL DEFAULT '',
    `form_ID` VARCHAR( 255 ) NULL DEFAULT NULL,
    `moderate` TINYINT ( 1 ) NOT NULL DEFAULT '0',
    `changed` INT ( 10 ) NOT NULL DEFAULT '0',
    `spamScore` FLOAT NULL DEFAULT '0.00',
    `spamClassification` VARCHAR( 255 ) NULL DEFAULT NULL,
    `solved` TINYINT ( 1 ) NULL DEFAULT NULL,
    `profanityScore` FLOAT NULL DEFAULT '0.00',
    `reason` VARCHAR( 255 ) NULL DEFAULT NULL,
    `languages` VARCHAR( 255 ) NULL DEFAULT NULL,
    UNIQUE (
    `comment_ID` ,
    `content_ID`
    )";
  
    // Tabel definition for MOLLOM_CACHE_TABLE
    $mollom_cache_tbl_definition = "
    `created` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
    `form_id` VARCHAR( 40 ) NULL DEFAULT NULL,
    `key` VARCHAR( 128 ) NULL DEFAULT NULL,
    UNIQUE (
    `created`,
    `form_id`
    )";
  
    mollom_table_install(MOLLOM_TABLE, MOLLOM_TABLE_VERSION, $mollom_tbl_definition);
    mollom_table_install(MOLLOM_CACHE_TABLE, MOLLOM_TABLE_VERSION, $mollom_cache_tbl_definition);
  }
  
}

// Register the activation callback
register_activation_hook(__FILE__, array('WPMollomFactory', 'activate'));

// Gone with the wind!
WPMollomFactory::get_instance();

