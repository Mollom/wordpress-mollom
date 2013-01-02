<?php

/**
 * WPMollomAdmin
 *
 * Contains all the functionality to administer and configure WP Mollom.
 */
class WPMollomAdmin extends WPMollomBase {

  private $mollom_nonce = 'mollom-configuration';

  /**
   * Constructor
   *
   * Upon instantiation, we'll hook up the base methods of this class to actions as
   * callbacks. Lazyload anything extra in the methods themselves.
   */
  public function __construct() {
    parent::__construct();
    // register the redirect to My Mollom
    add_action('admin_init', array(&$this, 'moderation_redirect'));
    // register the administration page
    add_action('admin_menu', array(&$this, 'register_administration_pages'));
    // Enqueue our scripts
    add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
    // register the comment feedback when managing comments
    add_action('wp_set_comment_status', array(&$this, 'send_feedback'));
    add_action('delete_comment', array(&$this, 'delete_comment'));
    //add_filter('comment_row_actions', array(&$this, 'comment_actions'));
  }

  /**
   * Enqueues files for inclusion in the head of a page
   *
   * This function is called through the wp_enqueue_scripts action hook.
   */
  public function admin_enqueue_scripts() {
    // Add an extra CSS file. But only on the wp-comments-edit.php page
    wp_enqueue_style('wp-mollom', '/wp-content/plugins/wp-mollom/wp-mollom.css');
  }

  /**
   * Redirect the user when editing comments
   *
   * Redirect the user to http://my.mollom.com to moderate comments instead of the regular
   * Wordpress comment moderation system at edit-comments.php. The setting is "Remote moderation"
   * is configurated at the Mollom tab under General options
   */
  public function moderation_redirect() {
    $location = basename($_SERVER['PHP_SELF']);
    if (($location == 'edit-comments.php') && (get_option('mollom_moderation_redirect', 'off') == 'on')) {
      wp_redirect('http://my.mollom.com');
    }
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
      // Protection mode
      update_option('mollom_protection_mode', $_POST['protection_mode']['mode']);
      // Redirect to http://my.mollom.com
      update_option('mollom_moderation_redirect', !empty($_POST['moderation_redirect']) ? 'on' : 'off');
      // Content analysis strategies
      $analysis_types = $_POST['mollom_analysis_types'];
      if (empty($analysis_types)) {
        $analysis_types = array('spam');
      } else {
        $analysis_types + array('spam');
      }
      update_option('mollom_analysis_types', $analysis_types);
      // Show privacy notice
      update_option('mollom_privacy_notice', !empty($_POST['privacy_notice']) ? 'on' : 'off');
  
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
    $vars['mollom_protection_mode'] = $this->mollom_protection_mode();
    $vars['mollom_analysis_types'] = $this->mollom_analysis_types_element();
    $vars['mollom_developer_mode'] = (get_option('mollom_developer_mode', 'on') == 'on') ? ' checked="checked"' : '';
    $vars['mollom_fallback_mode'] = (get_option('mollom_fallback_mode', 'accept') == 'block') ? ' checked="checked"' : '';
    $vars['mollom_moderation_redirect'] = (get_option('mollom_moderation_redirect', 'on') == 'on') ? ' checked="checked"' : '';
    $vars['mollom_privacy_notice'] = (get_option('mollom_privacy_notice', 'on') == 'on') ? ' checked="checked"' : '';
  
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
   * Helper function.
   *
   * Generates a list of checkboxes with different analysis types.
   *
   * @return string
   */
  private function mollom_analysis_types_element() {
    $map = array(
        'spam' => __('Spam', MOLLOM_I18N),
        'profanity' => __('Profanity', MOLLOM_I18N),
    );
    $mollom_check_types = get_option('mollom_analysis_types', array());
    $element = "<ul>";
  
    foreach ($map as $key => $label) {
      if ($mollom_check_types) {
        $checked = (in_array($key, $mollom_check_types)) ? "checked" : "";
      }
      $element .= "<li><input type=\"checkbox\" name=\"mollom_analysis_types[]\" value=\"" . $key . "\" " . $checked . " /> " . $label . "</li>";
    }
  
    $element .= "</ul>";
  
    return $element;
  }
  
  /**
   * Helper function
   *
   * Generate a checked=checked item for the captcha/analysis checkboxes on the configuration screen
   *
   * @todo refactor this
   *
   * @return string
   */
  private function mollom_protection_mode() {
    $mollom_protection_mode = get_option('mollom_protection_mode', MOLLOM_MODE_ANALYSIS);
    $mollom_parsed = array(
        'analysis' => '',
        'spam' => '',
    );
  
    if ($mollom_protection_mode['mode'] == MOLLOM_MODE_ANALYSIS) {
      $mollom_parsed['analysis'] = ' checked="checked"';
    }
    elseif ($mollom_protection_mode['mode'] == MOLLOM_MODE_CAPTCHA) {
      $mollom_parsed['spam'] = ' checked="checked"';
    }
  
    return $mollom_parsed;
  }
  
  /**
   * Callback. Show Mollom actions in the Comments table
   *
   * Show Mollom action links and status messages per commentinthe comments table.
   *
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
   * Callback. Send feedback to Mollom on moderation
   *
   * When moderating comments from edit-comments.php, this callback will send
   * feedback if a comment status changes to 'trash', 'spam', 'hold', 'approve'.
   *
   * @param unknown_type $comment_ID
   * @param unknown_type $comment_status
   */
  public function send_feedback($comment_ID, $comment_status) {
  }

  /**
   * Callback. Delete the comment record form the mollom table
   *
   * When a comment is deleted from the system, the corresponding record
   * in the mollom table should be purged too.
   *
   * @param unknown_type $comment_ID
   */
  public function delete_comment($comment_ID) {
    global $wpdb;

    $count = $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . MOLLOM_TABLE . " WHERE comment_ID = %d", $comment_ID));
  }
}