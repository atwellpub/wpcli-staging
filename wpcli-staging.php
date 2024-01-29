<?php
/**
 * Plugin Name: WP-CLI Staging
 * Description: Pull production db from CloudWays wp instance and overwrites staging isntance.
 * Version: 1.0
 * Author: Hudson Atwell
 * Contributors: GPT4
 * Text Domain: wpcli-local-staging
 */


// Include the class files.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-settings.php';

// Register the WP-CLI command.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpcli-command.php';
	WP_CLI::add_command( 'staging', 'WPCLI_Local_Staging' );
}