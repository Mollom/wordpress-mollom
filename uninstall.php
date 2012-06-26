<?php
/*
Uninstall logic when WP-Mollom is deleted  through the admin panel(>= WP 2.7)
*/

// do not run unless within the WP plugin flow
if( (!defined("ABSPATH")) && (!defined("WP_UNINSTALL_PLUGIN")) ) {
	define( 'MOLLOM_I18N', 'wp-mollom' );
	wp_die(__('The uninstall is not being executed from plugins.php. Halting.', MOLLOM_I18N));
}

// define/init variables we'll need
global $wpdb, $wp_db_version;

// < WP 2.7 don't have their own uninstallation file
if ( 8645 > $wp_db_version ) {
	return;
}

define( 'MOLLOM_TABLE', 'mollom' );

// delete all mollom related options
delete_option('mollom_developer_mode');
delete_option('mollom_reverseproxy_addresses');
delete_option('mollom_fallback_mode');
delete_option('mollom_roles');
delete_option('mollom_analysis_types');
delete_option('mollom_protection_mode');
delete_option('mollom_private_key');
delete_option('mollom_public_key');
delete_option('mollom_servers');

// delete MOLLOM_TABLE
$mollom_table = $wpdb->prefix . MOLLOM_TABLE;
$wpdb->query('DROP TABLE IF EXISTS ' . $mollom_table);
