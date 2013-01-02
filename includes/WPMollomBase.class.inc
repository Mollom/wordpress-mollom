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

}