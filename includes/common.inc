<?php

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
  // Template register
	$registered_templates = array(
    'configuration' => array(
      'template' => 'configuration',
    ),
    'comment_moderation' => array(
      'template' => 'comment-moderation',
    ),
    'show_captcha' => array(
      'template'=> 'show-captcha',
    ),
  );

  $args = func_get_args();
  $hook = array_shift($args);
  $variables = array_shift($args);

  $variables = ($variables) ? $variables : array();

  if (isset($registered_templates[$hook])) {
    $template_file = $registered_templates[$hook]['template'] . '.tpl.php';
    if (file_exists(MOLLOM_PLUGIN_PATH . "/templates/$template_file")) {
      $output = _mollom_render_template($template_file, $variables);
    }
  }

  return $output;
}

/**
* Render the template and print the output
*
* @param $template_file String the template file which will be rendered
* @param $variables Array an array of variables which will be integrated in the template
*/
function _mollom_render_template($template_file, $variables) {
  // Provide global variables.
  $title = $GLOBALS['title'];

  extract($variables, EXTR_SKIP); // Extract the variables to a local namespace
  ob_start(); // Start output buffering
  include MOLLOM_PLUGIN_PATH . "/templates/$template_file"; // Include the template file
  $contents = ob_get_contents(); // Get the contents of the buffer
  ob_end_clean(); // End buffering and discard
  print $contents; // Return the contents
}

function mollom_nonce_field($action = -1) {
  return wp_nonce_field($action);
}

/**
 * Install Mollom tables
 *
 * Installer function which helps with the installation of tables. Registers
 * the version of the table with get_option in the {prefix}-options table.
 * tableversions are tracked each time a change is made.
 *
 * @param string $table_name Name of the table you want to install
 * @param string $table_version The version of the table. This can be be a string, but it's advised to use a number
 * @param string $sql The definition of the different tablefields which populate the table
 */
function mollom_table_install($table_name, $table_version, $sql) {
  global $wpdb;

  if ( ! empty($wpdb->charset) ) {
    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
  }

  if ( ! empty($wpdb->collate) ) {
    $charset_collate .= " COLLATE $wpdb->collate";
  }

  $wp_table_name = $wpdb->prefix . $table_name;

  if ($wpdb->get_var("SHOW TABLES LIKE '" . $wp_table_name."'") != $wp_table_name) {
    $sql_create_table = "CREATE TABLE " . $wp_table_name . " ( " . $sql . " ) " . $charset_collate . ";";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_create_table);

    //create option for table version
    $option_name = $table_name.'_tbl_version';
    $newvalue = $table_version;
    if ( get_option($option_name) ) {
      update_option($option_name, $newvalue);
    }
    // @todo do we need this?
    /* else {
		  add_option($option_name, $newvalue, '', 'no');
	  }*/

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
 * Helper function
 *
 * Include files, take care of paths
 * @param string $file
 *   The file name of an existing file in the includes/ folder
 */
function mollom_include($file) {
  require_once(MOLLOM_PLUGIN_PATH . '/includes/' . $file);
}
