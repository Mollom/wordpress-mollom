<?php

/**
 * WPMollomContent
 *
 * Contains all the functionality to check content against the Mollom API.
 */
class WPMollomContent extends WPMollomBase {
  private $mollom_comment = array();

  /**
   * Constructor
   *
   * Upon instantiation, we'll hook up the base methods of this class to actions as
   * callbacks. Lazyload anything extra in the methods themselves.
   */
  public function __construct() {
    parent::__construct();
    // pass comments through Mollom during processing
    add_filter('preprocess_comment', array(&$this, 'check_comment'));
    // Enqueue our scripts
    add_action('wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts'));
  }

  /**
   * Enqueues files for inclusion in the head of a page
   *
   * This function is called through the wp_enqueue_scripts action hook.
   */
  public function wp_enqueue_scripts() {
    // Add jquery. We'll need it when we're on our CAPTCHA page
    wp_enqueue_script('jquery');
    wp_enqueue_script('js/wp-mollom', plugins_url('js/wp-mollom.js', __FILE__), array('jquery'), '1.0', true);
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

    // Wordpress doesn't expose the raw POST data to its API. It strips
    // extra information off and only returns the comment fields. WE
    // introduce the missing information back into the flow.
    $protection_mode = get_option('mollom_protection_mode', MOLLOM_MODE_ANALYSIS);
    $this->mollom_comment = array(
        'captcha_passed' => FALSE,
        'require_analysis' => ($protection_mode == MOLLOM_MODE_ANALYSIS),
        'require_captcha' => ($protection_mode == MOLLOM_MODE_CAPTCHA),
    );
    $this->mollom_comment += self::mollom_set_fields($_POST, $comment);

    // Get a mollom instance
    $mollom = self::get_mollom_instance();

    // Text analysis is required. Depending on the outcome, appropriate action
    // is taken
    if ($this->mollom_comment['require_analysis']) {
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
      // If the contentId exists, the data is merely rechecked.
      // One case where this could happen is when a CAPTCHA is validated
      // Rather then storing the analysis data clientside, we retrieve
      // it again from the API since changes to the content must be
      // validated again.
      if (isset($this->mollom_comment['contentId'])) {
        $data['contentId'] = $this->mollom_comment['contentId'];
      }
      // Add the author IP, support for reverse proxy
      $data['authorIp'] = $this->fetch_author_ip();
      // Add contextual information for the commented on post.
      $data['contextUrl'] = get_permalink();
      $data['contextTitle'] = get_the_title($comment['comment_post_ID']);
      // Trackbacks cannot handle CAPTCHAs; the 'unsure' parameter controls
      // whether a 'unsure' response asking for a CAPTCHA is possible.
      $data['unsure'] = (int) ($comment['comment_type'] != 'trackback');
      // A string denoting the check to perform.
      $data['checks'] = get_option('mollom_analysis_types', array('spam'));

      $result = $mollom->checkContent($data);

      // Hook Mollom data to our mollom comment
      $this->mollom_comment['analysis'] = $result;
      // Trigger global fallback behavior if there is a unexpected result.
      if (!is_array($result) || !isset($result['id'])) {
        return $this->mollom_fallback($comment);
      }

      // Profanity check
      if (isset($result['profanityScore']) && $result['profanityScore'] >= 0.5) {
        wp_die(__('Your submission has triggered the profanity filter and will not be accepted until the inappropriate language is removed.'), __('Comment blocked'));
      }

      // Spam check
      if ($result['spamClassification'] == 'spam') {
        wp_die(__('Your submission has triggered the spam filter and will not be accepted.', MOLLOM_I18N), __('Comment blocked', MOLLOM_I18N));
        return;
      }
      elseif ($result['spamClassification'] == 'unsure') {
        // If a captchaId exists, this was probably a POST request from the
        // CAPTCHA form and we must validate the CAPTCHA
        if ($this->mollom_comment['captchaId']) {
          $this->mollom_check_captcha();
        }
        if (!$this->mollom_comment['captcha_passed']) {
          $this->mollom_show_captcha();
        }
      }
      elseif ($result['spamClassification'] == 'ham') {
        // Fall through
      }
    }

    // The plugin runs in CAPTCHA mode. Text analysis is skipped and a CAPTCHA is always
    // shown to the end user
    if ($this->mollom_comment['require_captcha']) {
      // If a captchaId exists, this was probably a POST request from the
      // CAPTCHA form and we must validate the CAPTCHA
      if ($this->mollom_comment['captchaId']) {
        $this->mollom_check_captcha();
      }
      if (!$this->mollom_comment['captcha_passed']) {
        $this->mollom_show_captcha();
      }
    }

    add_action('comment_post', array(&$this, 'mollom_save_comment'), 1, 1);

    return $comment;
  }

  /**
   * Save the comment to the database
   *
   * @param  $comment_ID
   * @return array The comment
   */
  public function mollom_save_comment($comment_ID) {

    mollom_include('comment.inc');

    /*
     array(13) {
    ["captcha_passed"]=> bool(true)
    ["comment_post_ID"]=> string(1) "1"
    ["author"]=> string(7) "zrtezre"
    ["url"]=> string(0) ""
    ["email"]=> string(19) "qsdfjkm@etrytey.com"
    ["comment"]=> string(6) "unsure"
    ["comment_parent"]=> string(1) "0"
    ["mollom_solution"]=> string(7) "correct"
    ["captchaId"]=> string(12) "1fxq7w7hbx0x"
    ["contentId"]=> string(12) "1fxq7w7hbx0x"
    ["form_id"]=> string(40) "a59f0c655cbd68646f296a47a4cefc9cb82915b1"
    ["analysis"]=> array(8) {
    ["id"]=> string(12) "1fxq7w7hbx0x"
    ["spamScore"]=> string(3) "0.5"
    ["reason"]=> string(18) "some secret reason"
    ["postBody"]=> string(6) "unsure"
    ["authorMail"]=> string(19) "qsdfjkm@etrytey.com"
    ["authorIp"]=> string(9) "127.0.0.1"
    ["authorId"]=> string(1) "0"
    ["spamClassification"]=> string(6) "unsure"
    }
    ["captcha"]=> array(3) {
    ["id"]=> string(12) "1fxq7w7hbx0x"
    ["solved"]=> string(1) "1"
    ["reason"]=> string(0) ""
    }
    }
    */

    $data = array(
        'comment_ID' => $comment_ID,
        'content_ID' => $this->mollom_comment['contentId'],
        'captcha_ID' => $this->mollom_comment['captchaId'],
        'form_ID' => $this->mollom_comment['form_id'],
        'moderate' => 0,
        'changed' => REQUEST_TIME,
        'spamScore' => $this->mollom_comment['analysis']['spamScore'],
        'spamClassification' => $this->mollom_comment['analysis']['spamClassification'],
        'solved' => $this->mollom_comment['captcha']['solved'],
        'profanityScore' => 0.0,
        'reason' => '',
        'languages' => '',
    );

    // Save the comment
    $mollom_comment = new MollomComment();
    $mollom_comment->create($data);

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
   * Helper function. This function preprocesses and renders the CAPTCHA form
   */
  private function mollom_show_captcha() {
    // 1. Generate the audio and image captcha
    $mollom = self::get_mollom_instance();
    $data = array(
        'contentId' => $this->mollom_comment['analysis']['id'],
        'ssl' => FALSE,
    );

    $data['type'] = 'image';
    $image = $mollom->createCaptcha($data);

    $data['type'] = 'audio';
    $audio = $mollom->createCaptcha($data);

    // The image id and the audio id are essentially the same. But we can't be
    // sure that the API throws back something different. In that case, we'll go
    // with the id returned from our last API call.
    $this->mollom_comment['captchaId'] = ($image['id'] == $audio['id']) ? $image['id'] : $audio['id'];
    $variables['mollom_image_captcha'] = $image['url'];
    $variables['mollom_audio_captcha'] = WP_PLUGIN_URL . '/wp-mollom/assets/mollom-captcha-player.swf?url=' . str_replace('%2F', '/', rawurlencode($audio['url']));

    // 2. Build the form
    $this->mollom_comment['contentId'] = $this->mollom_comment['analysis']['id'];
    $variables['attached_form_fields'] = self::mollom_get_fields($this->mollom_comment);

    // 3. Cache the form (assign a unique form ID)
    $variables['form_id'] = self::mollom_form_id($this->mollom_comment);

    $variables['show_privacy_notice'] = (get_option('mollom_privacy_notice', 'on') == 'on') ? TRUE : FALSE;

    // 4. Show the rendered form and kill any further processing of the comment
    mollom_theme('show_captcha', $variables);
    die();
  }

  /**
   * Validates the submitteded CAPTCHA solution
   *
   * The CAPTCHA solution is send back to Mollom for validation. Depending
   * on the result, the comment will be rejected or admitted. This function
   * works in two stages:
   *  - Validation against replay attacks and CSFR
   *  - Validation of the CAPTCHA solution
   */
  private function mollom_check_captcha() {
    // Replay attack and CSRF validation
    if (!isset($this->mollom_comment['form_id'])) {
      return FALSE;
    }

    if (!self::mollom_check_form_id($this->mollom_comment)) {
      return FALSE;
    }

    // Check the solution with Mollom
    $mollom = self::get_mollom_instance();

    $data = array(
        'id' => $this->mollom_comment['captchaId'],
        'solution' => $this->mollom_comment['mollom_solution'],
        'authorName' => $this->mollom_comment['author'],
        'authorUrl' => $this->mollom_comment['url'],
        'authorMail' =>$this->mollom_comment['email'],
        'authorIp' => self::fetch_author_ip(),
        'rateLimit' => MOLLOM_CAPTCHA_RATE_LIMIT,
    );
    $result = $mollom->checkCaptcha($data);

    // Hook data to the comment
    $this->mollom_comment['captcha'] = $result;
    $this->mollom_comment['captchaId'] = $result['id'];

    // No session id was specified
    if ($result !== FALSE) {
      if ($result['solved'] == TRUE) {
        $this->mollom_comment['captcha_passed'] = TRUE;
      }
    }
  }

  /**
   * Generates a form id.
   *
   * The form id is used as a hidden field for the captcha form. The id is stored
   * server side with a timestamp. When the response comes back. Validation of the input
   * includes checking if the id exists and the form was submitted within a reasonable
   * timeframe. This prevents replay attacks.
   *
   * @return string A hash of the current time + a random number
   */
  private function mollom_form_id($comment) {
    mollom_include('cache.inc');

    $time = current_time('timestamp');

    // Calculate the HMAC. The key is a random generated salted hash
    $key = wp_hash(mt_rand() . current_time('timestamp'), 'nonce');
    $data = $comment['author'] . '|' . $comment['email'] . '|' . $comment['url'] . '|' . $comment['comment'] . '|' . $key;
    $form_id = hash_hmac('sha1', $data, $key);

    // Store it in the cache
    $cache = new MollomCache();
    if (!$cache->create($time, $form_id, $key)) {
      return FALSE;
    }

    return $form_id;
  }

  /**
   * Checks the form id
   *
   * This function performs to validation checks. First, the form id should be in the
   * cache and second, the form id should not be older then an hour. If both criteria
   * are satisfied, the form id is removed from the cache and the function returns TRUE
   * Otherwise, it returns FALSE;
   *
   * @param string The form id to be checked
   * @return boolean TRUE if valid, FALSE if invalid
   */
  private function mollom_check_form_id($comment) {
    mollom_include('cache.inc');

    $cache = new MollomCache();

    // Clear the cache table of older entries first
    // Acts as a sort of Poormans cron to keep things clean
    $time = current_time('timestamp');
    $cache->clear($time, MOLLOM_FORM_ID_LIFE_TIME);

    // Perform the check
    if ($cached_data = $cache->exists($comment['form_id'])) {
      $data = $comment['author'] . '|' . $comment['email'] . '|' . $comment['url'] . '|' . $comment['comment'] . '|' . $cached_data->key;
      $hmac = hash_hmac('sha1', $data, $cached_data->key);
      if (($cached_data->created + MOLLOM_FORM_ID_LIFE_TIME) >= current_time('timestamp') && ($cached_data->form_id == $hmac)) {
        $cache->delete($cached_data->form_id);
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * This is a helper function. Get all the applicable comment fields from
   * $_POST and $comment and put them in one array before passing on to
   * show_captcha()
   *
   * @param array $post the $_POST array
   * @param array $comment the $comment array which is passed through the add_action hook
   */
  private function mollom_set_fields($post = array(), $comment = array()) {
    $mollom_comment = array(
        'comment_post_ID' => $comment['comment_post_ID'],
        'author' => $comment['comment_author'],
        'url' => $comment['comment_author_url'],
        'email' => $comment['comment_author_email'],
        'comment' => $comment['comment_content'],
        'comment_parent' => $comment['comment_parent']
    );

    $omitted = array('submit');

    // add possible extra fields to the $mollom_comment array
    foreach ($post as $key => $value) {
      if ((!array_key_exists($key, array_keys($mollom_comment))) && (!in_array($key, $omitted))) {
        $mollom_comment[$key] = $value;
      }
    }

    return $mollom_comment;
  }

  /**
   * Generate HTML hidden fields from an array.
   *
   * This is a helper function. A comment yield extra data attached by other
   * plugins. We don't want to lose that information. We generate the data as a
   * a set of hidden fields and display them in the CAPTCHA form. All fields
   * except email/url are sanitized against non-western encoding sets.
   *
   * @param array $comment
   *   an array with fields where key is the name of the field and value is the
   *   value of the field
   *
   * @return string
   *   A string containing the rendered hidden fields.
   */
  private function mollom_get_fields($comment = array()) {
    $output = '';

    foreach ($comment as $key => $value) {
      // While processing, the old form_id will be processed again. We prevent
      // it from rendering here.
      $omitted = array('analysis', 'form_id', 'mollom_solution');
      if (in_array($key, $omitted)) {
        continue;
      }

      // sanitize for non-western encoding sets. Only URL and e-mail adress are
      // exempted. Extra non-wp fields are included.
      switch ($key) {
        case 'url':
        case 'email':
          break;
        default: {
          $charset = get_option('blog_charset');
          $value = htmlspecialchars(stripslashes($value), ENT_COMPAT, $charset);
          break;
        }
      }

      // output the value to a hidden field
      $output .= '<input type="hidden" name= "' . $key . '" value = "' . $value . '" />';
    }

    return $output;
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
