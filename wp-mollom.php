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

/* define this version of the plugin */
define('MOLLOM_PLUGIN_VERSION', '2.x-dev');

/* define WP Mollom's i18n namespace */
define('MOLLOM_I18N', 'wp-mollom');

/* define a few paths */
define('MOLLOM_PLUGIN_PATH', plugin_dir_path(__FILE__));

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
    add_action('admin_menu', array(&$this, 'register_administration_pages'));
    register_activation_hook(__FILE__, array(&$this, 'activate'));
    // pass comments through Mollom during processing
    add_filter('preprocess_comment', array(&$this, 'check_comment'));
  }

  /**
   * Instantiates WPMollom
   *
   * Instantiates WPMollom as a singleton.
   * @return WPMollom
   */
  public static function get_instance() {
    if (!self::$instance) {
      self::$instance = new WPMollom();
    }

    return self::$instance;
  }

  /**
   * Get an instance of MollomWordpress
   *
   * Instantiates MollomWordpress as a singleton.
   * @return MollomWordpress
   */
  public static function get_mollom_instance() {
    if (!isset(self::$mollom)) {
      self::mollom_include('mollom.class.inc');
      self::mollom_include('mollom.wordpress.inc');
      if (get_option('mollom_developer_mode', 'off') == 'off') {
        self::$mollom = new MollomWordpress();
      } else {
        self::$mollom = new MollomWordpressTest();
      }
    }
    return self::$mollom;
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
    add_submenu_page('options-general.php', __('Mollom', MOLLOM_I18N), __('Mollom', MOLLOM_I18N), 'manage_options', 'mollom-key-config', array(&$this, 'configuration_page'));
    add_action('admin_init', array(&$this, 'register_configuration_options'));
    add_action('manage_comments_custom_column', array(&$this, 'mollom_comment_column_row'), 10, 2);
    add_filter('manage_edit-comments_columns', array(&$this, 'mollom_comments_columns'));
  }

  /**
   * Register settings with Wordpress
   *
   * The register_setting() function registers a setting for easy handling through option_get/update/delete.
   *
   * @todo: add sanitization callbacks (do we need this?)
   */
  public function register_configuration_options() {
    // Mollom class configuration.
    register_setting('mollom_settings', 'mollom_public_key');
    register_setting('mollom_settings', 'mollom_private_key');
    register_setting('mollom_settings', 'mollom_roles');
    register_setting('mollom_settings', 'mollom_fallback_mode');
    register_setting('mollom_settings', 'mollom_reverse_proxy_addresses');
    register_setting('mollom_settings', 'mollom_developer_mode');
  }

  /**
   * Page callback
   *
   * Handle the configuration page attached to options-general.php.
   */
  public function configuration_page() {
    self::mollom_include('common.inc');

    $mollom = self::get_mollom_instance();
    $messages = array();

    if (isset($_POST['submit'])) {
      if (function_exists('current_user_can') && !current_user_can('manage_options')) {
        die(__('Cheatin&#8217; uh?'));
      }
      check_admin_referer($this->mollom_nonce);

      // API keys.
      if (isset($_POST['publicKey'])) {
        $mollom->publicKey = preg_replace('/[^a-z0-9]/i', '', $_POST['publicKey']);
        update_option('mollom_public_key', $mollom->publicKey);
        if (strlen($mollom->publicKey) != 32) {
          $messages[] = '<div class="error"><p>' . __('The public API key must be 32 characters. Ensure you copied the key correctly.', MOLLOM_I18N) . '</p></div>';
        }
      }
      if (isset($_POST['privateKey'])) {
        $mollom->privateKey = preg_replace('/[^a-z0-9]/i', '', $_POST['privateKey']);
        update_option('mollom_private_key', $mollom->privateKey);
        if (strlen($mollom->privateKey) != 32) {
          $messages[] = '<div class="error"><p>' . __('The private API key must be 32 characters. Ensure you copied the key correctly.', MOLLOM_I18N) . '</p></div>';
        }
      }
      // Excluded roles.
      if (!empty($_POST['mollom_roles'])) {
        $mollom->roles = $_POST['mollom_roles'];
        update_option('mollom_roles', $mollom->roles);
      }
      else {
        delete_option('mollom_roles');
      }
      // Reverse proxy addresses.
      update_option('mollom_reverseproxy_addresses', $_POST['mollom_reverseproxy_addresses']);
      // Fallback mode.
      update_option('mollom_fallback_mode', !empty($_POST['fallback_mode']) ? 'block' : 'accept');
      // Developer mode
      update_option('mollom_developer_mode', !empty($_POST['developer_mode']) ? 'on' : 'off');

      $messages[] = '<div class="updated"><p>' . __('The configuration was saved.') . '</p></div>';
    }

    // When requesting the page, and after updating the settings, verify the
    // API keys (unless empty).
    if (empty($mollom->publicKey) || empty($mollom->privateKey)) {
      $messages[] = '<div class="error"><p>' . __('The Mollom API keys are not configured yet.', MOLLOM_I18N) . '</p></div>';
    } else {
      $result = $mollom->verifyKeys();

      if ($result === TRUE) {
        $messages[] = '<div class="updated"><p>' . __('Mollom servers verified your keys. The services are operating correctly.', MOLLOM_I18N) . '</p></div>';
      }
      else if ($result === MOLLOM::AUTH_ERROR) {
        $messages[] = '<div class="error"><p>' . __('The configured Mollom API keys are invalid.', MOLLOM_I18N) . '</p></div>';
      }
      else if ($result === MOLLOM::NETWORK_ERROR) {
        $messages[] = '<div class="error"><p>' . __('The Mollom servers could not be contacted. Please make sure that your web server can make outgoing HTTP requests.', MOLLOM_I18N) . '</p></div>';
      }
      else {
        $messages[] = '<div class="error"><p>' . __('The Mollom servers could be contacted, but the Mollom API keys could not be verified.', MOLLOM_I18N) . '</p></div>';
      }
    }

    // Set variables used to render the page.
    $vars['messages'] = (!empty($messages)) ? '<div class="messages">' . implode("<br/>\n", $messages) . '</div>' : '';
    $vars['mollom_nonce'] = $this->mollom_nonce;
    $vars['publicKey'] = $mollom->publicKey;
    $vars['privateKey'] = $mollom->privateKey;
    $vars['mollom_reverseproxy_addresses'] = get_option('mollom_reverseproxy_addresses', '');
    $vars['mollom_roles'] = $this->mollom_roles_element();
    $vars['mollom_developer_mode'] = (get_option('mollom_developer_mode', 'on') == 'on') ? ' checked="checked"' : '';
    $vars['mollom_fallback_mode'] = (get_option('mollom_fallback_mode', 'accept') == 'block') ? ' checked="checked"' : '';

    // Render the page.
    mollom_theme('configuration', $vars);
  }

  /**
   * Helper function. Generate an <ul> list of roles
   *
   * @global type $wp_roles
   * @return string
   */
  private function mollom_roles_element() {
    global $wp_roles;
    $mollom_roles = get_option('mollom_roles', array());
    $checked = '';

    $element = "<ul>";

    foreach ($wp_roles->get_names() as $role => $name) {
      $name = translate_user_role($name);
      if ($mollom_roles) {
        $checked = (in_array($role, $mollom_roles)) ? "checked" : "";
      }
      $element .= "<li><input type=\"checkbox\" name=\"mollom_roles[]\" value=\"" . $role . "\" " . $checked . " /> " . $name . "</li>";
    }

    $element .= "</ul>";

    return $element;
  }

  /**
   * Callback. Show Mollom actions in the Comments table
   *
   * Show Mollom action links and status messages per commentinthe comments table.
   *
   * @todo add links
   * @todo add spaminess indicator
   * @todo add a had a captcha indicator
   * @todo add status messages
   *
   * @param string $column The column name
   * @param int $comment_id The comment ID
   * @return string Rendered output
   */
  public function mollom_comment_column_row($column, $comment_id) {
    if ($column != 'mollom')
      return;

    self::mollom_include('common.inc');

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
  public function mollom_comments_columns($columns) {
    $columns['mollom'] = __('Mollom');
    return $columns;
  }

  /**
   * Callback. Perform the actual Mollom check on a new comment
   *
   * This function hooks onto the comment preprocessing. It will pass the comment
   * to Mollom. Depending on the result, it will either pass the comment to WP as ham,
   * block it as spam or show captcha if unsure. Trackbacks are also passed to Mollom.
   *
   * @param array $comment The preprocessed comment
   * @return array The comment if it passed the check, or void to block it from the database
   */
  public function check_comment($comment) {
    // If a logged in user exists check if the role is exempt from a Mollom check
    // non-registered visitors don't have a role so their submissions are always checked
    $user = wp_get_current_user();
    if ($user->ID) {
      $mollom_roles = get_option('mollom_roles');
      $detected = array_intersect($user->roles, $mollom_roles);
      if (count($detected) > 0) {
        return $comment;
      }
    }

    $map = array(
        'postTitle' => NULL,
        'postBody' => 'comment_content',
        'authorName' => 'comment_author',
        'authorMail' => 'comment_author_email',
        'authorUrl' => 'comment_author_url',
        'authorId' => 'user_ID',
    );
    $data = array();
    foreach ($map as $param => $key) {
      if (isset($comment[$key]) && $comment[$key] !== '') {
        $data[$param] = $comment[$key];
      }
    }
    // Add the author IP, support for reverse proxy
    $data['authorIp'] = $this->fetch_author_ip();
    // Add contextual information for the commented on post.
    $data['contextUrl'] = get_permalink();
    $data['contextTitle'] = get_the_title($comment['comment_post_ID']);
    // Trackbacks cannot handle CAPTCHAs; the 'unsure' parameter controls
    // whether a 'unsure' response asking for a CAPTCHA is possible.
    $data['unsure'] = (int) ($comment['comment_type'] != 'trackback');

    $mollom = self::get_mollom_instance();
    $result = $mollom->checkContent($data);

    // Trigger global fallback behavior if there is a unexpected result.
    if (!is_array($result) || !isset($result['id'])) {
      return $this->mollom_fallback($comment);
    }

    // Profanity check
    if (isset($result['profanityScore']) && $result['profanityScore'] >= 0.5) {
      wp_die(__('Your submission has triggered the profanity filter and will not be accepted until the inappropriate language is removed.'), __('Comment blocked'));
    }
    
    $result = array();
    $result['spamClassification'] = 'unsure';

    if ($result['spamClassification'] == 'spam') {
      wp_die(__('Your submission has triggered the spam filter and will not be accepted.'), __('Comment blocked'));
      return;
    } elseif ($result['spamClassification'] == 'unsure') {
      // @todo Retrieve and check CAPTCHA.
      $comment['mollom_session_id'] = $result['session_id'];
      $comment['mollom_quality'] = $result['quality'];
      self::mollom_show_captcha($comment);
    } elseif ($result['spamClassification'] == 'ham') {
      // @todo Associate the Mollom session information with the comment
      return $comment;
    }

    return $comment;
  }

  /**
   * Handles the fallback scenarios when the Mollom service is not available.
   *
   * @param array $comment
   */
  private function mollom_fallback($comment) {
    // Do nothing if posts shall be accepted in case of a service outage.
    if (get_option('mollom_fallback_mode', 'accept') == 'accept') {
      return $comment;
    }

    $title = __('Your comment was blocked', MOLLOM_I18N);
    $msg = __("The spam filter installed on this site is currently unavailable. Per site policy, we are unable to accept new submissions until that problem is resolved. Please try resubmitting the form in a couple of minutes.", MOLLOM_I18N);
    wp_die($msg, $title);
  }
    
  /**
   * Show the CAPTCHA form
   */
  private function mollom_show_captcha($comment) {
    self::mollom_include('common.inc');
    // 1. Set the audio and image captcha
    // 2. Build the form fields
    // 3. Cache the form (assign a unique form ID)
    // 4. Show the rendered form
    mollom_theme('show_captcha', $vars);
    die();
  }
  
  /**
   * Retrieve a CAPTCHA
   */
  private function mollom_get_captcha($type, $comment) {
    $mollom = self::get_mollom_instance();
    $result = $mollom->createCaptcha(array('type' => $type));
  }

  /**
   * Fetch the IP address of the user who posts data to Mollom
   *
   * This function tries to retrieve the correct IP address of a user posting data
   * to Mollom. Since an IP address can be hidden through a reverse proxy, we need to resolve
   * this correctly by parsing the http incoming request.
   * First we try to determine if the request matches a list of proxies, if yes, substitute
   * with the HTTP_X_FORWARDED_FOR property.
   * Second we'll look if this site runs in a clustered environment. If yes, substitute with
   * the HTTP_X_CLUSTER_CLIENT_IP property.
   *
   * @return string
   *   The IP of the host from which the request originates
   */
  private function fetch_author_ip() {
    $reverse_proxy_option = get_option('mollom_reverseproxy_addresses', '');
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (!empty($reverse_proxy_option)) {
      $reverse_proxy_addresses = explode(',', $reverse_proxy_option);
      if (!empty($reverse_proxy_addresses)) {
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
          if (in_array($ip_address, $reverse_proxy_addresses, TRUE)) {
            // If there are several arguments, we need to check the most
            // recently added one, ie the last one.
            $ip_address = array_pop(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
          }
        }
      }
    }

    // If WP is run in a clustered environment
    if (array_key_exists('HTTP_X_CLUSTER_CLIENT_IP', $_SERVER)) {
      $ip_address = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    }

    return $ip_address;
  }

}

// Gone with the wind
WPMollom::get_instance();
