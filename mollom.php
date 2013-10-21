<?php

/*
  Plugin Name: Mollom
  Plugin URI: https://mollom.com
  Version: 2.2
  Text Domain: mollom
  Description: Protects you from spam and unwanted posts. <strong>Get started:</strong> 1) <em>Activate</em>, 2) <a href="https://mollom.com/pricing">Sign up</a> and create API keys, 3) Set them in <a href="options-general.php?page=mollom">settings</a>.
  Author: Matthias Vandermaesen
  Author URI: http://www.colada.be
  License: GPLv2 or later; see LICENSE.md
*/

if (!function_exists('add_action')) {
  header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
  exit;
}

/**
 * Gettext localization domain.
 */
if (!defined('MOLLOM_L10N')) {
  define('MOLLOM_L10N', 'mollom');
}

/**
 * The base URL to this plugin.
 *
 * Use plugins_url() instead of plugin_dir_url() to avoid trailing slash.
 */
if (!defined('MOLLOM_PLUGIN_URL')) {
  define('MOLLOM_PLUGIN_URL', plugins_url('', __FILE__));
}

spl_autoload_register('mollom_classloader');

/**
 * Registers the plugin activation callback.
 *
 * The WP core plugin activation process attempts to verify whether a plugin can
 * be safely enabled in a "sandbox" first. This sandbox does not run in a
 * separate process and thus fails to account for PHP constants (which can be
 * defined only once), so the plugin activation succeeds, but reports a bogus
 * warning about "unexpected output" after doing so.
 *
 * Therefore all constants defined above have to be wrapped into conditions.
 */
register_activation_hook(__FILE__, array('MollomSchema', 'install'));

// Register hook callbacks.
// Note: Unlike code examples in Codex, we do not (ab)use object-oriented
// programming for more than clean organization and automated loading of code,
// unless WP Core learns how to use and adopt OO patterns in a proper way.
// @see http://phptherightway.com
// Note the priority argument semantics (4th argument to add_filter/add_action):
//   WordPress $priority == Drupal $weight != Symfony $priority
// Argument default values: $priority = 10, $accepted_args = 1
add_action('plugins_loaded', 'mollom_plugins_loaded');
add_action('init', 'mollom_moderate');

if (is_admin()) {
  add_action('admin_init', array('MollomAdmin', 'init'));
  add_action('admin_menu', array('MollomAdmin', 'registerPages'));
  add_action('admin_enqueue_scripts', array('MollomAdmin', 'enqueueScripts'));
  add_action('admin_notices', array('MollomAdmin', 'testingModeWarning'));
}

// Comments.
add_filter('comment_form_defaults', 'mollom_dispatch_hook');
add_filter('preprocess_comment', 'mollom_dispatch_hook', 0);
add_action('comment_post', 'mollom_dispatch_hook');
add_action('delete_comment', 'mollom_dispatch_hook');
add_action('wp_set_comment_status', 'mollom_dispatch_hook', 10, 2);
add_action('transition_comment_status', 'mollom_dispatch_hook', 10, 3);
add_filter('mollom_moderate_comment', 'mollom_dispatch_hook', 10, 2);

// Users.
// @todo Multisite uses wp-signup.php.
// @see register_new_user(), wp-login.php
add_action('register_form', 'mollom_dispatch_hook');
add_filter('registration_errors', 'mollom_dispatch_hook', 10, 3);
// @see wp_create_user(), wp_insert_user(), wp-includes/user.php
add_action('user_register', 'mollom_dispatch_hook');
// @see wp_delete_user(), wp-admin/includes/user.php
add_action('delete_user', 'mollom_dispatch_hook');
add_filter('mollom_moderate_user', 'mollom_dispatch_hook', 10, 2);

add_filter('wp_die_handler', 'mollom_die_handler_callback', 100);

/**
 * Dispatches filter/action hooks to class instances.
 *
 * The architecture of this hook dispatcher is based on the following
 * architectural constraints, considerations, and assumptions:
 * - Filter/action hook callbacks need to be registered unconditionally on every
 *   request, but not every request needs to instantiate Mollom entity wrapper
 *   classes.
 * - WP's add_filter() does not support additional custom arguments per
 *   registered callback.
 * - Mollom's entity wrapper class needs to be re-used and re-accessed for
 *   multiple hooks (e.g., form building vs. form validation).
 * - WP form building, validation, and rendering stages are executed from
 *   entirely different code paths (or even front controllers) that do not have
 *   anything in common.
 * - Only one entity of a certain entity type is processed by Mollom in a single
 *   request.
 *
 * Given the constraints of WP's hook architecture, PHP 5.3's LSB would mostly
 * eliminate this, but cannot be used due to WP's minimum compatibility with
 * PHP 5.2.
 */
function mollom_dispatch_hook($has_args = NULL) {
  static $instances = array();
  static $mapping = array(
    // Comments.
    'comment_form_defaults' => array('MollomEntityComment', 'buildForm'),
    'preprocess_comment' => array('MollomEntityComment', 'validateForm'),
    'comment_post' => array('MollomEntityComment', 'save'),
    'delete_comment' => array('MollomEntityComment', 'delete'),
    'wp_set_comment_status' => array('MollomEntityComment', 'sendFeedback'),
    'transition_comment_status' => array('MollomEntityComment', 'transitionStatus'),
    'mollom_moderate_comment' => array('MollomEntityComment', 'moderate'),
    // Users.
    'register_form' => array('MollomEntityUser', 'buildForm'),
    'registration_errors' => array('MollomEntityUser', 'validateRegistrationForm'),
    'user_register' => array('MollomEntityUser', 'save'),
    'delete_user' => array('MollomEntityUser', 'delete'),
    'mollom_moderate_user' => array('MollomEntityUser', 'moderate'),
  );

  $filter = current_filter();
  if (isset($mapping[$filter])) {
    list($class, $method) = $mapping[$filter];
    if (!isset($instances[$class])) {
      $instances[$class] = new $class;
    }
    if (isset($has_args)) {
      $args = func_get_args();
      return call_user_func_array(array($instances[$class], $method), $args);
    }
    else {
      return $instances[$class]->$method();
    }
  }
}

/**
 * Loads Mollom* classes.
 *
 * @see spl_autoload_register()
 */
function mollom_classloader($class) {
  if (strpos($class, 'Mollom') === 0) {
    // Classname as includes/Foo.php (without 'Mollom' prefix).
    include dirname(__FILE__) . '/includes/' . substr($class, 6) . '.php';
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

/**
 * Plugins loaded callback; Initializes localization.
 */
function mollom_plugins_loaded() {
  load_plugin_textdomain(MOLLOM_L10N, FALSE, dirname(plugin_basename(__FILE__)) . '/languages/');
}

/**
 * Init callback; Intercepts a Mollom moderation request.
 */
function mollom_moderate() {
  // Intercept and handle Mollom moderation requests.
  // WP core does not provide a means of registering proper routes, except for
  // its Rewrite API. However, the WP Rewrite subsystem is 1) customizable
  // whereas this endpoint is not, and 2) not generic enough to work for custom
  // routes. Thus, this seems to be the only viable solution.
  // @see http://codex.wordpress.org/Rewrite_API
  // @see http://codex.wordpress.org/Plugin_API/Action_Reference/init
  // @todo Most likely fails on sites that don't have pretty URLs enabled.
  if (preg_match('@/mollom/moderate/(?P<contentId>[^/]+)/(?P<action>[^/]+)$@', $_SERVER['REQUEST_URI'], $args)) {
    echo (int) MollomModeration::handleRequest($args['contentId'], $args['action']);
    //error_log(var_export(MollomModeration::$log, TRUE) . "\n", 3, __DIR__ . '/includes/log.log');
    exit;
  }
}

/**
 * wp_die_handler callback.
 *
 * Overrides the default (or last registered) callback with Mollom's callback.
 * The originally registered callback is statically cached and re-invoked in
 * case Mollom's callback does not apply.
 *
 * @param string $function
 *   The function name of the last registered callback.
 * @param bool $return_last
 *   (optional) Whether to return the last registered callback function name.
 *
 * @return string
 *   The Mollom wp_die_handler function name, or the last registered callback.
 *
 * @see mollom_die_handler()
 */
function mollom_die_handler_callback($function, $return_last = FALSE) {
  static $last_callback;
  if ($return_last) {
    return $last_callback;
  }
  $last_callback = $function;
  return 'mollom_die_handler';
}

/**
 * wp_die callback.
 *
 * Mutes the duplicate comment check error if testing mode is enabled.
 * In all other cases, the previously registered wp_die callback is invoked,
 * which may be the default, or the one of another plugin.
 */
function mollom_die_handler($message, $title, $args) {
  // Disable duplicate comment check when testing mode is enabled, since one
  // typically tests with the literal ham/unsure/spam strings only.
  if (get_option('mollom_testing_mode') && $message === __('Duplicate comment detected; it looks as though you&#8217;ve already said that!')) {
    return;
  }
  $function = mollom_die_handler_callback(NULL, TRUE);
  $function($message, $title, $args);
}
