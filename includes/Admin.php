<?php

/**
 * @file
 * Administrative functionality.
 */

/**
 * Defines administrative methods.
 */
class MollomAdmin {

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

    register_setting('mollom', 'mollom_checks');
    register_setting('mollom', 'mollom_privacy_link', 'intval');

    register_setting('mollom', 'mollom_bypass_roles');
    register_setting('mollom', 'mollom_fallback_mode');

    // Configuration sections.
    add_settings_section('mollom_keys', 'API keys', '__return_false', 'mollom');
    add_settings_section('mollom_options', 'Protection options', '__return_false', 'mollom');
    add_settings_section('mollom_advanced', 'Advanced settings', '__return_false', 'mollom');

    // API keys section.
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

    // Protection options section.
    add_settings_field('mollom_checks', 'Checks', array('MollomForm', 'printItemsArray'), 'mollom', 'mollom_options', array(
      'type' => 'checkboxes',
      'name' => 'mollom_checks',
      'options' => array(
        'spam' => 'Spam',
        'profanity' => 'Profanity',
      ),
      'values' => get_option('mollom_checks'),
    ));
    add_settings_field('mollom_bypass_roles', 'Bypass roles', array('MollomForm', 'printItemsArray'), 'mollom', 'mollom_options', array(
      'type' => 'checkboxes',
      'name' => 'mollom_bypass_roles',
      'options' => array_map('translate_user_role', $GLOBALS['wp_roles']->get_names()),
      'values' => get_option('mollom_bypass_roles'),
      'description' => __('Select user roles to exclude from all Mollom checks.'),
    ));
    add_settings_field('mollom_fallback_mode', 'When Mollom is down', array('MollomForm', 'printItemsArray'), 'mollom', 'mollom_options', array(
      'type' => 'radios',
      'name' => 'mollom_fallback_mode',
      'options' => array(
        'block' => 'Block all form submissions',
        'accept' => 'Accept all form submissions',
      ),
      'value' => get_option('mollom_fallback_mode', 'accept'),
      'description' => vsprintf(__('In case Mollom services are unreachable, no text analysis can be performed and no CAPTCHAs can be generated. Customers on <a href="%s">paid plans</a> have access to <a href="%s">Mollom\'s high-availability backend infrastructure</a>, not available to free users, reducing potential downtime.', MOLLOM_L10N), array(
        '//mollom.com/web-service-privacy-policy',
        '//mollom.com/terms-of-service',
      )),
    ));
    add_settings_field('mollom_privacy_link', 'Privacy policy link', array('MollomForm', 'printItemArray'), 'mollom', 'mollom_options', array(
      'type' => 'checkbox',
      'name' => 'mollom_privacy_link',
      'label' => "Link to Mollom's privacy policy",
      'value' => get_option('mollom_privacy_link'),
      'description' => vsprintf(__('Displays a link to the recommended <a href="%s">privacy policy on mollom.com</a> on all protected forms. When disabling this option, you are required to inform visitors about data privacy through other means, as stated in the <a href="%s">terms of service</a>.', MOLLOM_L10N), array(
        '@privacy-policy-url' => '//mollom.com/web-service-privacy-policy',
        '@terms-of-service-url' => '//mollom.com/terms-of-service',
      )),
    ));

    // Advanced section.
    add_settings_field('mollom_reverse_proxy_addresses', 'Reverse proxy IP addresses', array('MollomForm', 'printItemArray'), 'mollom', 'mollom_advanced', array(
      'type' => 'text',
      'name' => 'mollom_reverse_proxy_addresses',
      'value' => get_option('mollom_reverse_proxy_addresses'),
      'size' => 60,
      'description' => __('If your site resides behind one or more reverse proxies, enter their IP addresses as a comma-separated list.'),
    ));
    add_settings_field('mollom_testing_mode', 'Testing mode', array('MollomForm', 'printItemArray'), 'mollom', 'mollom_advanced', array(
      'type' => 'checkbox',
      'name' => 'mollom_testing_mode',
      'label' => 'Enable Mollom testing mode',
      'value' => get_option('mollom_testing_mode'),
      // @todo Sanitize.
      'description' => __('Submitting "ham", "unsure", or "spam" on a protected form will trigger the corresponding behavior. Image CAPTCHAs will only respond to "correct" and audio CAPTCHAs only respond to "demo". This option should be disabled in production environments.', MOLLOM_L10N),
    ));
  }

  /**
   * Registers administration pages.
   *
   * @see http://codex.wordpress.org/Administration_Menus
   */
  public static function registerPages() {
    add_options_page('Mollom settings', 'Mollom', 'manage_options', 'mollom', array(__CLASS__, 'settingsPage'));
  }

  /**
   * Enqueues files for inclusion in the head of a page
   */
  public static function enqueueScripts($hook) {
    // Add CSS for the comment listing page.
    if ($hook == 'edit-comments.php') {
      wp_enqueue_style('mollom', MOLLOM_PLUGIN_URL . '/css/mollom-admin.css');
    }
  }

  /**
   * Page callback; Presents the Mollom settings options page.
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
