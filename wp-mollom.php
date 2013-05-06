<?php

/*
  Plugin Name: Mollom
  Plugin URI: http://mollom.com
  Version: 2.x-dev
  Text Domain: mollom
  Description: Protects you from spam and unwanted posts. <strong>Get started:</strong> 1) <em>Activate</em>, 2) <a href="//mollom.com/pricing">Sign up</a> and create API keys, 3) Set them in <a href="options-general.php?page=mollom">settings</a>.
  Author: Matthias Vandermaesen
  Author URI: http://www.colada.be
  License: GPLv2 or later
*/

if (!function_exists('add_action')) {
  header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not found');
  exit;
}

/**
 * Localization text domain.
 */
define('MOLLOM_L10N', 'mollom');

// Use plugins_url() instead of plugin_dir_url() to avoid trailing slash.
define('MOLLOM_PLUGIN_URL', plugins_url('', __FILE__));

spl_autoload_register('mollom_classloader');

/**
 * Loads Mollom* classes.
 *
 * @see spl_autoload_register()
 */
function mollom_classloader($class) {
  if (strpos($class, 'Mollom') === 0) {
    $dir = dirname(__FILE__);
    // Literal classname as includes/MollomFoo.php.
    if (file_exists($dir . "/includes/$class.php")) {
      include_once $dir . "/includes/$class.php";
    }
    // Disambiguated classname as includes/Foo.php.
    else {
      include_once $dir . '/includes/' . substr($class, 6) . '.php';
    }
  }
}

/**
 * Instantiates a new Mollom client (once).
 */
function mollom() {
  static $instance;

  require_once dirname(__FILE__) . '/lib/mollom.class.inc';

  $class = 'MollomWordpress';

  if (get_option('mollom_testing_mode', FALSE)) {
    $class = 'MollomWordpressTest';
  }
  // If there is no instance yet or if it is not of the desired class, create a
  // new one.
  if (!isset($instance) || !($instance instanceof $class)) {
    $instance = new $class();
  }
  return $instance;
}

// Note: Unlike code examples in Codex, we do not (ab)use object-oriented
// programming for more than clean organization and automated loading of code,
// unless WP Core learns how to use and adopt OO patterns in a proper way.
// @see http://phptherightway.com
if (is_admin()) {
  add_action('admin_init', array('MollomAdmin', 'init'));
  add_action('admin_menu', array('MollomAdmin', 'registerPages'));
  add_action('admin_enqueue_scripts', array('MollomAdmin', 'enqueueScripts'));
}

// register the comment feedback when managing comments
add_action('wp_set_comment_status', array('MollomAdmin', 'sendFeedback'), 10, 2);

// Register callback to delete associated mollom information
//add_filter('comment_row_actions', array('MollomAdmin', 'comment_actions'));



// @todo Move into comment form bucket.

add_filter('comment_form_default_fields', array('MollomForm', 'addMollomFields'));

add_filter('preprocess_comment', 'mollom_preprocess_comment', 0);
function mollom_preprocess_comment($comment) {
  // Exclude all posts performed from the administrative interface.
  if (is_admin()) {
    return $comment;
  }
  $user = wp_get_current_user();
  $bypass_roles = array_keys(array_filter((array) get_option('mollom_bypass_roles', array())));
  if (array_intersect($user->roles, $bypass_roles)) {
    return $comment;
  }

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
    $result = mollom()->checkCaptcha($data);

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
    $title = __('Comment not posted', MOLLOM_L10N);
    $msg = __('The spam filter installed on this site is currently unavailable. Per site policy, we are unable to accept new submissions until that problem is resolved. Please try resubmitting the form in a couple of minutes.', MOLLOM_L10N);
    wp_die($msg, $title);
  }

  // Output the new contentId to include it in the next form submission attempt.
  $_POST['mollom']['contentId'] = $result['id'];

  $errors = array();

  // If we checked for spam, handle the spam classification result:
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

add_action('comment_form_before', array('MollomForm', 'beforeFormRendering'), -100);
add_action('comment_form_after', array('MollomForm', 'afterFormRendering'), 100);

// @see comment_form()
// @see site_url()
// @see get_site_url()
add_filter('site_url', 'mollom_filter_site_url', 10, 2);
function mollom_filter_site_url($url, $path) {
  if ($path === '/wp-comments-post.php') {
    $url .= '#commentform';
  }
  return $url;
}

add_filter('comment_form_defaults', array('MollomForm', 'formatPrivacyPolicyLink'));

add_action('comment_post', 'mollom_entity_save_meta');
function mollom_entity_save_meta($id) {
  if (empty($_POST['mollom']['contentId'])) {
    return;
  }
  // @todo Abstract this.
  // Store the contentId separately to enable reverse-mapping lookups for CMP.
  add_metadata('comment', $id, 'mollom_content_id', $_POST['mollom']['contentId']);
  add_metadata('comment', $id, 'mollom', $_POST['mollom']);
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
function ip_address() {
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
