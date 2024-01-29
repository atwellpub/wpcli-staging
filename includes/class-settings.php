<?php
/**
 * Settings Class
 */
class WPCLI_Local_Staging_Settings {

	public static $prefix = 'wpcli_staging-';

	/**
	 * Initialize the settings.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Add settings page.
	 */
	public static function add_settings_page() {
		add_options_page(
			'WP-CLI Staging',
			'WP-CLI Staging',
			'manage_options',
			'wpcli-staging-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting('wpcli-staging-settings-group', self::$prefix.'remote_domain', 'sanitize_text_field');
		register_setting('wpcli-staging-settings-group', self::$prefix.'cloudways_ssh_ip', 'sanitize_text_field');
		register_setting('wpcli-staging-settings-group', self::$prefix.'cloudways_ssh_port', 'absint');
		register_setting('wpcli-staging-settings-group', self::$prefix.'cloudways_ssh_username', 'sanitize_user');
		register_setting('wpcli-staging-settings-group', self::$prefix.'cloudways_wordpress_path', 'sanitize_text_field'); // New setting for application ID
		register_setting('wpcli-staging-settings-group', self::$prefix.'cloudways_private_key_path', ['WPCLI_Local_Staging_Settings', 'sanitize_private_key_file'] );
		register_setting('wpcli-staging-settings-group', self::$prefix.'flywheel_mysql_port', 'absint');
		register_setting('wpcli-staging-settings-group', self::$prefix.'rollback_point', 'sanitize_text_field');
		register_setting('wpcli-staging-settings-group', self::$prefix.'rollfoward_point', 'sanitize_text_field');
	}


	/**
	 * Render settings page.
	 */
	/**
	 * Render settings page.
	 */
	/**
	 * Render settings page.
	 */
	public static function render_settings_page() {
		// Get current settings
		$settings = self::get_settings();
		?>
        <div class="wrap">
            <h2><?php _e('WP-CLI Local Setup', 'wpcli-local-staging'); ?></h2>
            <form method="post" action="options.php">
				<?php settings_fields('wpcli-staging-settings-group'); ?>
				<?php do_settings_sections('wpcli-staging-settings-group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Production URL', 'wpcli-local-staging'); ?></th>
                        <td>
                            <input type="text" name="<?php echo self::$prefix; ?>remote_domain" value="<?php echo esc_attr($settings['remote_domain']); ?>" />
                            <p class="description"><?php _e('The base URL of your remote host. eg: https://www.example.com/. We will replace all occurrences of this URL in the imported database with your local url.', 'wpcli-local-staging'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Local URL', 'wpcli-local-staging'); ?></th>
                        <td>
                            <input type="text" name="<?php echo self::$prefix; ?>local_domain" value="<?php echo esc_attr($settings['local_domain']); ?>" disabled/>
                            <p class="description"><?php _e('This URL will replace all occurrences of the above URL during import.', 'wpcli-local-staging'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Cloudways SSH IP', 'wpcli-local-staging'); ?></th>
                        <td>
                            <input type="text" name="<?php echo self::$prefix; ?>cloudways_ssh_ip" value="<?php echo esc_attr($settings['cloudways_ssh_ip']); ?>" />
                            <p class="description"><?php _e('The IP address of your Cloudways server. You can find this in your Cloudways dashboard under the "Access Details" section.', 'wpcli-local-staging'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Cloudways SSH Port', 'wpcli-local-staging'); ?></th>
                        <td>
                            <input type="text" name="<?php echo self::$prefix; ?>cloudways_ssh_port" value="<?php echo esc_attr($settings['cloudways_ssh_port']); ?>" />
                            <p class="description"><?php _e('The SSH port for your Cloudways server, typically 22. You can find this information in your Cloudways dashboard under the "Access Details" section.', 'wpcli-local-staging'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Cloudways SSH Username', 'wpcli-local-staging'); ?></th>
                        <td>
                            <input type="text" name="<?php echo self::$prefix; ?>cloudways_ssh_username" value="<?php echo esc_attr($settings['cloudways_ssh_username']); ?>" />
                            <p class="description"><?php _e('Your Cloudways SSH username. This username can be found in your server dashboard in the "Master Credentials" section.', 'wpcli-local-staging'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Cloudways Full WP Path', 'wpcli-local-staging'); ?></th>
                        <td>
                            <input type="text" name="<?php echo self::$prefix; ?>cloudways_wordpress_path" value="<?php echo esc_attr($settings['cloudways_wordpress_path']); ?>" />
                            <p class="description"><?php _e('The full file system path to your remote WordPress instance. For example: /home/master/applications/YOUR_APPLICATION/public_html/', 'wpcli-local-staging'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('SSH Private Key Path', 'wpcli-local-staging'); ?></th>
                        <td>
                            <input type="text" name="<?php echo self::$prefix; ?>cloudways_private_key_path" value="<?php echo esc_attr($settings['cloudways_private_key_path']); ?>" />
                            <p class="description"><?php _e('The file system path to your SSH private key. For permissions reasons, this needs to be inside your site directory in Local.', 'wpcli-local-staging'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('MySQL Port for Flywheel', 'wpcli-local-staging'); ?></th>
                        <td>
                            <input type="text" name="<?php echo self::$prefix; ?>flywheel_mysql_port" value="<?php echo esc_attr($settings['flywheel_mysql_port']); ?>" />
                            <p class="description"><?php _e('The MySQL port used by Flywheel. The default is 10023. You can find this by navigating to the Database tab inside Local.', 'wpcli-local-staging'); ?></p>
                        </td>
                    </tr>
                </table>
				<?php submit_button(__('Save Changes', 'wpcli-local-staging')); ?>
            </form>
        </div>
		<?php
	}

	/**
	 * Sanitize the SSH private key file upload.
	 */
	public static function sanitize_private_key_file($input) {
        return sanitize_text_field($input);
	}

	/**
     * A routine to save legacy wpcli-staging settings after a fresh wp db import.
	 * @param $settings
	 *
	 * @return void
	 */
	public static function resave_settings($settings) {

		foreach ($settings as $key => $value) {

			if (strpos($key, self::$prefix) !== 0) {
				$key = self::$prefix . $key;
			}

			exec('wp option update ' . escapeshellarg($key) . ' ' . escapeshellarg($value));
			WP_CLI::log('wp option update ' . escapeshellarg($key) . ' ' . escapeshellarg($value));
		}
	}

	/**
	 * @return array
	 */
	public static function get_settings() {
	    $settings = [
		    'local_domain' => get_option('home'),
		    'remote_domain' => get_option(self::$prefix.'remote_domain'),
		    'cloudways_ssh_ip' => get_option(self::$prefix.'cloudways_ssh_ip'),
		    'cloudways_ssh_port' => get_option(self::$prefix.'cloudways_ssh_port' , 22),
		    'cloudways_ssh_username' => get_option(self::$prefix.'cloudways_ssh_username'),
		    'cloudways_wordpress_path' => get_option(self::$prefix.'cloudways_wordpress_path'),
		    'cloudways_private_key_path' => get_option(self::$prefix.'cloudways_private_key_path'),
		    'flywheel_mysql_port' => get_option(self::$prefix.'flywheel_mysql_port' , 10023),
		    'rollback_point' => get_option(self::$prefix.'rollback_point' ),
            'rollforward_point' => get_option(self::$prefix.'rollforward_point' )
	    ];

		$settings['local_domain'] = str_replace(['https://','http://'] , '' , $settings['local_domain']);
		$settings['remote_domain'] = str_replace(['https://','http://'] , '' , $settings['remote_domain']);


		return $settings;
    }


}

WPCLI_Local_Staging_Settings::init();
