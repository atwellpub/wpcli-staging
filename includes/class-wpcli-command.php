<?php

if (!defined('IMPORT_DEBUG'))
	define( 'IMPORT_DEBUG', false );


/**
 * WP CLI Commands that help manage Local as a staging environment for a production site
 */
class WPCLI_Local_Staging extends WP_CLI_Command {

	/**
	 * Display help information for all subcommands.
	 *
	 * ## EXAMPLES
	 *
	 *     wp staging help
	 *
	 * @when after_wp_load
	 */
	public function help() {
		WP_CLI::log("WPCLI Local Staging Commands:");

		WP_CLI::log("backup:");
		WP_CLI::log("  Create a backup of the current local database.");
		WP_CLI::log("  Usage: wp staging backup");

		WP_CLI::log("rollback:");
		WP_CLI::log("  Rollback the database to the last known backup.");
		WP_CLI::log("  Usage: wp staging rollback");

		WP_CLI::log("rollforward:");
		WP_CLI::log("  Rollforward the database to the next newer backup after a rollback.");
		WP_CLI::log("  Usage: wp staging rollforward");

		WP_CLI::log("import:");
		WP_CLI::log("  Import the the database from remote server.");
		WP_CLI::log("  Usage: wp staging import");

		WP_CLI::log("restore:");
		WP_CLI::log("  Restore the local database from a backup file inside the backups folder. Options will be given for selection.");
		WP_CLI::log("  Usage: wp staging restore ");

	}


	/**
	 * get directories and create if not exists
	 * @return string[]
	 */
	private function get_directories() {
		$directories = [
			'import' => WP_CONTENT_DIR . '/wpcli-staging/imports/',
			'backup' => WP_CONTENT_DIR . '/wpcli-staging/backups/',
		];

		foreach ($directories as $type => $directory) {
			if (!is_dir($directory)) {
				mkdir($directory, 0755, true);
				WP_CLI::log(sprintf('Created %s directory: %s', $type, $directory));
			}
		}

		return $directories;
	}

	/**
	 * Create a backup of the current local database.
	 * This method is reusable by both the backup and import subcommands.
	 */
	private function create_local_backup( $is_manual = false , $settings = false ) {
		$timestamp = date('Y-m-d-H-i');
		$directories = $this->get_directories();
		$file = ($is_manual) ? "manual-backup-$timestamp.sql" : "automatic-backup-$timestamp.sql";

		$settings = ($settings) ? $settings : WPCLI_Local_Staging_Settings::get_settings();
		$backup_command = "wp db export " . escapeshellarg($directories['backup'] . $file);
        WP_CLI::log($backup_command);
		exec($backup_command, $output, $return_var);

		if ($return_var === 0) {
			WP_CLI::success('Local database backed up successfully.');
			return $directories['backup'] . $file; // Return the path of the backup file
		} else {
			WP_CLI::error('Failed to backup local database.');
			return false; // Return false if backup failed
		}
	}

	/**
	 * Perform a database import from a specified file.
	 *
	 * @param string $file Path to the SQL file to be imported.
	 */
	private function perform_db_import($file, $url) {
		// Use debug_backtrace to find out the calling method
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2); // Get the last two stack frames
		$callingMethod = $backtrace[1]['function']; // The calling method's name

		// Determine the URL to use based on the calling method
		$url = rtrim( $url , '/');

		$import_command = 'wp db import "' . $file . '" --url=' . $url;
		WP_CLI::log($import_command);
		exec($import_command, $output, $return_var);

		if ($return_var === 0) {
			WP_CLI::success("Database successfully imported from $file.");
		} else {
			WP_CLI::error("Failed to import database from $file.");
		}
	}

	/**
	 * Subcommand to create a backup of the local database.
	 *
	 * ## EXAMPLES
	 *
	 *     wp staging backup
	 *
	 * @when after_wp_load
	 */
	public function backup() {
		$backup_file = $this->create_local_backup(true );

		if (!$backup_file) {
			WP_CLI::error("Ther was an error creating the local backup");
		}

		$directories = $this->get_directories();
		$backups = glob($directories['backup'] . '*.sql');
		usort($backups, function ($a, $b) {
			return filemtime($b) - filemtime($a); // Sort files by modification time (newest first)
		});

		// Find the index of the newly created backup file
		$current_index = array_search($backup_file, $backups);
		$rollback_index = $current_index + 1;
		$rollback_file = (isset($backups[$rollback_index])) ? $backups[$rollback_index] : false;


		if ($rollback_file) {
			exec('wp option update wpcli_staging-rollback_point ' . escapeshellarg($rollback_file));
			WP_CLI::log("Detected and assigned a rollback point: " . $rollback_file);
		}

	}

	/**
	 * Subcommand to rollback the database to the last known backup.
	 *
	 * @when after_wp_load
	 */
	public function rollback() {
		$settings = WPCLI_Local_Staging_Settings::get_settings();

		if (!$settings['rollback_point']) {
			WP_CLI::error("No rollback point set. Please run 'wp staging backup', 'wp staging rollforward', or 'wp staging import' before attempting a rollback.");
		}

		WP_CLI::success("Rollback point found: " . $settings['rollback_point']);

		$directories = $this->get_directories();
		$backups = glob($directories['backup'] . '*.sql');
		usort($backups, function ($a, $b) {
			return filemtime($b) - filemtime($a);
		});

		WP_CLI::log(print_r($backups,true));
		$current_index = array_search($settings['rollforward_point'], $backups);
		$rollback_index = $current_index + 1;
		$rollback_file = (isset($backups[$rollback_index])) ? $backups[$rollback_index] : false;
		$rollforward_index = $current_index - 1;
		$rollforward_file = (isset($backups[$rollforward_index])) ? $backups[$rollforward_index] : false;
		WP_CLI::log('rollback:'  . $rollback_file);
		WP_CLI::log('rollforward:'  . $rollforward_file);

		WP_CLI::confirm("Are you sure you want to rollback to this backup?");

		$this->perform_db_import($settings['rollback_point'], $settings['local_domain']);
		WP_CLI::success("Database rolled backward successfully.");

		if (!$rollback_file) {
			exec('wp option delete wpcli_staging-rollback_point');
		} else {
			exec( 'wp option update wpcli_staging-rollback_point ' . escapeshellarg( $rollback_file ) ); // Set new rollback point
			WP_CLI::success("New rollback point set: " . $rollback_file );
		}

		if ($rollforward_file) {
			exec( 'wp option update wpcli_staging-rollforward_point ' . escapeshellarg( $rollforward_file ) ); // Set new rollback point
			WP_CLI::success("Rollforward point set:" . $rollforward_file);
		}

	}

	/**
	 * @return void
	 */
	public function rollforward() {

		$settings = WPCLI_Local_Staging_Settings::get_settings();

		if (!$settings['rollforward_point']) {
			WP_CLI::error("No known rollforward file found. Please run a 'wp staging rollback' before attempting a rollforward.");
			return;
		}

		WP_CLI::success("Rollforward point found: " . $settings['rollforward_point']);

		$directories = $this->get_directories();
		$backups = glob($directories['backup'] . '*.sql');
		usort($backups, function ($a, $b) {
			return filemtime($b) - filemtime($a);
		});

		$current_index = array_search($settings['rollforward_point'], $backups);
		$rollback_index = $current_index + 1;
		$rollback_file = (isset($backups[$rollback_index])) ? $backups[$rollback_index] : false;
		$rollforward_index = $current_index - 1;
		$rollforward_file = (isset($backups[$rollforward_index])) ? $backups[$rollforward_index] : false;

		$last_rollfoward = false;
		if (!$rollforward_file) {
			WP_CLI::log("No new rollforward file found. This is the last possible rollfoward.");
			$last_rollfoward = true;
		}


		WP_CLI::confirm("Are you sure you want to roll forward to this backup?");

		$this->perform_db_import($settings['rollforward_point'], $settings['local_domain']);

		WP_CLI::success("Database rolled forward successfully.");

		if ($last_rollfoward) {
			exec('wp option delete wpcli_staging-rollforward_point');
		} else {
			exec('wp option update wpcli_staging-rollforward_point ' . escapeshellarg($rollforward_file));
			WP_CLI::success("New rollforward point set: " . $rollforward_file);
		}


		if (!$rollback_file) {
			WP_CLI::success("Rollback point stays the same.");
		} else {
			exec('wp option update wpcli_staging-rollback_point ' . escapeshellarg($rollback_file));
			// Set the next rollback point to the backup just rolled forward from
			WP_CLI::success("New rollback point set: " . $rollback_file);
		}

	}


	/**
	 * Subcommand to import the database from Cloudways.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     wp staging import               // Import with a timestamp-based name
	 *     wp staging import my_database.sql // Import with a specific name
	 *
	 * @when after_wp_load
	 */
	public function import() {

        $directories = $this->get_directories();
        $timestamp = date('Y-m-d-H-i-s');
        $randomPart = substr(md5(rand()), 0, 7); // Generate a random 7-character string
        $fileName = "backup_{$timestamp}_{$randomPart}";
        $sqlFileName = "{$fileName}.sql";


        $settings = WPCLI_Local_Staging_Settings::get_settings();

		// Inform the user that the remote export is starting
		WP_CLI::log('Initiating remote database export...');

		// Remote ssh and generate backup using settings from the $settings array
		$ssh_command = sprintf(
			'ssh -i %s %s@%s -p %d "cd %s && wp db export '.$fileName.'"',
			escapeshellarg($settings['cloudways_private_key_path']),
			escapeshellarg($settings['cloudways_ssh_username']),
			escapeshellarg($settings['cloudways_ssh_ip']),
			intval($settings['cloudways_ssh_port']),
			escapeshellarg($settings['cloudways_wordpress_path']),
            escapeshellarg($sqlFileName),
            escapeshellarg($sqlFileName),
            escapeshellarg($fileName)
		);

		exec($ssh_command, $output, $return_var);

		// Check if SSH command was successful and handle the result accordingly
		if ($return_var === 0) {
            WP_CLI::log('Production database backup generated on the remote server.');
		} else {
			WP_CLI::error('Failed to generate remote database backup. Please check your SSH settings and remote server configuration.');
			return; // Exit the function if SSH command failed
		}

        // Use curl to download the backup file
        $localFilePath = escapeshellarg($directories['import'] . 'production-backup.sql');
        $remoteFileUrl = escapeshellarg("https://".rtrim($settings['remote_domain'],'/') . '/'. $fileName); // Adjust according to your domain/IP and secure access method

        $curl_command = "curl -o $localFilePath --url $remoteFileUrl";

        WP_CLI::log("Beginning download of remote backup file: $curl_command");

        exec($curl_command, $curl_output, $return_var);

        if ($return_var === 0) {
            WP_CLI::log('Production database successfully downloaded locally.');

            // Delete the remote backup file after successful download
            $delete_command = sprintf(
                'ssh -i %s %s@%s -p %d "rm -f %s"',
                escapeshellarg($settings['cloudways_private_key_path']),
                escapeshellarg($settings['cloudways_ssh_username']),
                escapeshellarg($settings['cloudways_ssh_ip']),
                intval($settings['cloudways_ssh_port']),
                escapeshellarg($fileName)
            );
            exec($delete_command);
            WP_CLI::log('Remote backup file deleted.');

        } else {
            WP_CLI::error('Failed to download the backup.');
            return;
        }

		// Create a backup before importing
		$settings['rollback_point'] = $this->create_local_backup(false, $settings);
		if (!$settings['rollback_point']) {
			return; // Stop execution if backup fails
		}

		WP_CLI::log("Setting backup as a rollback point: " . $settings['backup_file']);

		// Use the perform_db_import method to import the database
		$path_to_import_file = $directories['import'].'production-backup.sql';
		$this->perform_db_import($path_to_import_file , $settings['remote_domain']);

		// Search and replace URLs using settings from the $settings array
		$search_replace_command = 'wp search-replace "'. $settings['remote_domain'].'" "'.$settings['local_domain'].'" --all-tables';
		WP_CLI::debug($search_replace_command);
		exec($search_replace_command);
		WP_CLI::success("Domains replaced.");

		// Automatically activate the wpcli-staging plugin
		$plugin_activation_command = "wp plugin activate wpcli-staging";
		WP_CLI::debug($plugin_activation_command);
		exec($plugin_activation_command);
		WP_CLI::success("WP-CLI Staging plugin activated.");

		// Resave the plugin settings]
		WPCLI_Local_Staging_Settings::resave_settings($settings);
		WP_CLI::success("Plugin settings resaved successfully.");

		WP_CLI::success("Import process complete!");
	}


	/**
	 * Subcommand to restore the local database from a backup.
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     wp staging restore               // List available backups and prompt for selection
	 *
	 * @when after_wp_load
	 */
	public function restore( $args, $assoc_args ) {
		list( $requested_backup ) = $args;

		// Define the backup directory
		$directories = $this->get_directories();

		// List available backups in the backup directory and filter for .sql files
		$backups = glob($directories['backup'] . '*.sql');


		if ( empty( $backups ) ) {
			WP_CLI::error( 'No .sql backups found in the backup directory.' );
			return;
		}

		// First sort by oldest to newest
		usort($backups, function ($a, $b) {
			return filemtime($a) - filemtime($b); // Sort files by modification time (oldest first for rollforward)
		});

		// List backups with a numeric index
		WP_CLI::log( 'Available backups:' );
		$index = 1;
		foreach ( $backups as $backup ) {
			WP_CLI::log( $index++ . '. ' . str_replace( $directories['backup'] , '',  $backup ) );
		}

		// Prompt the user to select a backup by number
		WP_CLI::log( 'Type the number of the backup you want to restore:' );
		$selection = trim( fgets( STDIN ) ); // Read user input from the command line

		// Validate the selection and retrieve the backup name
		if ( is_numeric( $selection ) && $selection > 0 && $selection < $index ) {
			$backup_to_restore = array_values( $backups )[ $selection - 1 ];
		} else {
			WP_CLI::error( 'Invalid selection. Please run the command again and select a valid number.' );
			return;
		}

		WP_CLI::log("You've selected: " . $backup_to_restore );
		WP_CLI::confirm("Do you want to restore this backup?");

		WP_CLI::log('Importing backup. This may take a moment.');

		// Restore the selected backup using WP-CLI command
		$command = 'wp db import "'.$backup_to_restore .'"';
		exec($command);

		WP_CLI::success( "Local database restored successfully from $backup_to_restore." );

		// Then sort by newest to oldest
		usort($backups, function ($a, $b) {
			return filemtime($b) - filemtime($a); // Sort files by modification time (oldest first for rollforward)
		});

		$current_index = array_search( $backup_to_restore , $backups);
		$rollback_index = $current_index + 1;
		$rollback_file = (isset($backups[$rollback_index])) ? $backups[$rollback_index] : false;

		if ($rollback_file) {
			exec('wp option update wpcli_staging-rollback_point ' . escapeshellarg($rollback_file));
			WP_CLI::log("Found rollback point: " . $rollback_file);
		} else {
			exec('wp option delete wpcli_staging-rollback_point');
		}

		$rollforward_index = $current_index - 1;
		$rollforward_file = (isset($backups[$rollforward_index])) ? $backups[$rollforward_index] : false;

		if ($rollforward_file) {
			exec( 'wp option update wpcli_staging-rollforward_point ' . escapeshellarg( $rollforward_file ) ); // Set new rollback point
			WP_CLI::log("Found rollfoward point: " . $rollforward_file );
		}else {
			exec('wp option delete wpcli_staging-rollforward_point');
		}
		
		// Automatically activate the wpcli-staging plugin
		$plugin_activation_command = "wp plugin activate wpcli-staging";
		# WP_CLI::runcommand($import_command); // can't get this to work inside flywheel terminal
		exec($plugin_activation_command);
		WP_CLI::success("WP-CLI Staging plugin activated.");
	}

}
