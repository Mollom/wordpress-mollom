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
      'template' => 'comment-moderation'
    ),
	);

  $args = func_get_args();
  $hook = array_shift($args);
	$variables = array_shift($args);

  $variables = ($variables) ? $variables : array();

  if (isset($registered_templates[$hook])) {
    $template_file = $registered_templates[$hook]['template'] . '.tpl.php';
    if (file_exists(MOLLOM_BASE_PATH . "/templates/$template_file")) {
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
  extract($variables, EXTR_SKIP); // Extract the variables to a local namespace
  ob_start(); // Start output buffering
  include MOLLOM_BASE_PATH . "/templates/$template_file"; // Include the template file
  $contents = ob_get_contents(); // Get the contents of the buffer
  ob_end_clean(); // End buffering and discard
  print $contents; // Return the contents
}

function mollom_nonce_field($action = -1) {
	return wp_nonce_field($action);
}