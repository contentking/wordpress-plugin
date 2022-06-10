<?php
/**
 * Plugin Name:     ContentKing for Conductor
 * Description:     Real-time SEO Auditing & Monitoring Platform
 * Author:          ContentKing
 * Author URI:      https://www.contentkingapp.com/
 * Text Domain:     contentking-plugin
 * Domain Path:
 * Version:         1.5.14
 *
 * @package         contentking-plugin
 */

// Prevent direct access to this file.
defined('ABSPATH') || die('This file should not be accessed directly!');

// Plugin constants.
if (defined( 'CKP_ROOT_DIR') === FALSE) {
	define('CKP_ROOT_DIR', str_replace('\\', '/', dirname(__FILE__)));
}

if (defined('CKP_ROOT_URL') === FALSE) {
	define('CKP_ROOT_URL', rtrim(plugin_dir_url(__FILE__), '/'));
}

// Include libraries.
require_once CKP_ROOT_DIR . '/vendor/autoload.php';
require_once CKP_ROOT_DIR . '/lib/class-contentkingsavepost.php';
require_once CKP_ROOT_DIR . '/lib/class-contentkingtrashpost.php';
require_once CKP_ROOT_DIR . '/lib/class-contentkingchangesitemap.php';
require_once CKP_ROOT_DIR . '/lib/contentking-api-interface.php';
require_once CKP_ROOT_DIR . '/lib/class-contentkingapi.php';
require_once CKP_ROOT_DIR . '/lib/contentking-helper-interface.php';
require_once CKP_ROOT_DIR . '/lib/class-contentkinghelper.php';

if (!class_exists('WP_Contentking')) {

/**
 * Main plugin class
 */
class WP_Contentking
{

	/**
	 * Construct the plugin object
	 */
	public function __construct()
	{
		/*Prevents curl fatal error when creating async task via wp-async-task*/
		add_filter('https_local_ssl_verify', '__return_false');

		load_textdomain('contentking-plugin', CKP_ROOT_DIR . '/languages/wp-contentking-' . get_locale() . '.mo');
		// Register hooks that are fired when the plugin is activated and deactivated.
		register_activation_hook(__FILE__, [& $this, 'activate']);
		register_deactivation_hook(__FILE__, [& $this, 'deactivate']);

		// Load plugin admin menu.
		add_action('admin_menu', [& $this, 'add_menu']);
		add_action('admin_init', [& $this, 'admin_init']);
		add_action('admin_bar_menu', [& $this, 'notification_button'], 100);

		// Styles and Scripts.
		add_action('admin_enqueue_scripts', [& $this, 'register_contentking_adminbar_styles']);
		add_action('wp_enqueue_scripts', [& $this, 'register_contentking_adminbar_styles']);
		add_action('admin_enqueue_scripts', [& $this, 'register_icon_styles']);

		// Instantiate async task class.
		add_action('plugins_loaded', [& $this, 'instantiate_async']);
		// Register for save_post action.
		add_action('wp_async_save_post', [& $this, 'send_to_api']);
		// Register for contentking_updated_sitemap action.
		add_action('wp_async_contentking_updated_sitemap', [& $this, 'send_to_api']);
		// Register for wp_trash_post action.
		add_action('wp_async_wp_trash_post', [& $this, 'send_to_api']);
		// Register for client token updates.
		add_action('update_option_contentking_client_token', [& $this, 'check_new_token'], 10, 3);
		add_action('add_option_contentking_client_token', [& $this, 'check_newly_added_token'], 10, 2);

		// Create REST API endpoint.
		add_action('rest_api_init', [& $this, 'rest_admin_edit_url']);
		// Get post id from URL.
		add_action('template_redirect', [& $this, 'get_post_id_from_url']);
		// Send WP version after upgrading.
		add_action('upgrader_process_complete', [& $this, 'after_upgrade_tasks'], 90, 2);
		// Define new action for XML sitemap.
		add_action('admin_init', [& $this, 'define_action_contentking_updated_sitemap']);
	}

	/**
	 * Instantiate WP_Async_Task for each hook.
	 *
	 * @return void
	 */
	public function instantiate_async()
	{
		new ContentkingSavePost(WP_Async_Task::LOGGED_IN);
		new ContentkingTrashPost(WP_Async_Task::LOGGED_IN);
		new ContentkingChangeSitemap(WP_Async_Task::LOGGED_IN);
	}

	/**
	 * Performs api calls to send URL to Contentking.
	 *
	 * @param array<string> $urls array of URLs to be sent to Contentking.
	 * @return void
	 */
	public function send_to_api($urls)
	{
		$api = new ContentkingAPI();

		foreach ($urls as $url) {
			$result = $api->check_url($url);

			if ($result === FALSE) {
				update_option('contentking_status_flag', '0');
				break;
			}
		}
	}

	/**
	 * Activate the plugin
	 *
	 * @return void
	 */
	public static function activate()
	{
		self::update_contentking_status_flag();
	}

	/**
	 * Deactivate the plugin
	 *
	 * @return void
	 */
	public static function deactivate()
	{
		self::update_contentking_status_flag();
	}

	/**
	 * Hook into WP's admin_init action hook
	 *
	 * @return void
	 */
	public function admin_init()
	{
		if (version_compare( phpversion(), '5.5.0', '<') ) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die(esc_html('This plugin requires PHP Version 5.5. Your current version is ' . phpversion()));
		}

		add_settings_section(
			'contentking_setting_section',
			__( 'Settings section in ContentKing', 'contentking-plugin' ),
			[& $this, 'contentking_setting_callback_function'],
			'contentking'
		);

		add_settings_field(
			'contentking_client_token',
			'ContentKing API token',
			[& $this, 'contentking_setting_callback_function'],
			'contentking',
			'contentking_setting_section'
		);

		register_setting(
			'contentking_setting_section',
			'contentking_client_token',
			[
				'sanitize_callback' => 'sanitize_text_field',
			]
		);
	}

	/**
	 * Just empty callback.
	 *
	 * @return void
	 */
	public function contentking_setting_callback_function() {}

	/**
	 * Performs api call to check API token on save.
	 *
	 * @param string $option Option name.
	 * @param mixed $value The new option value.
	 * @return void
	 */
	public function check_newly_added_token($option, $value)
	{
		self::update_contentking_status_flag($value);
	}

	/**
	 * Performs api call to check API token on save.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $new_value The new option value.
	 * @param string $option Option name.
	 * @return void
	 */
	public function check_new_token($old_value, $new_value, $option)
	{
		self::update_contentking_status_flag($new_value);
	}

	/**
	 * Check token after there was any upgrade of ContentKing plugin or WordPress
	 *
	 * @param object $upgrader WP_Upgrader instance.
	 * @param array{type: string, package?: mixed} $hook_extra Array with information on upgrade being performed.
	 * @return void
	 */
	public function after_upgrade_tasks($upgrader, $hook_extra)
	{
		if (
			(
				($hook_extra['type'] === 'plugin')
				&& isset($hook_extra['package'])
				&& in_array(plugin_basename( __FILE__ ), $hook_extra['package'], TRUE)
			)
			|| ($hook_extra['type'] === 'core')
		) {
			self::update_contentking_status_flag();
		}
	}

	/**
	 * Define action for update XML sitemap
	 *
	 * @return void
	 */
	public function define_action_contentking_updated_sitemap()
	{
		if (isset($_POST['wpseo_xml'])) {
			do_action('contentking_updated_sitemap');
		}
	}

	/**
	 * Create menu item in WP admin
	 *
	 * @return void
	 */
	public function add_menu()
	{
		global $menu, $submenu;
		add_submenu_page(
			'options-general.php',
			__(
				'ContentKing plugin',
				'contentking-plugin'
			),
			__(
				'ContentKing',
				'contentking-plugin'
			),
			'manage_options',
			'contentking',
			[& $this, 'view_plugin_screen']
		);
	}

	/**
	 * Screens
	 *
	 * @return void
	 */
	public function view_plugin_screen()
	{
		if (current_user_can('manage_options') === FALSE) {
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'contentking-plugin'));
		}

		if (
			empty($_POST) === FALSE
			&& check_admin_referer('contentking_validate_token', 'ck_validate_token')
			&& isset($_POST['validate_contentking_token'])
			&& $_POST['validate_contentking_token'] === '1'
		) {
			$this->update_contentking_status_flag();
		}

		include sprintf( '%s/screens/settings.php', dirname( __FILE__ ) );
	}

	/**
	 * Notification area
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 * @return void
	 */
	public function notification_button($wp_admin_bar)
	{
		if (current_user_can('manage_options') === FALSE) {
			return;
		}

		$wp_admin_bar->add_node([
			'id' => 'contentking',
			'title' => '<span> ' . __('ContentKing', 'contentking-plugin') . '</span>',
			'href' => get_admin_url() . 'options-general.php?page=contentking',
			'meta' => [
				'title' => 'ContentKing',
				'class' => get_option('contentking_status_flag') === '1'
					? 'contentking-green-notification'
					: 'contentking-red-notification',
			],
		]);
	}

	/**
	 * Echo post id for given URL or 0 when URL is not a single post or page.
	 *
	 * @return void
	 */
	public function get_post_id_from_url()
	{
		if (!empty($_POST) && isset($_POST['ck_get_url'])) {
			if (is_single() || is_page()) {
				global $post;
				echo wp_json_encode($post->ID);
			} else {
				echo wp_json_encode(0);
			}

			die();
		}
	}

	/**
	 * Creates REST API endpoint for ContentKing APP to get WP admin_url
	 * URL for given public post URL.
	 *
	 * @return void
	 */
	public function rest_admin_edit_url()
	{
		register_rest_route(
			'contentking/v1',
			'/admin_url/',
			[

				'methods' => 'POST',
				'callback' => [& $this, 'get_admin_url'],
			]
		);
	}

	/**
	 * Callback function for contentking/v1/admin_url endpoint.
	 *
	 * @param WP_REST_Request $wp_rest_request
	 * @return bool|string
	 */
	public function get_admin_url($wp_rest_request)
	{
		$headers = $wp_rest_request->get_headers();

		if (isset($headers['contentking_token']) === FALSE) {
			return FALSE;
		}

		if (get_option('contentking_client_token') === $headers['contentking_token'][0]) {
			$array_data = (array) $wp_rest_request->get_body();
			$decoded_data = json_decode($array_data[0], TRUE);

			if (isset($decoded_data['url'])) {
				$args = [
					'body' => [
						'ck_get_url' => 'true',
					],
				];
				$response = wp_remote_post($decoded_data['url'], $args);

				if (is_wp_error($response)) {
					return FALSE;
				}

				$post_id = json_decode($response['body']);

				if ($post_id > 0) {
					return admin_url('post.php') . "?post=$post_id&action=edit";
				}
			}
		}

		return FALSE;
	}

	/**
	 * Styles for top admin bar notification area
	 *
	 * @return void
	 */
	public function register_contentking_adminbar_styles()
	{
		if (is_admin_bar_showing()) {
			wp_register_style('contentking-stylesheet', plugins_url('assets/css/admin.css', __FILE__ ));
			wp_enqueue_style('contentking-stylesheet');
		}
	}

	/**
	 * Styles for icons at the setting page
	 *
	 * @param string $hook Slug of page we are on.
	 * @return void
	 */
	public function register_icon_styles($hook)
	{
		if ($hook !== 'settings_page_contentking') {
			return;
		}

		wp_register_style('fontello-stylesheet', plugins_url( 'assets/fonts/css/fontello.css', __FILE__ ));
		wp_enqueue_style('fontello-stylesheet' );

		wp_register_style('icon-stylesheet', plugins_url( 'assets/css/icons.css', __FILE__ ));
		wp_enqueue_style('icon-stylesheet');
	}

	/**
	 * @param string|NULL $token
	 * @return void
	 */
	private static function update_contentking_status_flag($token = NULL)
	{
		$api = new ContentkingAPI();

		update_option(
			'contentking_status_flag',
			$api->update_status(TRUE, isset($token) ? $token : get_option('contentking_client_token'))
		);
	}

}//end class

}

if ( class_exists( 'WP_Contentking' ) ) {
	// instantiate the plugin class.
	new WP_Contentking();
}
