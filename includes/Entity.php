<?php

/**
 * @file
 * Entity wrapping and form mapping logic.
 */

/**
 * Defines generic base definitions and methods shared across all entity types.
 */
abstract class MollomEntity {

  /**
   * The (WP) entity type (e.g., 'comment').
   *
   * @var string
   */
  protected $type;

  /**
   * A WP_Error class instance for the to be processed entity.
   *
   * @var WP_Error
   */
  protected $errors;

  /**
   * Whether the current user is allowed to bypass Mollom protection.
   *
   * @var bool
   */
  protected $isPrivileged;

  /**
   * Constructs a new MollomEntity object.
   */
  public function __construct() {
    $this->errors = new WP_Error();
  }

  /**
   * Returns the WP entity type to which this instance relates to.
   *
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Returns whether the current user is allowed to bypass the Mollom protection.
   *
   * @return bool
   */
  public function isPrivileged() {
    if (isset($this->isPrivileged)) {
      return $this->isPrivileged;
    }
    $this->isPrivileged = FALSE;
    // Exclude all posts performed from the administrative interface.
    if (is_admin()) {
      $this->isPrivileged = TRUE;
    }
    else {
      // Check whether the user has any of the bypass access roles.
      $user = wp_get_current_user();
      $bypass_roles = array_keys(array_filter((array) get_option('mollom_bypass_roles', array())));
      if (array_intersect($user->roles, $bypass_roles)) {
        $this->isPrivileged = TRUE;
      }
    }
    return $this->isPrivileged;
  }

  /**
   * Generates HTML markup for Mollom form elements.
   *
   * @return string
   *   HTML markup for multiple form elements.
   *
   * @see MollomEntity::alterFormAction()
   */
  public function buildForm($fields) {
    if ($this->isPrivileged()) {
      return '';
    }
    $values = (isset($_POST['mollom']) ? $_POST['mollom'] : array());
    $values += array(
      'contentId' => '',
      'captchaId' => '',
      'homepage' => '',
    );
    $output = '';
    $output .= MollomForm::formatInput('hidden', 'mollom[contentId]', $values['contentId']);
    $output .= MollomForm::formatInput('hidden', 'mollom[captchaId]', $values['captchaId']);

    $output .= '<div style="display: none;">';
    $output .= MollomForm::formatInput('text', 'mollom[homepage]', $values['homepage']);
    $output .= '</div>';

    if (!empty($_POST['mollom']['captchaId'])) {
      // @todo Automatically retrieve a new CAPTCHA in case captchaUrl doesn't
      //   exist for whatever reason?
      $attributes = array(
        'src' => $_POST['mollom']['captchaUrl'],
        'alt' => __('Type the characters you see in this picture.', MOLLOM_L10N),
      );
      $attributes['title'] = $attributes['alt'];

      $captcha = '<div>';
      $captcha .= '<img ' . MollomForm::formatAttributes($attributes) . '/>';
      $captcha .= '</div>';
      $captcha .= MollomForm::formatInput('text', 'mollom[solution]', '', array(
        'required' => NULL,
        'size' => 10,
      ));

      $output .= "\n";
      $output .= MollomForm::formatItem('text', __('Word verification', MOLLOM_L10N), $captcha, __('Type the characters in the image above (not case-sensitive).', MOLLOM_L10N), array(
        'item' => array('class' => array('mollom-captcha'))
      ));
    }

    if (get_option('mollom_privacy_link', TRUE)) {
      $output .= "\n";
      $output .= '<p class="description mollom-privacy-policy">';
      $output .= '<small>';
      $output .= vsprintf(__('By submitting this form, you accept the <a href="%s" target="_blank" rel="nofollow">Mollom privacy policy</a>.', MOLLOM_L10N), array(
        'https://mollom.com/web-service-privacy-policy',
      ));
      $output .= '</small>';
      $output .= '</p>';
    }

    add_filter('site_url', array($this, 'alterFormAction'), 10, 2);

    return $output;
  }

  /**
   * site_url callback.
   *
   * Manipulates the form 'action' attribute value.
   *
   * @see site_url()
   * @see get_site_url()
   */
  public function alterFormAction($url, $path) {
    return $url;
  }

  /**
   * Validates user-submitted form values.
   *
   * @param array $data
   *   An associative array containing entity-type-specific parameters for the
   *   Mollom Content API. The following paramters are handled internally and do
   *   not need to be provided by the entity-type-specific implementation:
   *   - contentId
   *   - captchaId
   *   - authorIp
   *   - honeypot
   *   - solution
   *   For all available parameters, see Mollom::checkContent().
   *
   * @return array
   *   The passed-in $data array, with potential additions. Use
   *   MollomEntity::hasErrors() to check for validation errors.
   */
  public function validateForm($data) {
    if ($this->isPrivileged()) {
      return $data;
    }
    // Unescape all user input.
    // Most entity-specific values in $data are derived from WP core's bogusly
    // munged user input or helper functions that equally munge data objects.
    $data = MollomForm::unescapeUserInput($data);

    $data += $this->getAuthorParams();

    if (isset($_POST['mollom']['homepage']) && $_POST['mollom']['homepage'] !== '') {
      $data['honeypot'] = $_POST['mollom']['homepage'];
    }

    $author_data = array_intersect_key($data, array_flip(array('authorIp', 'authorId', 'authorCreated', 'authorName', 'authorMail', 'authorUrl', 'honeypot')));

    // Check (unsure) CAPTCHA solution.
    if (!empty($_POST['mollom']['captchaId'])) {
      $data['captchaId'] = $_POST['mollom']['captchaId'];
      $captcha_data = array(
        'id' => $_POST['mollom']['captchaId'],
        'solution' => isset($_POST['mollom']['solution']) ? $_POST['mollom']['solution'] : '',
      );
      $captcha_data += $author_data;
      $captcha_result = mollom()->checkCaptcha($captcha_data);

      unset($_POST['mollom']['solution']);
    }

    // Check content.
    // Ensure to pass existing content ID if we have one already.
    if (!empty($_POST['mollom']['contentId'])) {
      $data['id'] = $_POST['mollom']['contentId'];
    }
    // These parameters should be sent regardless of whether they are empty.
    $data += array(
      'unsure' => (get_option('mollom_unsure', 'captcha') == 'captcha') ? 1 : 0,
      'checks' => array_keys(get_option('mollom_checks', array('spam' => 1))),
    );

    $result = mollom()->checkContent($data);

    if (!is_array($result) || !isset($result['id'])) {
      if (get_option('mollom_fallback_mode', 'accept') == 'accept') {
        return $data;
      }
      $title = __('Service unavailable', MOLLOM_L10N);
      $msg = __('The spam filter installed on this site is currently unavailable. Per site policy, we are unable to accept new submissions until that problem is resolved. Please try resubmitting the form in a couple of minutes.', MOLLOM_L10N);
      wp_die($msg, $title);
    }

    // Output the new contentId to include it in the next form submission attempt.
    $data['contentId'] = $_POST['mollom']['contentId'] = $result['id'];
    $data += $result;

    // Handle the spam classification result:
    if (isset($result['spamClassification'])) {
      $_POST['mollom']['spamClassification'] = $result['spamClassification'];

      // Spam: Discard the post.
      if ($result['spamClassification'] == 'spam') {
        $this->errors->add('spam', __('Your submission has triggered the spam filter and will not be accepted.', MOLLOM_L10N) . ' ' . $this->formatFalsePositiveLink($data));
      }
      // Unsure: Require to solve a CAPTCHA.
      elseif ($result['spamClassification'] == 'unsure') {
        // UX: Don't make the user believe that there's a bug or endless loop by
        // presenting a different error message, depending on whether we already
        // showed a CAPTCHA previously or not.
        if (empty($_POST['mollom']['captchaId'])) {
          $this->errors->add('unsure', __('To complete this form, please complete the word verification below.', MOLLOM_L10N));
        }
        else {
          $this->errors->add('unsure', __('The word verification was not completed correctly. Please complete this new word verification and try again.', MOLLOM_L10N) . ' ' . $this->formatFalsePositiveLink($data));
        }
        // Retrieve a new CAPTCHA, assign the captchaId, and pass the full
        // response to the form constructor.
        $captcha_result = mollom()->createCaptcha(array(
          'type' => 'image',
          'contentId' => $_POST['mollom']['contentId'],
        ));
        $data['captchaId'] = $_POST['mollom']['captchaId'] = $captcha_result['id'];
        $data['captchaUrl'] = $_POST['mollom']['captchaUrl'] = $captcha_result['url'];
      }
      // Ham: Accept the post.
      else {
        // Ensure the CAPTCHA validation above is not re-triggered after a
        // previous 'unsure' response.
        $_POST['mollom']['captchaId'] = NULL;
      }
    }

    // Handle the profanity classification result:
    if (isset($result['profanityScore']) && $result['profanityScore'] >= 0.5) {
      $this->errors->add('profanity', __('Your submission has triggered the profanity filter and will not be accepted until the inappropriate language is removed.', MOLLOM_L10N) . ' ' . $this->formatFalsePositiveLink($data));
    }

    return $data;
  }

  /**
   * Formats a message for end-users to report false-positives.
   *
   * @param array $data
   *   The latest Mollom session data pertaining to the form submission attempt.
   *
   * @return string
   *   A message string containing a specially crafted link to Mollom's
   *   false-positive report form, supplying these parameters:
   *   - public_key: The public API key of this site.
   *   - url: The current, absolute URL of the form.
   *   At least one or both of:
   *   - contentId: The content ID of the Mollom session.
   *   - captchaId: The CAPTCHA ID of the Mollom session.
   *   If available, to speed up and simplify the false-positive report form:
   *   - authorName: The author name, if supplied.
   *   - authorMail: The author's e-mail address, if supplied.
   */
  public function formatFalsePositiveLink($data) {
    $mollom = mollom();
    $params = array(
      'public_key' => $mollom->loadConfiguration('publicKey'),
    );
    $params += array_intersect_key($data, array_flip(array('contentId', 'captchaId', 'authorName', 'authorMail')));

    // This should be the URL of the page containing the form.
    // NOT the general URL of your site!
    $params['url'] = isset($data['contextUrl']) ? $data['contextUrl'] : site_url();

    $report_url = 'https://mollom.com/false-positive?' . http_build_query($params);
    return sprintf(__('If you feel this is in error, please <a href="%s" target="_blank">report that you are blocked</a>.', MOLLOM_L10N), htmlspecialchars($report_url, ENT_QUOTES, 'UTF-8'));
  }

  /**
   * Form pre-render callback.
   *
   * Starts output buffering for MollomForm::afterFormRendering().
   *
   * @see MollomEntity::afterFormRendering()
   */
  public function beforeFormRendering() {
    ob_start();
  }

  /**
   * Form post-render callback.
   *
   * Re-injects previously submitted POST values back into a newly rendered form.
   *
   * @see MollomEntity::beforeFormRendering()
   */
  public function afterFormRendering() {
    // Retrieve the captured form output.
    $output = ob_get_contents();
    ob_end_clean();
  
    // Prepare all POST parameter values for re-injection.
    $input = MollomForm::unescapeUserInput($_POST);
    $values = array();
    foreach (explode('&', http_build_query($input)) as $param) {
      list($key, $value) = explode('=', $param);
      $values[urldecode($key)] = urldecode($value);
    }
  
    // Re-inject all POST values into the form.
    $dom = MollomForm::loadDOM($output);
    foreach ($dom->getElementsByTagName('input') as $input) {
      if ($name = $input->getAttribute('name')) {
        if (isset($values[$name])) {
          $input->setAttribute('value', $values[$name]);
        }
      }
    }
    foreach ($dom->getElementsByTagName('textarea') as $input) {
      if ($name = $input->getAttribute('name')) {
        if (isset($values[$name])) {
          $input->nodeValue = htmlspecialchars($values[$name], ENT_QUOTES, 'UTF-8');
        }
      }
    }

    // Inject error messages.
    // After the form's ID/anchor/jump-target, but before form fields.
    if ($errors = $this->renderErrors()) {
      $form = $dom->getElementsByTagName('form')->item(0);
      $fragment = $dom->createDocumentFragment();
      $fragment->appendXML($errors);
      $form->insertBefore($fragment, $form->firstChild);
    }

    // Output the form again.
    echo MollomForm::serializeDOM($dom);
  }

  /**
   * Returns whether Mollom validation encountered any errors.
   *
   * @return bool
   */
  public function hasErrors() {
    return (bool) $this->errors->get_error_code();
  }

  /**
   * Renders WP_Error object messages into HTML.
   *
   * @see wp-login.php
   * @see _default_wp_die_handler()
   */
  public function renderErrors() {
    $messages = $this->errors->get_error_messages();
    if (empty($messages)) {
      return '';
    }
    $output = '<div class="p messages error">';
    if (count($messages) == 1) {
      $output .= $messages[0];
    }
    else {
      $output .= '<ul><li>' . implode('</li><li>', $messages) . '</li></ul>';
    }
    $output .= '</div>';
    return $output;
  }

  /**
   * Saves Mollom data for a processed entity.
   *
   * @see http://codex.wordpress.org/Metadata_API
   */
  public function save($id) {
    if (empty($_POST['mollom']['contentId'])) {
      return;
    }
    // Notify that the entity was stored.
    $data = array(
      'id' => $_POST['mollom']['contentId'],
      'stored' => 1,
    );
    // Special-case: Supply the authorId for newly registered users.
    if ($this->getType() == 'user') {
      $data['authorId'] = $id;
    }
    mollom()->checkContent($data);
    // Save meta data.
    add_metadata($this->getType(), $id, 'mollom', $_POST['mollom']);

    // Store the reverse-mapping.
    // @todo Remove after double-checking WP_Query meta data support.
    // @see MollomEntity::sendFeedback()
    add_metadata($this->getType(), $id, 'mollom_content_id', $_POST['mollom']['contentId']);

    global $wpdb;
    $updated = $wpdb->update($wpdb->prefix . 'mollom',
      array('content_id' => $_POST['mollom']['contentId']),
      array('entity_type' => $this->getType(), 'entity_id' => $id),
      array('%s'),
      array('%s', '%d')
    );
    if (!$updated) {
      $wpdb->insert($wpdb->prefix . 'mollom',
        array(
          'entity_type' => $this->getType(),
          'entity_id' => $id,
          'content_id' => $_POST['mollom']['contentId'],
          'created' => time(),
        ),
        array('%s', '%d', '%s', '%d')
      );
    }
  }

  /**
   * Acts upon deletion of an entity.
   *
   * @param int $id
   *   The entity ID that is about to be deleted.
   */
  public function delete($id) {
    if ($contentId = get_metadata($this->getType(), $id, 'mollom_content_id', TRUE)) {
      $data = array(
        'id' => $contentId,
        'stored' => 0,
      );
      mollom()->checkContent($data);
    }
    global $wpdb;
    $table = $wpdb->prefix . 'mollom';
    $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE entity_type = '%s' AND entity_id = %d", array(
      $this->getType(),
      $id,
    )));
  }

  /**
   * Sends feedback to Mollom.
   *
   * @param int $id
   *   The entity ID.
   * @param string $status
   *   The new status of the entity.
   */
  public function sendFeedback($id, $status) {
    if ($status == 'spam' || $status == 'approve') {
      if ($contentId = get_metadata($this->getType(), $id, 'mollom_content_id', TRUE)) {
        $data = array(
          'reason' => $status,
          'contentId' => $contentId,
        );
        mollom()->sendFeedback($data);
      }
    }
  }

  /**
   * Moderates an entity.
   *
   * @param int $id
   *   The entity ID.
   * @param string $action
   *   The moderation action to perform.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function moderate($id, $action) {
    // No-op. There is no common denominator for all entity types in WP.
  }

  /**
   * Returns Mollom author parameters for the current user.
   *
   * @return array
   *   An associative array containing 'author*' parameters.
   */
  public function getAuthorParams() {
    $data = array(
      'authorIp' => self::getAuthorIp(),
    );
    $user = wp_get_current_user();
    if (empty($user->ID)) {
      return $data;
    }
    $data += array(
      'authorId' => $user->ID,
      'authorCreated' => strtotime($user->data->user_registered . ' ' . get_option('timezone_string')),
      'authorMail' => $user->data->user_email,
    );
    if (!empty($user->data->display_name)) {
      $data['authorName'] = $user->data->display_name;
    }
    else {
      $data['authorName'] = $user->data->user_login;
    }
    if (!empty($user->data->user_url)) {
      $data['authorUrl'] = $user->data->user_url;
    }
    return $data;
  }

  /**
   * Returns the IP address of the client.
   *
   * If the app is behind a reverse proxy, we use the X-Forwarded-For header
   * instead of $_SERVER['REMOTE_ADDR'], which would be the IP address of
   * the proxy server, and not the client's. The actual header name can be
   * configured by the reverse_proxy_header variable.
   *
   * @return
   *   IP address of client machine, adjusted for reverse proxy and/or cluster
   *   environments.
   *
   * @see http://api.drupal.org/api/drupal/includes!bootstrap.inc/function/ip_address/7
   */
  public static function getAuthorIp() {
    static $ip_address;

    if (!isset($ip_address)) {
      $ip_address = $_SERVER['REMOTE_ADDR'];

      if ($reverse_proxy_addresses = get_option('mollom_reverse_proxy_addresses', '')) {
        $reverse_proxy_addresses = array_filter(array_map('trim', explode(',', $reverse_proxy_addresses)));
        $reverse_proxy_header = 'HTTP_X_FORWARDED_FOR';

        if (!empty($_SERVER[$reverse_proxy_header])) {
          // If an array of known reverse proxy IPs is provided, then trust
          // the XFF header if request really comes from one of them.
          $reverse_proxy_addresses = (array) $reverse_proxy_addresses;

          // Turn XFF header into an array.
          $forwarded = explode(',', $_SERVER[$reverse_proxy_header]);

          // Trim the forwarded IPs; they may have been delimited by commas and spaces.
          $forwarded = array_map('trim', $forwarded);

          // Tack direct client IP onto end of forwarded array.
          $forwarded[] = $ip_address;

          // Eliminate all trusted IPs.
          $untrusted = array_diff($forwarded, $reverse_proxy_addresses);

          // The right-most IP is the most specific we can trust.
          $ip_address = array_pop($untrusted);
        }
      }
    }
    return $ip_address;
  }

}
