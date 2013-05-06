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
    // Classname as includes/Foo.php (without 'Mollom' prefix).
    include_once dirname(__FILE__) . '/includes/' . substr($class, 6) . '.php';
  }
}

/**
 * Instantiates a new Mollom client (once).
 */
function mollom() {
  static $instance;

  // The only class that is not covered by mollom_classloader().
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


add_filter('comment_form_defaults', array('MollomEntity', 'buildFormArray'));

add_filter('preprocess_comment', array('MollomEntity', 'validateForm'), 0);
add_action('comment_form_before', array('MollomForm', 'beforeFormRendering'), -100);
add_action('comment_form_after', array('MollomForm', 'afterFormRendering'), 100);

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

add_filter('wp_die_handler', 'mollom_die_handler_callback', 100);

function mollom_die_handler_callback($function, $return_last = FALSE) {
  static $last_callback;
  if ($return_last) {
    return $last_callback;
  }
  $last_callback = $function;
  return 'mollom_die_handler';
}

function mollom_die_handler($message, $title, $args) {
  // Disable duplicate comment check when testing mode is enabled, since one
  // typically tests with the literal ham/unsure/spam strings only.
  if (get_option('mollom_testing_mode') && $message === __('Duplicate comment detected; it looks as though you&#8217;ve already said that!')) {
    return;
  }
  $function = mollom_die_handler_callback(NULL, TRUE);
  $function($message, $title, $args);
}
