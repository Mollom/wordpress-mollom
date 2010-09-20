<?php
/**
 * This file contains functions which depend on Wordpress. They are used by WP Mollom
 * but you could use them in your own plugin.
 *
 * @package Mollom
 * @author Matthias Vandermaesen <matthias@colada.be>
 */

/**
 * Severity levels, as defined in RFC 3164 http://www.faqs.org/rfcs/rfc3164.html
 *
 * @see mollom_watchdog()
 * @see watchdog_severity_levels()
 */
define('MOLLOM_WATCHDOG_EMERG',    0); // Emergency: system is unusable
define('MOLLOM_WATCHDOG_ALERT',    1); // Alert: action must be taken immediately
define('MOLLOM_WATCHDOG_CRITICAL', 2); // Critical: critical conditions
define('MOLLOM_WATCHDOG_ERROR',    3); // Error: error conditions
define('MOLLOM_WATCHDOG_WARNING',  4); // Warning: warning conditions
define('MOLLOM_WATCHDOG_NOTICE',   5); // Notice: normal but significant condition
define('MOLLOM_WATCHDOG_INFO',     6); // Informational: informational messages
define('MOLLOM_WATCHDOG_DEBUG',    7); // Debug: debug-level messages

/**
 * Mollom theming funcion
 *
 * Wordpress doesn't have a clean way to seperate business logic from presentation.
 * This function is a primitive based on Drupal's theming functionality. It will take on
 * a random number of arguments. The first being a registered template, the following
 * being data which needs to be inserted into the template.
 * Registered templates are hard coded in the $registered_templates array. Drupal stores
 * those dynamically in the theme registry. But we won't be needing that kind of flexibility
 * here.
 *
 * @param $args mixed A mixed array of variables
 */
function mollom_theme() {
  $registered_templates = mollom_registered_templates();
  $args = func_get_args();
  $hook = array_shift($args);

  // @todo add functionality for theming functions, not only templates

  if (isset($registered_templates[$hook])) {
    $template_file = $registered_templates[$hook]['template'] . '.tpl.php';

    if (file_exists(MOLLOM_BASE_PATH . "/templates/$template_file")) {
	   	$variables = mollom_preprocess();
	
	    $preprocess_function = 'mollom_preprocess_' . $registered_templates[$hook]['template'];

      if (!empty($registered_templates[$hook]['args'])) {
	      foreach ($registered_templates[$hook]['args'] as $key => $value) {
		      $arg = array_shift($args);
	        $variables[$key] = (!is_null($arg)) ? $arg : $value;
	      }
	    }

	    if (function_exists($preprocess_function)) {
	      $preprocess_function($variables);
      }

	    $output = _mollom_render_template($template_file, $variables);
	    return $output;
    }
  }
}

/**
 * Render the template and print the output
 *
 * @param $template_file String the template file which will be rendered
 * @param $variables Array an array of variables which will be integrated in the template
 */
function _mollom_render_template($template_file, $variables) {
  extract($variables, EXTR_SKIP);  // Extract the variables to a local namespace
  ob_start();                    // Start output buffering
  include MOLLOM_BASE_PATH . "/templates/$template_file";      // Include the template file
  $contents = ob_get_contents();   // Get the contents of the buffer
  ob_end_clean();                  // End buffering and discard
  print $contents;                // Return the contents
}

function mollom_load_page($active = NULL) {
	$pages = WP_Mollom::register_administration_tabs();	

  if (isset($pages[$active])) {
    require_once(ABSPATH . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/classes/' . $pages[$active]['file']);

    $page = new $pages[$active]['class'];

    // Wordpress does a wp_redirect in options.php when processing variables set through register_setting()
    // we lose our $_POST and the only indication that something happened is through $_GET['updated]
	  if ((!empty($_POST)) || ($_GET['updated'] === "true")) {
			$form_state = $page->process($_POST);
		}
		
		$page->display($form_state);
  }
}

function mollom_render_tabs($plugin = 'core_configuration') {
  $output = '';

  $_tabs = WP_Mollom::register_administration_tabs();
  foreach ($_tabs as $key => $tab) {
	  extract($tab);
	  $link = esc_url( "options-general.php?page=mollom-key-config&tab=$key" );
	  $tabs[$key] = "<a href='$link'>$name</a>";
  }

  return $tabs;
}

/**
 * Set a status message which will be displayed on the next page load
 * @param $message string A message which is going to be added to the session
 * @param $type string the type of message you want to display
 */
function mollom_set_message($message, $type = 'notice') {
  $_SESSION['mollom_messages'][$type][] = $message;
}

/**
 * Show all messages on page load and clear all current messages afterwards
 * @return string rendered unordered lists of messages with a class $type
 */
function mollom_display_messages() {
  $output = '';
  if (isset($_SESSION['mollom_messages'])) {
	  $keys = array_keys($_SESSION['mollom_messages']);
	  foreach ($keys as $key) {
		$output .= '<ul class="mollom-' . $key . '">';
	    foreach ($_SESSION['mollom_messages'][$key] as $message) {
	      $output .= '<li>' . $message . '</li>';
	    }
	    $output .= '</ul>';
	  }

	  unset($_SESSION['mollom_messages']);
  }
  return $output;
}

/**
 * Installer function which helps with the installation of tables. Registers
 * the version of the table with get_option in the {prefix}-options table. 
 * tableversions are tracked each time a change is made.
 *
 * @param string $table_name Name of the table you want to install
 * @param string $table_version The version of the table. This can be be a string, but it's advised to use a number
 * @param string $sql The definition of the different tablefields which populate the table
 */
function mollom_run_install($table_name, $table_version, $sql) {
  global $wpdb;

	if ( ! empty($wpdb->charset) ) {
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	}
	
	if ( ! empty($wpdb->collate) ) {
		$charset_collate .= " COLLATE $wpdb->collate";
	}

	$wp_table_name = $wpdb->prefix . $table_name;

  if($wpdb->get_var("SHOW TABLES LIKE '" . $wp_table_name."'") != $wp_table_name) {
    $sql_create_table = "CREATE TABLE " . $wp_table_name . " ( " . $sql . " ) " . $charset_collate . ";";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_create_table);
 
    //create option for table version
		$option_name = $table_name.'_tbl_version';
		$newvalue = $table_version;
		if ( get_option($option_name) ) {
		  update_option($option_name, $newvalue);
    } else {
		  add_option($option_name, $newvalue, '', 'no');
	  }
	
    //create option for table name
    $option_name = $table_name.'_tbl';
    $newvalue = $wp_table_name;

    if ( get_option($option_name) ) {
      update_option($option_name, $newvalue);
    } else {
      add_option($option_name, $newvalue, '', 'no');
	  }
  }
 
  // Code here with new database upgrade info/table Must change version number to work.
  $installed_ver = get_option( $table_name.'_tbl_version' );
	if( $installed_ver != $table_version ) {
	  $sql_create_table = "CREATE TABLE " . $wp_table_name . " ( " . $sql . " ) " . $charset_collate . ";";
	  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	  dbDelta($sql_create_table);
    update_option( $table_name.'_tbl_version', $table_version );
  }
}

/**
 * Returns an array with all severity levels machine/human readable converted
 */
function mollom_severity_levels() {
  return array(
    MOLLOM_WATCHDOG_EMERG    => __('emergency', MOLLOM_I18N),
    MOLLOM_WATCHDOG_ALERT    => __('alert', MOLLOM_I18N),
    MOLLOM_WATCHDOG_CRITICAL => __('critical', MOLLOM_I18N),
    MOLLOM_WATCHDOG_ERROR    => __('error', MOLLOM_I18N),
    MOLLOM_WATCHDOG_WARNING  => __('warning', MOLLOM_I18N),
    MOLLOM_WATCHDOG_NOTICE   => __('notice', MOLLOM_I18N),
    MOLLOM_WATCHDOG_INFO     => __('info', MOLLOM_I18N),
    MOLLOM_WATCHDOG_DEBUG    => __('debug', MOLLOM_I18N),
  );
}

/**
 * Records messages into WP Mollom core's watchdog table. Useful for debugging and tracking
 * Mollom's behaviour.
 *
 * @param $type String the type of the message. Usually the subsystem which wants to log a message.
 * @param $message String the message itself
 * @param $severity String the severity of the message. Refer to RFC 3164 http://www.faqs.org/rfcs/rfc3164.html.
 */
function mollom_watchdog($type = 'none', $message = '', $severity = 'notice', $comment_id = NULL) {
  global $wpdb;

	$mollom_table = $wpdb->prefix . MOLLOM_WATCHDOG;	
	$data = array(
		'type' => $type,
		'message' => $message,
		'severity' => $severity,
		'comment_ID' => $comment_id,
		'created' => date('Y-m-d H:i:s'),
	);
	
  $wpdb->insert($mollom_table, $data);
}

function purge_mollom_watchdog() {
	global $wpdb;

  // delete watchdog messages which are older then 1 week. No exceptions.
  $wpdb->query('DELETE FROM wp_mollom_watchdog WHERE created < (now() - 604800)');
}