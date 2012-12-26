<?php

/**
 * WPMollomBase class
 * 
 * Registers common callback hooks and provides a factory function to
 * get a fully loaded Mollom API instance.
 */
class WPMollomBase {
  static private $mollom = NULL;

  public function __construct() {
    // load the text domain for localization
    load_plugin_textdomain(MOLLOM_I18N, false, dirname(plugin_basename(__FILE__)));
    // Register the activation callback
    register_activation_hook(__FILE__, array(&$this, 'activate'));
  }

  /**
   * Get an instance of MollomWordpress
   *
   * Instantiates MollomWordpress as a singleton.
   * @return MollomWordpress
   */
  public static function get_mollom_instance() {
    if (!isset(self::$mollom)) {
      mollom_include('mollom.class.inc');
      mollom_include('mollom.wordpress.inc');
      if (get_option('mollom_developer_mode', 'off') == 'off') {
        self::$mollom = new MollomWordpress();
      } else {
        self::$mollom = new MollomWordpressTest();
      }
    }
    return self::$mollom;
  }

  /**
   * Callback.
   *
   * Called on activation of the plugin. This hook will install and register the
   * Mollom tables in the database.
   */
  function activate() {
    // Table definition for MOLLOM_TABLE
    $mollom_tbl_definition = "
    `comment_ID` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
    `content_ID` VARCHAR( 128 ) NOT NULL DEFAULT '',
    `captcha_ID` VARCHAR( 128 ) NOT NULL DEFAULT '',
    `form_ID` VARCHAR( 255 ) NULL DEFAULT NULL,
    `moderate` TINYINT ( 1 ) NOT NULL DEFAULT '0',
    `changed` INT ( 10 ) NOT NULL DEFAULT '0',
    `spamScore` FLOAT NULL DEFAULT '0.00',
    `spamClassification` FLOAT NULL DEFAULT NULL,
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