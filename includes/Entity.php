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

  public function __construct($entity_type) {
    $this->type = $entity_type;
  }

  public function getType() {
    return $this->type;
  }

  /**
   * Adds HTML for Mollom form fields to a given array of form fields.
   *
   * @param array $args
   *   An associative array whose keys are input field names and whose values
   *   are raw HTML representations of the form fields to output.
   *
   * @return array
   *   $args with additional 'mollom' key (containing HTML markup for multiple
   *   form elements).
   *
   * @todo $options is specific to comment_form().
   */
  public static function buildFormArray($args) {
    //$args['mollom'] = self::buildForm();
    $args['comment_notes_after'] .= self::buildForm();
    return $args;
  }

  /**
   * Generates HTML markup for Mollom form elements.
   *
   * @return string
   *   HTML markup for multiple form elements.
   */
  public static function buildForm() {
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

    add_filter('site_url', array('MollomEntity', 'alterFormAction'), 10, 2);

    return $output;
  }

  /**
   * Callback for 'site_url' hook.
   *
   * Manipulates the form 'action' attribute value.
   *
   * @see comment_form()
   * @see site_url()
   * @see get_site_url()
   */
  public static function alterFormAction($url, $path) {
    if ($path === '/wp-comments-post.php') {
      $url .= '#commentform';
    }
    return $url;
  }

  public static function validateForm($comment) {
    // Exclude all posts performed from the administrative interface.
    if (is_admin()) {
      return $comment;
    }
    // Check whether the user has any of the bypass access roles.
    $user = wp_get_current_user();
    $bypass_roles = array_keys(array_filter((array) get_option('mollom_bypass_roles', array())));
    if (array_intersect($user->roles, $bypass_roles)) {
      return $comment;
    }

    add_action('comment_post', array('MollomEntity', 'saveMeta'));

    $author_data = array(
      'authorName' => $comment['comment_author'],
      'authorMail' => $comment['comment_author_email'],
      'authorUrl' => $comment['comment_author_url'],
      'authorIp' => ip_address(),
    );
    if (!empty($comment['user_ID'])) {
      $author_data['authorId'] = $comment['user_ID'];
    }
    if (isset($_POST['mollom']['homepage']) && $_POST['mollom']['homepage'] !== '') {
      $author_data['honeypot'] = $_POST['mollom']['homepage'];
    }

    // Check (unsure) CAPTCHA solution.
    if (!empty($_POST['mollom']['captchaId'])) {
      $data = array(
        'id' => $_POST['mollom']['captchaId'],
        'solution' => isset($_POST['mollom']['solution']) ? $_POST['mollom']['solution'] : '',
      );
      $data += $author_data;
      $captcha_result = mollom()->checkCaptcha($data);

      unset($_POST['mollom']['solution']);
    }

    // Check content.
    $data = array();
    // Ensure to pass existing content ID if we have one already.
    if (!empty($_POST['mollom']['contentId'])) {
      $data['id'] = $_POST['mollom']['contentId'];
    }
    $data += $author_data;
    // These parameters should be sent regardless of whether they are empty.
    $data += array(
      'checks' => array_keys(get_option('mollom_checks', array('spam' => 1))),
      'postBody' => isset($comment['comment_content']) ? $comment['comment_content'] : '',
      'contextUrl' => get_permalink(),
      'contextTitle' => get_the_title($comment['comment_post_ID']),
    );
    if (isset($comment['comment_type']) && $comment['comment_type'] == 'trackback') {
      $data['unsure'] = FALSE;
    }
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
    $_POST['mollom']['contentId'] = $result['id'];

    $errors = array();

    // Handle the spam classification result:
    if (isset($result['spamClassification'])) {
      $_POST['mollom']['spamClassification'] = $result['spamClassification'];

      // Spam: Discard the post.
      if ($result['spamClassification'] == 'spam') {
        $errors[] = __('Your submission has triggered the spam filter and will not be accepted.', MOLLOM_L10N);
        // @todo False-positive report link.
      }
      // Unsure: Require to solve a CAPTCHA.
      elseif ($result['spamClassification'] == 'unsure') {
        // UX: Don't make the user believe that there's a bug or endless loop by
        // presenting a different error message, depending on whether we already
        // showed a CAPTCHA previously or not.
        if (empty($_POST['mollom']['captchaId'])) {
          $errors[] = __('To complete this form, please complete the word verification below.', MOLLOM_L10N);
        }
        else {
          $errors[] = __('The word verification was not completed correctly. Please complete this new word verification and try again.', MOLLOM_L10N);
        }
        // Retrieve a new CAPTCHA, assign the captchaId, and pass the full
        // response to the form constructor.
        $captcha_result = mollom()->createCaptcha(array(
          'type' => 'image',
          'contentId' => $_POST['mollom']['contentId'],
        ));
        $_POST['mollom']['captchaId'] = $captcha_result['id'];
        $_POST['mollom']['captchaUrl'] = $captcha_result['url'];
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
      $errors[] = __('Your submission has triggered the profanity filter and will not be accepted until the inappropriate language is removed.', MOLLOM_L10N);
    }

    // If there are errors, re-render the page containing the form.
    if ($errors) {
      $_POST['_errors'] = $errors;
      add_action('wp_enqueue_scripts', array('MollomForm', 'enqueueScripts'));

      // @see http://codex.wordpress.org/Function_Reference/WP_Query
      $post = query_posts('p=' . $comment['comment_post_ID']);
      // @see template-loader.php
      $template = get_single_template();
      include $template;
      // Prevent wp_new_comment() from processing this POST further.
      exit;
    }

    $comment['mollom_content_id'] = $result['id'];
    return $comment;
  }

  public static function saveMeta($id) {
    if (empty($_POST['mollom']['contentId'])) {
      return;
    }
    // @todo Abstract this.
    // Store the contentId separately to enable reverse-mapping lookups for CMP.
    add_metadata('comment', $id, 'mollom_content_id', $_POST['mollom']['contentId']);
    add_metadata('comment', $id, 'mollom', $_POST['mollom']);
  }

}
