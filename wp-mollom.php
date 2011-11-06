<?php

/*
Plugin Name: WP Mollom
Plugin URI: http://wordpress.org/extend/plugins/wp-mollom/
Description: Enable <a href="http://www.mollom.com">Mollom</a> on your wordpress blog. This plugin provides Mollom core functionality.
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

/* define this version of the plugin */
define( 'MOLLOM_PLUGIN_VERSION', '2.x-dev' );

/* define WP Mollom's i18n namespace */
define( 'MOLLOM_I18N', 'wp-mollom' );

/* define a few paths */
define( 'MOLLOM_PLUGIN_PATH', plugin_dir_path(__FILE__) );

class WPMollom {

  // Static objects as singletons
  static private $instance = NULL;
  static private $mollom = NULL;
  private $mollom_nonce = 'mollom-configuration';
	
  /**
  * Constructor
  *
  * Upon instantiation, we'll hook up the base methods of this class to actions as
  * callbacks. Lazyload anything extra in the methods themselves.
  */
  private function __construct() {
    // load the text domain for localization
    load_plugin_textdomain(MOLLOM_I18N, false, dirname(plugin_basename(__FILE__)));
    // register the administration page
    add_action('admin_menu',array(&$this, 'register_administration_pages'));
    register_activation_hook(__FILE__, array(&$this, 'activate'));
  }

  /**
   * Instantiates WPMollom
   *
   * Instantiates WPMollom as a singleton.
   * @return WPMollom
   */
  public function getInstance() {
    if (!self::$instance) {
      self::$instance = new WPMollom();
      return self::$instance;
    }
  }

  /**
   * Get an instance of MollomWordpress
   *
   * Instantiates MollomWordpress as a singleton.
   * @return MollomWordpress
   */
  public function getMollomInstance() {
    self::mollom_include('mollom.class.inc');
    self::mollom_include('mollom.wordpress.inc');
	
    if (!self::$mollom) {
      self::$mollom = new MollomWordpress();
      return self::$mollom;
    }		
  }

  /**
   * Helper function
   *
   * Include files, take care of paths
   * @param string $file
   *  The file name of an existing file in the includes/ folder
   */
  static private function mollom_include($file) {
    require_once(MOLLOM_PLUGIN_PATH . '/includes/' . $file);
  }

  /**
   * Callback. Called on activation of the plugin.
   */
  function activate() {
    self::mollom_include('common.inc');
    mollom_table_install();
  }

  /**
   * Register the administration pages
   *
   * Register new pages so to get displayed in /wp-admin
   */
  public function register_administration_pages() {
    add_submenu_page( 'options-general.php', __('Mollom', MOLLOM_I18N), __('Mollom', MOLLOM_I18N), 'manage_options', 'mollom-key-config', array(&$this, 'configuration_page') );
    add_action( 'admin_init', array(&$this, 'register_configuration_options') );
    add_action( 'manage_comments_custom_column', array(&$this, 'mollom_comment_column_row'), 10, 2 );
    add_filter( 'manage_edit-comments_columns', array(&$this, 'mollom_comments_columns') );
  }
	
  /**
   * Register settings with Wordpress
   *
   * The register_setting() function registers a setting for easy handling through option_get/update/delete.
   */
  public function register_configuration_options() {
    register_setting('mollom_settings', 'mollom_public_key');
    register_setting('mollom_settings', 'mollom_private_key');
    register_setting('mollom_settings', 'mollom_roles');
    register_setting('mollom_settings', 'mollom_site_policy');
    register_setting('mollom_settings', 'mollom_reverse_proxy');
    register_setting('mollom_settings', 'mollom_reverse_proxy_addresses');
  }
	
  /**
   * Page callback
   *
   * Handle the configuration page attached to options-general.php.
   */
  public function configuration_page() {
    self::mollom_include('common.inc');
	
    $mollom_public_key = NULL;
    $mollom_private_key = NULL;
    $mollom = self::getMollomInstance();
    $mollom = new MollomWordpress();

    $m = $mollom->verifyKeys();
		
    if ( isset($_POST['submit']) ) {
      if ( function_exists('current_user_can') && !current_user_can('manage_options') ) {
        die(__('Cheatin&#8217; uh?'));
      }
			
      check_admin_referer( $this->mollom_nonce );
			
      if ( $_POST['mollom_public_key'] ) {
        $mollom_public_key = preg_replace( '/[^a-z0-9]/i', '', $_POST['mollom_public_key'] );
        update_option('mollom_public_key', $mollom_public_key);
      }
			
      if ( $_POST['mollom_private_key'] ) {
        $mollom_private_key = preg_replace( '/[^a-z0-9]/i', '', $_POST['mollom_private_key'] );
        update_option('mollom_private_key', $mollom_private_key);
      }
    }

    // set variables used to render the page
    $vars['mollom_nonce'] = $this->mollom_nonce;
    $vars['mollom_public_key'] = ($mollom_public_key) ? $mollom_public_key : get_option('mollom_public_key');
    $vars['mollom_private_key'] = ($mollom_private_key) ? $mollom_private_key : get_option('mollom_private_key');

    // Render the output
    mollom_theme('configuration', $vars);
  }

  /**
   * Callback. Show Mollom actions in the Comments table
   *
   * Show Mollom action links and status messages per commentinthe comments table.
   *
   * @param string $column The column name
   * @param int $comment_id The comment ID
   * @return string Rendered output
   */
  function mollom_comment_column_row($column, $comment_id) {
    if ( $column != 'mollom' )
		  return;

    self::mollom_include('common.inc');

    // @todo add links:
    // @todo add spaminess indicator
    // @todo add had a captcha indicator
    // @todo add status messages (showstopper?)

    // Render the output
    mollom_theme('comment_moderation', $vars);
  }

  /**
   * Callback. Registers an extra column in the Comments table.
   *
   * Registers an extra column in the Comments section of wp-admin. This column
   * is used to display Mollom specific status messages and actions per comment.
   *
   * @param array $columns an array of columns for a table
   * @return array An array of columns for a table
   */
  function mollom_comments_columns( $columns ) {
	  $columns[ 'mollom' ] = __( 'Mollom' );
	  return $columns;
  }
}

// Gone with the wind
WPMollom::getInstance();
