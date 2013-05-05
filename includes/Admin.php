<?php

/**
 * @file
 * Administrative functionality.
 */

/**
 * Defines administrative methods.
 */
class MollomAdmin {

  const NONCE = 'mollom-configuration';

  public static function init() {
    self::registerSettings();
    // @todo Remove.
//    self::redirect();
  }

  /**
   * Registers options for the WP Settings API.
   *
   * @see http://codex.wordpress.org/Settings_API
   */
  public static function registerSettings() {
    add_settings_section('mollom_keys', 'API keys', '__return_false', 'mollom');
    add_settings_section('mollom_options', 'Protection options', '__return_false', 'mollom');
    add_settings_section('mollom_dev', 'Testing', '__return_false', 'mollom');

    add_settings_field('mollom_public_key', 'Public key', array('MollomForm', 'printInputArray'), 'mollom', 'mollom_keys', array(
      'type' => 'text',
      'name' => 'mollom_public_key',
      'value' => get_option('mollom_public_key'),
      'required' => NULL,
      'size' => 40,
      'maxlength' => 32,
    ));
    add_settings_field('mollom_private_key', 'Private key', array('MollomForm', 'printInputArray'), 'mollom', 'mollom_keys', array(
      'type' => 'text',
      'name' => 'mollom_private_key',
      'value' => get_option('mollom_private_key'),
      'required' => NULL,
      'size' => 40,
      'maxlength' => 32,
    ));

    add_settings_field('mollom_checks', 'Checks', array('MollomForm', 'printItemsArray'), 'mollom', 'mollom_options', array(
      'type' => 'checkboxes',
      'name' => 'mollom_checks',
      'options' => array(
        'spam' => 'Spam',
        'profanity' => 'Profanity',
      ),
      'values' => get_option('mollom_checks'),
    ));
    add_settings_field('mollom_privacy_link', 'Privacy policy link', array('MollomForm', 'printItemArray'), 'mollom', 'mollom_options', array(
      'type' => 'checkbox',
      'name' => 'mollom_privacy_link',
      'label' => "Link to Mollom's privacy policy",
      'value' => (int) get_option('mollom_privacy_link'),
      'description' => vsprintf(__('Displays a link to the recommended <a href="%s">privacy policy on mollom.com</a> on all protected forms. When disabling this option, you are required to inform visitors about data privacy through other means, as stated in the <a href="%s">terms of service</a>.'), array(
        '@privacy-policy-url' => '//mollom.com/web-service-privacy-policy',
        '@terms-of-service-url' => '//mollom.com/terms-of-service',
      )),
    ));

    add_settings_field('mollom_developer_mode', 'Testing mode', array('MollomForm', 'printItemArray'), 'mollom', 'mollom_dev', array(
      'type' => 'checkbox',
      'name' => 'mollom_developer_mode',
      'label' => 'Enable Mollom testing mode',
      'value' => (int) get_option('mollom_developer_mode'),
      // @todo Sanitize.
      'description' => 'Submitting "ham", "unsure", or "spam" on a protected form will trigger the corresponding behavior. Image CAPTCHAs will only respond to "correct" and audio CAPTCHAs only respond to "demo". This option should be disabled in production environments.',
    ));

    // Mollom client configuration.
    register_setting('mollom', 'mollom_public_key', 'trim');
    register_setting('mollom', 'mollom_private_key', 'trim');
    register_setting('mollom', 'mollom_developer_mode', 'intval');
    register_setting('mollom', 'mollom_reverse_proxy_addresses');

    register_setting('mollom', 'mollom_checks');
    register_setting('mollom', 'mollom_privacy_link');

    register_setting('mollom', 'mollom_roles');
    register_setting('mollom', 'mollom_fallback_mode');
  }

  /**
   * Registers administration pages.
   *
   * @see http://codex.wordpress.org/Administration_Menus
   */
  public static function registerPages() {
    add_options_page('Mollom settings', 'Mollom', 'manage_options', 'mollom', array(__CLASS__, 'settingsPage'));

    add_action('manage_comments_custom_column', array(__CLASS__, 'mollom_comment_column_row'), 10, 2);
    add_filter('manage_edit-comments_columns', array(__CLASS__, 'mollom_comments_columns'));
  }

  /**
   * Enqueues files for inclusion in the head of a page
   */
  public static function enqueueScripts($hook) {
    // Add CSS for the comment listing page.
    if ($hook == 'edit-comments.php') {
      wp_enqueue_style('wp-mollom', MOLLOM_PLUGIN_URL . '/css/mollom-admin.css');
    }
  }

  /**
   * Redirect the user when editing comments
   *
   * Redirect the user to http://my.mollom.com to moderate comments instead of the regular
   * Wordpress comment moderation system at edit-comments.php. The setting is "Remote moderation"
   * is configurated at the Mollom tab under General options
   */
  public static function redirect() {
    $location = basename($_SERVER['PHP_SELF']);
    if (($location == 'edit-comments.php') && (get_option('mollom_moderation_redirect', 'off') == 'on')) {
      wp_redirect('http://my.mollom.com');
    }
  }

  /**
   * Page callback; Presents the Mollom settings options page.
   */
  public static function settingsPage() {
    $mollom = mollom();

    // When requesting the page, and after updating the settings, verify the
    // API keys (unless empty).
    if (empty($_POST)) {
      $error = FALSE;
      if (!get_option('mollom_public_key') || !get_option('mollom_private_key')) {
        $error = __('The Mollom API keys are not configured yet.', MOLLOM_I18N);
      }
      elseif (TRUE !== $result = $mollom->verifyKeys()) {
        // Bad request: Invalid client system time: Too large offset from UTC.
        if ($result === Mollom::REQUEST_ERROR) {
          $error = vsprintf(__('The server time of this site is incorrect. The time of the operating system is not synchronized with the Coordinated Universal Time (UTC), which prevents a successful authentication with Mollom. The maximum allowed offset is %d minutes. Please consult your hosting provider or server operator to correct the server time.', MOLLOM_I18N), array(
            Mollom::TIME_OFFSET_MAX / 60,
          ));
        }
        // Invalid API keys.
        elseif ($result === Mollom::AUTH_ERROR) {
          $error = __('The configured Mollom API keys are invalid.', MOLLOM_I18N);
        }
        // Communication error.
        elseif ($result === Mollom::NETWORK_ERROR) {
          $error = __('The Mollom servers could not be contacted. Please make sure that your web server can make outgoing HTTP requests.', MOLLOM_I18N);
        }
        // Server error.
        elseif ($result === Mollom::RESPONSE_ERROR) {
          $error = __('The Mollom API keys could not be verified. Please try again later.', MOLLOM_I18N);
        }
        else {
          $error = __('The Mollom servers could be contacted, but the Mollom API keys could not be verified.', MOLLOM_I18N);
        }
      }
      if ($error) {
        add_settings_error('mollom', 'mollom_keys', $error, 'error');
      }
      else {
        $status = __('Mollom servers verified your keys. The services are operating correctly.', MOLLOM_I18N);
        add_settings_error('mollom', 'mollom_keys', $status, 'updated');
      }
      settings_errors('mollom');
    }
    mollom_theme('configuration', array());
    return;


    if (isset($_POST['submit'])) {
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
      // Protection mode
      update_option('mollom_protection_mode', $_POST['protection_mode']['mode']);
      // Redirect to http://my.mollom.com
      update_option('mollom_moderation_redirect', !empty($_POST['moderation_redirect']) ? 'on' : 'off');
    }

    // Set variables used to render the page.
    $vars['mollom_reverseproxy_addresses'] = get_option('mollom_reverseproxy_addresses', '');
    $vars['mollom_roles'] = self::mollom_roles_element();
    $vars['mollom_protection_mode'] = self::mollom_protection_mode();
    $vars['mollom_fallback_mode'] = (get_option('mollom_fallback_mode', 'accept') == 'block') ? ' checked="checked"' : '';
    $vars['mollom_moderation_redirect'] = (get_option('mollom_moderation_redirect', 'on') == 'on') ? ' checked="checked"' : '';

    // Render the page.
    mollom_theme('configuration', $vars);
  }

  /**
   * Helper function. Generate an <ul> list of roles
   *
   * @global type $wp_roles
   * @return string
   */
  protected static function mollom_roles_element() {
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
   * Helper function
   *
   * Generate a checked=checked item for the captcha/analysis checkboxes on the configuration screen
   *
   * @todo refactor this
   *
   * @return string
   */
  protected static function mollom_protection_mode() {
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
  public static function mollom_comment_column_row($column, $comment_id) {
    if ($column != 'mollom')
      return;

    mollom_include('comment.class.inc');
    $mollom_comment = new MollomComment();
    $object = $mollom_comment->get($comment_id);

    $vars['spam_classification'] = $object->spamClassification;

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
  public static function mollom_comments_columns($columns) {
    $columns['mollom'] = __('Mollom score', MOLLOM_I18N);
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
  public static function send_feedback($comment_ID, $comment_status) {
    if ($comment_status == 'spam') {
      mollom_include('comment.class.inc');
      $mollom_comment = new MollomComment();
      $object = $mollom_comment->get($comment_ID);
      if ($object) {
        $data = array(
          'reason' => 'spam',
          'contentId' => $object->content_ID,
        );
        $mollom = mollom();
        $mollom->sendFeedback($data);
        // @todo Find a way to display feedback as an admin notice in the interface.
      }
    }
  }

  /**
   * Callback. Delete the comment record form the mollom table
   *
   * When a comment is deleted from the system, the corresponding record
   * in the mollom table should be purged too.
   *
   * @param unknown_type $comment_ID
   */
  public static function delete_comment($comment_ID) {
    mollom_include('comment.class.inc');
    $mollom_comment = new MollomComment();
    $mollom_comment->delete($comment_ID);
  }
}
