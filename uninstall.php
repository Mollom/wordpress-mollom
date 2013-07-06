<?php

/**
 * @file
 * Uninstallation functionality.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
  header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
  exit;
}

require_once dirname(__FILE__) . '/includes/Schema.php';
MollomSchema::uninstall();

// @todo Delete meta data.

delete_option('mollom_public_key');
delete_option('mollom_private_key');

delete_option('mollom_checks');
delete_option('mollom_bypass_roles');
delete_option('mollom_fallback_mode');
delete_option('mollom_privacy_link');

delete_option('mollom_reverse_proxy_addresses');
delete_option('mollom_testing_mode');
