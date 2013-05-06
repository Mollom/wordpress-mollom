<?php

/**
 * @file
 * Entity wrapping and form mapping logic.
 */

/**
 * Defines generic base definitions and methods shared across all entity types.
 */
abstract class MollomEntity {

  protected $type;

  protected $errors;

  protected $isPrivileged;

  public function __construct() {
    $this->errors = new WP_Error();
  }

  public function getType() {
    return $this->type;
  }

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
   */
  public function buildForm() {
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

    $output .= '<div class="hidden">';
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
      $output .= MollomForm::formatItem('text', __('Word verification', MOLLOM_L10N), $captcha);
    }

    if (get_option('mollom_privacy_link', TRUE)) {
      $output .= "\n";
      $output .= '<p class="description">';
      $output .= vsprintf(__('By submitting this form, you accept the <a href="%s" target="_blank" rel="nofollow">Mollom privacy policy</a>.', MOLLOM_L10N), array(
        '//mollom.com/web-service-privacy-policy',
      ));
      $output .= '</p>';
    }

    add_filter('site_url', array($this, 'alterFormAction'), 10, 2);

    return $output;
  }

  /**
   * Callback for 'site_url' hook.
   *
   * Manipulates the form 'action' attribute value.
   *
   * @see site_url()
   * @see get_site_url()
   */
  public function alterFormAction($url, $path) {
    return $url;
  }

  public function validateForm($data) {
    if ($this->isPrivileged()) {
      return $data;
    }
    if (isset($_POST['mollom']['homepage']) && $_POST['mollom']['homepage'] !== '') {
      $data['honeypot'] = $_POST['mollom']['homepage'];
    }

    $author_data = array_intersect_key($data, array_flip(array('authorName', 'authorMail', 'authorUrl', 'authorIp', 'authorId', 'honeypot')));

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
      'checks' => array_keys(get_option('mollom_checks', array('spam' => 1))),
    );

    $result = mollom()->checkContent($data);

    if (!is_array($result) || !isset($result['id'])) {
      if (get_option('mollom_fallback_mode', 'accept') == 'accept') {
        return $comment;
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

    $report_url = '//mollom.com/false-positive?' . http_build_query($params);
    return sprintf(__('If you feel this is in error, please <a href="%s" target="_blank">report that you are blocked</a>.', MOLLOM_L10N), htmlspecialchars($report_url, ENT_QUOTES, 'UTF-8'));
  }

  /**
   * Form pre-render callback.
   *
   * Starts output buffering for MollomForm::afterFormRendering().
   */
  public function beforeFormRendering() {
    ob_start();
  }

  /**
   * Form post-render callback.
   *
   * Re-injects previously submitted POST values back into a newly rendered form.
   */
  public function afterFormRendering() {
    // Retrieve the captured form output.
    $output = ob_get_contents();
    ob_end_clean();
  
    // Prepare all POST parameter values for re-injection.
    $values = array();
    foreach (explode('&', http_build_query($_POST)) as $param) {
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

  public function saveMetaData($id) {
    if (empty($_POST['mollom']['contentId'])) {
      return;
    }
    add_metadata($this->getType(), $id, 'mollom', $_POST['mollom']);
    // Store the contentId separately to enable reverse-mapping lookups for CMP.
    add_metadata($this->getType(), $id, 'mollom_content_id', $_POST['mollom']['contentId']);
  }

}
