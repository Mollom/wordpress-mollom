<?php

/**
 * @file
 * Administrative functionality.
 */

/**
 * Defines administrative methods.
 */
class MollomAdmin {

  /**
   * admin_init callback.
   *
   * Registers plugin settings and administrative table column handlers.
   */
  public static function init() {
    self::registerSettings();

    add_filter('manage_edit-comments_columns', array(__CLASS__, 'registerCommentsColumn'));
    // @todo Consider to move this into MollomEntity.
    add_action('manage_comments_custom_column', array(__CLASS__, 'formatMollomCell'), 10, 2);
  }

  /**
   * Registers options for the WP Settings API.
   *
   * @see http://codex.wordpress.org/Settings_API
   * @see MollomForm
   */
  public static function registerSettings() {
    // Mollom client configuration.
    register_setting('mollom', 'mollom_public_key', 'trim');
    register_setting('mollom', 'mollom_private_key', 'trim');
    register_setting('mollom', 'mollom_testing_mode', 'intval');
    register_setting('mollom', 'mollom_reverse_proxy_addresses');

    register_setting('mollom', 'mollom_checks', array('MollomAdmin', 'sanitizeChecksValue'));
    register_setting('mollom', 'mollom_privacy_link', 'intval');

    register_setting('mollom', 'mollom_bypass_roles', array('MollomAdmin', 'sanitizeCheckboxesValue'));
    register_setting('mollom', 'mollom_fallback_mode');
    register_setting('mollom', 'mollom_unsure');

    // Configuration sections.
    add_settings_section('mollom_keys', __('API keys', MOLLOM_L10N), '__return_false', 'mollom');
    add_settings_section('mollom_options', __('Protection options', MOLLOM_L10N), '__return_false', 'mollom');
    add_settings_section('mollom_advanced', __('Advanced settings', MOLLOM_L10N), '__return_false', 'mollom');

    // API keys section.
    add_settings_field('mollom_public_key', __('Public key', MOLLOM_L10N), array('MollomForm', 'printInputArray'), 'mollom', 'mollom_keys', array(
      'type' => 'text',
      'name' => 'mollom_public_key',
      'value' => get_option('mollom_public_key'),
      'required' => NULL,
      'size' => 40,
      'maxlength' => 32,
    ));
    add_settings_field('mollom_private_key', __('Private key', MOLLOM_L10N), array('MollomForm', 'printInputArray'), 'mollom', 'mollom_keys', array(
      'type' => 'text',
      'name' => 'mollom_private_key',
      'value' => get_option('mollom_private_key'),
      'required' => NULL,
      'size' => 40,
      'maxlength' => 32,
    ));

    // Protection options section.
    add_settings_field('mollom_checks', __('Checks', MOLLOM_L10N), array('MollomForm', 'printItemsArray'), 'mollom', 'mollom_options', array(
      'type' => 'checkboxes',
      'name' => 'mollom_checks',
      'options' => array(
        'spam' => __('Spam', MOLLOM_L10N),
        'profanity' => __('Profanity', MOLLOM_L10N),
      ),
      'values' => get_option('mollom_checks'),
    ));
    // Protection options section.
    add_settings_field('mollom_unsure', __('When Mollom is unsure', MOLLOM_L10N), array('MollomForm', 'printItemsArray'), 'mollom', 'mollom_options', array(
      'type' => 'radios',
      'name' => 'mollom_unsure',
      'options' => array(
        'captcha' => __('Show a CAPTCHA (recommended)', MOLLOM_L10N),
        'binary' => __('Accept the post', MOLLOM_L10N),
      ),
      'value' => get_option('mollom_unsure', 'captcha'),
      'description' => vsprintf(__('Only a small fraction of posts are determined as unsure. <a href="%s">Mollom works best</a> by showing a CAPTCHA, since <a href="%s">Mollom CAPTCHAs are "intelligent"</a>.', MOLLOM_L10N), array(
        'https://mollom.com/how-mollom-works',
        'http://buytaert.net/mollom-captchas-are-intelligent',
      )),
    ));
    add_settings_field('mollom_bypass_roles', __('Bypass roles', MOLLOM_L10N), array('MollomForm', 'printItemsArray'), 'mollom', 'mollom_options', array(
      'type' => 'checkboxes',
      'name' => 'mollom_bypass_roles',
      'options' => array_map('translate_user_role', $GLOBALS['wp_roles']->get_names()),
      'values' => get_option('mollom_bypass_roles'),
      'description' => __('Select user roles to exclude from all Mollom checks.', MOLLOM_L10N),
    ));
    add_settings_field('mollom_fallback_mode', __('When Mollom is down', MOLLOM_L10N), array('MollomForm', 'printItemsArray'), 'mollom', 'mollom_options', array(
      'type' => 'radios',
      'name' => 'mollom_fallback_mode',
      'options' => array(
        'block' => __('Block all form submissions', MOLLOM_L10N),
        'accept' => __('Accept all form submissions', MOLLOM_L10N),
      ),
      'value' => get_option('mollom_fallback_mode', 'accept'),
      'description' => vsprintf(__('In case Mollom services are unreachable, no text analysis can be performed and no CAPTCHAs can be generated. Customers on <a href="%s">paid plans</a> have access to <a href="%s">Mollom\'s high-availability backend infrastructure</a>, not available to free users, reducing potential downtime.', MOLLOM_L10N), array(
        'https://mollom.com/pricing',
        'https://mollom.com/standard-service-level-agreement',
      )),
    ));
    add_settings_field('mollom_privacy_link', __('Privacy policy link', MOLLOM_L10N), array('MollomForm', 'printItemArray'), 'mollom', 'mollom_options', array(
      'type' => 'checkbox',
      'name' => 'mollom_privacy_link',
      'label' => __("Link to Mollom's privacy policy", MOLLOM_L10N),
      'value' => get_option('mollom_privacy_link', TRUE),
      'description' => vsprintf(__('Displays a link to the recommended <a href="%s">privacy policy on mollom.com</a> on all protected forms. When disabling this option, you are required to inform visitors about data privacy through other means, as stated in the <a href="%s">terms of service</a>.', MOLLOM_L10N), array(
        'https://mollom.com/web-service-privacy-policy',
        'https://mollom.com/terms-of-service',
      )),
    ));

    // Advanced section.
    add_settings_field('mollom_reverse_proxy_addresses', __('Reverse proxy IP addresses', MOLLOM_L10N), array('MollomForm', 'printItemArray'), 'mollom', 'mollom_advanced', array(
      'type' => 'text',
      'name' => 'mollom_reverse_proxy_addresses',
      'value' => get_option('mollom_reverse_proxy_addresses'),
      'size' => 60,
      'description' => __('If your site resides behind one or more reverse proxies, enter their IP addresses as a comma-separated list.', MOLLOM_L10N),
    ));
    add_settings_field('mollom_testing_mode', 'Testing mode', array('MollomForm', 'printItemArray'), 'mollom', 'mollom_advanced', array(
      'type' => 'checkbox',
      'name' => 'mollom_testing_mode',
      'label' => __('Enable Mollom testing mode', MOLLOM_L10N),
      'value' => get_option('mollom_testing_mode'),
      'description' => __('Submitting "ham", "unsure", or "spam" on a protected form will trigger the corresponding behavior. Image CAPTCHAs will only respond to "correct" and audio CAPTCHAs only respond to "demo". This option should be disabled in production environments.', MOLLOM_L10N),
    ));
  }

  /**
   * Settings input sanitization callback.
   *
   * Ensures that the input for (multiple-choice) checkboxes is an array.
   *
   * @param array|null $input
   *   The submitted user input.
   *
   * @return array
   *   The passed-in $input array, or an empty array if $input is NULL.
   */
  public static function sanitizeCheckboxesValue($input) {
    return is_array($input) ? $input : array();
  }

  /**
   * mollom_checks setting input sanitization callback.
   *
   * Ensures that at least one check is enabled.
   */
  public static function sanitizeChecksValue($input) {
    if (!$input = self::sanitizeCheckboxesValue($input)) {
      $input = array('spam' => '1');
    }
    return $input;
  }

  /**
   * admin_menu callback.
   *
   * Registers administration pages.
   *
   * @see http://codex.wordpress.org/Administration_Menus
   * @see MollomAdmin::settingsPage()
   */
  public static function registerPages() {
    add_options_page('Mollom settings', 'Mollom', 'manage_options', 'mollom', array(__CLASS__, 'settingsPage'));
  }

  /**
   * admin_enqueue_scripts callback.
   *
   * Enqueues files for inclusion in the head of a page.
   */
  public static function enqueueScripts($hook) {
    // Add CSS for the comment listing page.
    if ($hook == 'edit-comments.php') {
      wp_enqueue_style('mollom', MOLLOM_PLUGIN_URL . '/css/mollom-admin.css');
    }
  }

  /**
   * admin_notices callback.
   *
   * Outputs a warning when testing mode is (still) enabled.
   *
   * @see http://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
   */
  public static function testingModeWarning() {
    if (get_option('mollom_testing_mode')) {
      print '<div class="updated"><p>';
      print sprintf(__('Mollom testing mode is still enabled. <a href="%s">Disable</a> it after testing.', MOLLOM_L10N), admin_url('options-general.php?page=mollom'));
      print '</p></div>';
    }
  }

  /**
   * Page callback; Presents the Mollom settings options page.
   *
   * @see MollomAdmin::registerPages()
   */
  public static function settingsPage() {
    // When requesting the page, and after updating the settings, verify the
    // API keys (unless empty).
    if (empty($_POST)) {
      $error = FALSE;
      if (!get_option('mollom_public_key') || !get_option('mollom_private_key')) {
        $error = __('The Mollom API keys are not configured yet.', MOLLOM_L10N);
      }
      elseif (TRUE !== $result = mollom()->verifyKeys()) {
        // Bad request: Invalid client system time: Too large offset from UTC.
        if ($result === Mollom::REQUEST_ERROR) {
          $error = vsprintf(__('The server time of this site is incorrect. The time of the operating system is not synchronized with the Coordinated Universal Time (UTC), which prevents a successful authentication with Mollom. The maximum allowed offset is %d minutes. Please consult your hosting provider or server operator to correct the server time.', MOLLOM_L10N), array(
            Mollom::TIME_OFFSET_MAX / 60,
          ));
        }
        // Invalid API keys.
        elseif ($result === Mollom::AUTH_ERROR) {
          $error = __('The configured Mollom API keys are invalid.', MOLLOM_L10N);
        }
        // Communication error.
        elseif ($result === Mollom::NETWORK_ERROR) {
          $error = __('The Mollom servers could not be contacted. Please make sure that your web server can make outgoing HTTP requests.', MOLLOM_L10N);
        }
        // Server error.
        elseif ($result === Mollom::RESPONSE_ERROR) {
          $error = __('The Mollom API keys could not be verified. Please try again later.', MOLLOM_L10N);
        }
        else {
          $error = __('The Mollom servers could be contacted, but the Mollom API keys could not be verified.', MOLLOM_L10N);
        }
      }
      if ($error) {
        add_settings_error('mollom', 'mollom_keys', $error, 'error');
      }
      else {
        $status = __('Mollom servers verified your keys. The services are operating correctly.', MOLLOM_L10N);
        add_settings_error('mollom', 'mollom_keys', $status, 'updated');
      }
      settings_errors('mollom');
    }

    echo '<div class="wrap">';
    screen_icon();
    echo '<h2>' . $GLOBALS['title'] . '</h2>';
    echo '<form action="options.php" method="post">';
    settings_fields('mollom');
    do_settings_sections('mollom');
    submit_button();
    echo '</form>';
    echo '</div>';
  }

  /**
   * Registers columns for the administrative comments table.
   *
   * @param array $columns
   *   An associative array of comment management table columns.
   *
   * @return array
   *   The processed $columns array.
   *
   * @see MollomAdmin::init()
   */
  public static function registerCommentsColumn($columns) {
    $columns['mollom'] = 'Mollom';
    return $columns;
  }

  /**
   * Formats Mollom classifaction info for the administrative comments table.
   *
   * @param string $column
   *   The currently processed table column name.
   * @param int $id
   *   The currently processed entity ID.
   *
   * @return string
   *   The formatted table cell HTML content.
   *
   * @see MollomAdmin::init()
   */
  public static function formatMollomCell($column, $id) {
    if ($column != 'mollom') {
      return;
    }
    $meta = get_metadata('comment', $id, 'mollom', TRUE);

    if (isset($meta['spamClassification'])) {
      if ($meta['spamClassification'] == 'ham') {
        _e('Ham', MOLLOM_L10N);
      }
      elseif ($meta['spamClassification'] == 'unsure') {
        _e('Unsure', MOLLOM_L10N);
      }
      elseif ($meta['spamClassification'] == 'spam') {
        _e('Spam', MOLLOM_L10N);
      }
    }
    else {
      echo '—';
    }
  }

}
