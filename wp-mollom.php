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

if (!function_exists('add_action')) {
  header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not found');
  exit;
}

/**
 * define this version of the plugin
 */
define('MOLLOM_PLUGIN_VERSION', '2.x-dev');

/**
 *  define WP Mollom's i18n namespace
 */
define('MOLLOM_I18N', 'wp-mollom');

/** 
 * define the plugin path
 */
define('MOLLOM_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Use plugins_url() instead of plugin_dir_url() to avoid trailing slash.
define('MOLLOM_PLUGIN_URL', plugins_url('', __FILE__));

/**
 * define WP Mollom table where mollom data per comment gets stored
 */
define( 'MOLLOM_TABLE', 'mollom' );

/**
 * define WP Mollom table where mollom cache data gets stored
 */
define( 'MOLLOM_CACHE_TABLE', 'mollom_cache' );

/**
 * Define the version of the mollom tables
 */
define( 'MOLLOM_TABLE_VERSION', '2000');

/**
 *  Define the life time a cached form.
 */
define( 'MOLLOM_FORM_ID_LIFE_TIME', 300);

/**
 * Seconds that must have passed by for the same author to post again.
 */
define( 'MOLLOM_CAPTCHA_RATE_LIMIT', 15);

/**
 * Form protection mode: no protection
 */
define( 'MOLLOM_MODE_DISABLED', 0);

/**
 * Form protection mode: text analysis with CAPTCHA fallback
 */
define ( 'MOLLOM_MODE_ANALYSIS', 1);

/**
 * Form protection mode: CAPTCHA only protection
 */
define( 'MOLLOM_MODE_CAPTCHA', 2);

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
 * Common functions are stored in common.inc file. These are made available
 * throughout the entire plugin.
 */
require_once(MOLLOM_PLUGIN_PATH . '/includes/common.inc');

/**
 * Instantiates a new Mollom client (once).
 */
function mollom() {
  static $instance;

  require_once dirname(__FILE__) . '/lib/mollom.class.inc';

  $class = 'MollomWordpress';

  if (get_option('mollom_developer_mode', FALSE)) {
    $class = 'MollomWordpressTest';
  }
  // If there is no instance yet or if it is not of the desired class, create a
  // new one.
  if (!isset($instance) || !($instance instanceof $class)) {
    $instance = new $class();
  }
  return $instance;
}

/**
 * Factory class.
 * 
 * WP Mollom has a componentized architecture since not all the functionality
 * needs to be loaded everytime a request is made. When we are on the frontend,
 * the content checkinking class is loaded, if we are in the backend, only the
 * administration class with moderation, configuration,... functionality gets
 * loaded.
 */
class WPMollomFactory {
  static private $instance = NULL;

  public static function get_instance() {
    mollom_include('WPMollomBase.class.inc');
    if (!self::$instance) {
      mollom_include('WPMollomContent.class.inc');
      self::$instance = new WPMollomContent();
    }

    return self::$instance;
  }

  
  /**
   * Callback.
   *
   * Called on activation of the plugin. This hook will install and register the
   * Mollom tables in the database.
   */
  public static function activate() {
    // Table definition for MOLLOM_TABLE
    $mollom_tbl_definition = "
    `comment_ID` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
    `content_ID` VARCHAR( 128 ) NOT NULL DEFAULT '',
    `captcha_ID` VARCHAR( 128 ) NOT NULL DEFAULT '',
    `form_ID` VARCHAR( 255 ) NULL DEFAULT NULL,
    `moderate` TINYINT ( 1 ) NOT NULL DEFAULT '0',
    `changed` INT ( 10 ) NOT NULL DEFAULT '0',
    `spamScore` FLOAT NULL DEFAULT '0.00',
    `spamClassification` VARCHAR( 255 ) NULL DEFAULT NULL,
    `solved` TINYINT ( 1 ) NULL DEFAULT NULL,
    `profanityScore` FLOAT NULL DEFAULT '0.00',
    `reason` VARCHAR( 255 ) NULL DEFAULT NULL,
    `languages` VARCHAR( 255 ) NULL DEFAULT NULL,
    UNIQUE (
    `comment_ID` ,
    `content_ID`
    )";
  
    // Tabel definition for MOLLOM_CACHE_TABLE
    $mollom_cache_tbl_definition = "
    `created` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
    `form_id` VARCHAR( 40 ) NULL DEFAULT NULL,
    `key` VARCHAR( 128 ) NULL DEFAULT NULL,
    UNIQUE (
    `created`,
    `form_id`
    )";
  
    mollom_table_install(MOLLOM_TABLE, MOLLOM_TABLE_VERSION, $mollom_tbl_definition);
    mollom_table_install(MOLLOM_CACHE_TABLE, MOLLOM_TABLE_VERSION, $mollom_cache_tbl_definition);
  }
  
}

// Register the activation callback
register_activation_hook(__FILE__, array('WPMollomFactory', 'activate'));

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
add_action('wp_set_comment_status', array('MollomAdmin', 'send_feedback'), 10, 2);

// Register callback to delete associated mollom information
add_action('delete_comment', array('MollomAdmin', 'delete_comment'));
//add_filter('comment_row_actions', array('MollomAdmin', 'comment_actions'));



// @todo Move into comment form bucket.

add_filter('comment_form_default_fields', function ($fields) {
  $values = (isset($_POST['mollom']) ? $_POST['mollom'] : array());
  $values += array(
    'contentId' => '',
    'captchaId' => '',
    'homepage' => '',
  );
  $fields['mollom'] = MollomForm::formatInput('hidden', 'mollom[contentId]', $values['contentId']);
  $fields['mollom'] .= MollomForm::formatInput('hidden', 'mollom[captchaId]', $values['captchaId']);
  $fields['mollom'] .= '<div class="hidden">';
  $fields['mollom'] .= MollomForm::formatInput('text', 'mollom[homepage]', $values['homepage']);
  $fields['mollom'] .= '</div>';
  if (!empty($_POST['mollom']['captchaId'])) {
    // @todo Automatically retrieve a new CAPTCHA in case captchaUrl doesn't
    //   exist for whatever reason?
    $output = '<div>';
    $output .= '<img src="' . $_POST['mollom']['captchaUrl'] . '" alt="Type the characters you see in this picture." />';
    $output .= '</div>';
    $output .= MollomForm::formatInput('text', 'mollom[solution]', '', array('required' => NULL, 'size' => 10));
    $fields['mollom'] .= MollomForm::formatItem('text', __('Word verification'), $output);
  }
  return $fields;
});

add_filter('preprocess_comment', 'mollom_preprocess_comment', 0);
function mollom_preprocess_comment($comment) {
  $author_data = array(
    'authorName' => $comment['comment_author'],
    'authorMail' => $comment['comment_author_email'],
    'authorUrl' => $comment['comment_author_url'],
    // @todo ip_address()
    'authorIp' => $_SERVER['REMOTE_ADDR'],
  );
  if (!empty($comment['user_ID'])) {
    $author_data['authorId'] = $comment['user_ID'];
  }
  if ($_POST['mollom']['homepage'] !== '') {
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
    'postBody' => $comment['comment_content'],
    'contextUrl' => get_permalink(),
    'contextTitle' => get_the_title($comment['comment_post_ID']),
  );
  if ($comment['comment_type'] == 'trackback') {
    $data['unsure'] = FALSE;
  }
  $result = mollom()->checkContent($data);

  if (!is_array($result) || !isset($result['id'])) {
    if (get_option('mollom_fallback_mode', 'accept') == 'accept') {
      return $comment;
    }
    $title = __('Comment not posted', MOLLOM_I18N);
    $msg = __('The spam filter installed on this site is currently unavailable. Per site policy, we are unable to accept new submissions until that problem is resolved. Please try resubmitting the form in a couple of minutes.', MOLLOM_I18N);
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
      $errors[] = __('Your submission has triggered the spam filter and will not be accepted.', MOLLOM_I18N);
      // @todo False-positive report link.
    }
    // Unsure: Require to solve a CAPTCHA.
    elseif ($result['spamClassification'] == 'unsure') {
      // UX: Don't make the user believe that there's a bug or endless loop by
      // presenting a different error message, depending on whether we already
      // showed a CAPTCHA previously or not.
      if (empty($_POST['mollom']['captchaId'])) {
        $errors[] = __('To complete this form, please complete the word verification below.', MOLLOM_I18N);
      }
      else {
        $errors[] = __('The word verification was not completed correctly. Please complete this new word verification and try again.', MOLLOM_I18N);
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
    $errors[] = __('Your submission has triggered the profanity filter and will not be accepted until the inappropriate language is removed.', MOLLOM_I18N);
  }

  // If there are errors, re-render the page containing the form.
  if ($errors) {
    $_POST['_errors'] = $errors;
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

add_action('comment_form_before', 'mollom_form_before', -100);
function mollom_form_before() {
  if (empty($_POST['mollom'])) {
    return;
  }
  ob_start();
}

add_action('comment_form_after', 'mollom_form_after', 100);
function mollom_form_after() {
  if (empty($_POST['mollom'])) {
    return;
  }
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
  $dom = filter_dom_load($output);
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
  // After form#commentform anchor/jump-target, but before form fields.
  $form = $dom->getElementsByTagName('form')->item(0);
  $errors = $dom->createElement('div');
  $errors->setAttribute('class', 'p messages error');
  if (count($_POST['_errors']) == 1) {
    $errors->nodeValue = $_POST['_errors'][0];
  }
  else {
    $list = $dom->createElement('ul');
    foreach ($_POST['_errors'] as $message) {
      $list->appendChild($dom->createElement('li', $message));
    }
    $errors->appendChild($list);
  }
  $form->insertBefore($errors, $form->firstChild);

  // Output the form again.
  echo filter_dom_serialize($dom);
}

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

add_filter('comment_form_defaults', function ($options) {
  if (get_option('mollom_privacy_link', TRUE)) {
    $options['comment_notes_after'] .= "\n";
    $options['comment_notes_after'] .= '<p class="description">';
    $options['comment_notes_after'] .= vsprintf(__('By submitting this form, you accept the <a href="%s" target="_blank" rel="nofollow">Mollom privacy policy</a>.', MOLLOM_I18N), array(
      '//mollom.com/web-service-privacy-policy',
    ));
    $options['comment_notes_after'] .= '</p>';
  }
  return $options;
});

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
 * Parses an HTML snippet and returns it as a DOM object.
 *
 * This function loads the body part of a partial (X)HTML document and returns
 * a full DOMDocument object that represents this document. You can use
 * filter_dom_serialize() to serialize this DOMDocument back to a XHTML
 * snippet.
 *
 * @param $text
 *   The partial (X)HTML snippet to load. Invalid markup will be corrected on
 *   import.
 *
 * @return
 *   A DOMDocument that represents the loaded (X)HTML snippet.
 */
function filter_dom_load($text) {
  $dom_document = new DOMDocument();
  // Ignore warnings during HTML soup loading.
  @$dom_document->loadHTML('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>' . $text . '</body></html>');

  return $dom_document;
}

/**
 * Converts a DOM object back to an HTML snippet.
 *
 * The function serializes the body part of a DOMDocument back to an XHTML
 * snippet. The resulting XHTML snippet will be properly formatted to be
 * compatible with HTML user agents.
 *
 * @param $dom_document
 *   A DOMDocument object to serialize, only the tags below
 *   the first <body> node will be converted.
 *
 * @return
 *   A valid (X)HTML snippet, as a string.
 */
function filter_dom_serialize($dom_document) {
  $body_node = $dom_document->getElementsByTagName('body')->item(0);
  $body_content = '';
  foreach ($body_node->childNodes as $child_node) {
    $body_content .= $dom_document->saveXML($child_node);
  }
  return $body_content;
}
