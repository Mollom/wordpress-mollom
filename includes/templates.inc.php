<?php
/**
 * This file contains functions which depend on Wordpress. They are used by WP Mollom
 * but you could use them in your own plugin.
 *
 * @package Mollom
 * @author Matthias Vandermaesen <matthias@colada.be>
 */

/**
 * Registers all templates with Mollom's templating system
 *
 * Currently hard coded because an extra table is just overhead for what we want to achieve.
 */
function mollom_registered_templates() {
	return array(
  	'core_configuration_page' =>  array(
	    'template' => 'core_admin_configuration_page',
	  ),
	  'core_watchdog_page' => array(
		  'args' => array('page_links' => NULL, 'watchdog_messages' => NULL),
		  'template' => 'core_admin_watchdog_page',
	  ),
  	'client_configuration_page' =>  array(
      'template' => 'client_admin_configuration_page',
    ),
  );
}

/**
 * preprocess base line variables
 *
 * preprocess base line variables and make them available to other preprocessors.
 *
 * @return array
 */
function mollom_preprocess() {
	$variables = array();
	$variables['mollom_template_path'] = MOLLOM_PLUGIN_PATH . '/templates';
	return $variables;
}

/**
 * preprocess admin_configuration_page.tpl.php
 * 
 * preprocess a template. Populate variables.
 *
 * @param variables An array of variables which will be used within the template
 */
function mollom_preprocess_core_admin_configuration_page(&$variables) {
	global $wp_roles;
	$_mollom_statistics = get_option('mollom_statistics');
	$_mollom_site_policy = get_option('mollom_site_policy');
	$_mollom_reverse_proxy = get_option('mollom_reverse_proxy');
	$_mollom_roles = get_option('mollom_roles');
  $mollom_roles = array();

	foreach ($wp_roles->roles as $role => $data) {
    $mollom_roles[] =	'<li><input type="checkbox" name="mollom_roles[' . $role . ']" value="' . $role . '"' . checked($role, $_mollom_roles[$role], FALSE) . '/> ' . $role . '</li>';
	}

	$statistic_types = array('total_days',
							 'total_accepted',
							 'total_rejected',
							 'yesterday_accepted',
							 'yesterday_rejected',
							 'today_accepted',
							 'today_rejected',
  );

  foreach ($statistic_types as $type) {
	  $variables['mollom_' . $type] = number_format_i18n($_mollom_statistics['mollom_' . $type]);
 // 	$variables['mollom_' . $type] = $_mollom_statistics['mollom_' . $type];
  }

  $variables['tabs']                           = mollom_render_tabs('core_configuration');
  $variables['mollom_roles']                   = $mollom_roles;
  $variables['mollom_public_key']              = get_option('mollom_public_key');
  $variables['mollom_private_key']             = get_option('mollom_private_key');
  $variables['mollom_site_policy']             = checked('on', $_mollom_site_policy, FALSE);
  $variables['mollom_reverseproxy']            = checked('on', $_mollom_reverse_proxy, FALSE);
  $variables['mollom_reverse_proxy_addresses'] = get_option('mollom_reverse_proxy_addresses');
  $variables['messages']                       = mollom_display_messages();
}

/**
 * preprocess core_admin_watchdog_page.tpl.php
 *
 * preprocess a templates. Populate with variables.
 *
 * @param variables An array of variables which will be used within the template
 */
function mollom_preprocess_core_admin_watchdog_page(&$variables) {
	$variables['tabs']                           = mollom_render_tabs('core_configuration');
	$variables['severity_levels']                = mollom_severity_levels();
  $variables['messages']                       = mollom_display_messages();
}

/**
 * preprocess client_configuration_page.tpl.php
 * 
 * preprocess a template. Populate variables.
 *
 * @param variables An array of variables which will be used within the template
 */
function mollom_preprocess_client_admin_configuration_page(&$variables) {
	global $wp_roles;
	$_mollom_roles = get_option('mollom_roles');
  $mollom_roles = array();
	
	foreach ($wp_roles->roles as $role => $data) {
    $mollom_roles[] =	'<li><input type="checkbox" name="mollom_roles[' . $role . ']" value="' . $role . '"' . checked($role, $_mollom_roles[$role], FALSE) . '/> ' . $role . '</li>';
	}

  $variables['tabs']                           = mollom_render_tabs('mollom_client');
  $variables['mollom_roles']                   = $mollom_roles;
  $variables['messages']                       = mollom_display_messages();
}
