<?php
/**
 * Plugin Name:     ContentKing
 * Description:     Real-time SEO auditing and content tracking for your website.
 * Author:          ContentKing
 * Author URI:      https://www.contentkingapp.com/
 * Text Domain:     contentking-plugin
 * Domain Path:
 * Version:         1.5.6
 *
 * @package         contentking-plugin
 */

// Prevent direct access to this file.
defined( 'ABSPATH' ) || die( 'This file should not be accessed directly!' );

// Plugin constants.
define( 'CKP_ROOT_DIR', str_replace( '\\', '/', dirname( __FILE__ ) ) );
define( 'CKP_ROOT_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );

// Include libraries.
require_once CKP_ROOT_DIR . '/vendor/autoload.php';
require_once CKP_ROOT_DIR . '/lib/class-contentkingsavepost.php';
require_once CKP_ROOT_DIR . '/lib/class-contentkingtrashpost.php';
require_once CKP_ROOT_DIR . '/lib/class-contentkingchangesitemap.php';
require_once CKP_ROOT_DIR . '/lib/contentking-api-interface.php';
require_once CKP_ROOT_DIR . '/lib/class-contentkingapi.php';
require_once CKP_ROOT_DIR . '/lib/contentking-helper-interface.php';
require_once CKP_ROOT_DIR . '/lib/class-contentkinghelper.php';

if ( ! class_exists( 'WP_Contentking' ) ) :

	/**
	 * Main plugin class
	 */
	class WP_Contentking {

		/**
		 * Construct the plugin object
		 */
		public function __construct() {

			/*Prevents curl fatal error when creating async task via wp-async-task*/
			add_filter( 'https_local_ssl_verify', '__return_false' );

			load_textdomain( 'contentking-plugin', CKP_ROOT_DIR . '/languages/wp-contentking-' . get_locale() . '.mo' );
			// Register hooks that are fired when the plugin is activated and deactivated.
			register_activation_hook( __FILE__, array( &$this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

			// Load plugin admin menu.
			add_action( 'admin_menu', array( &$this, 'add_menu' ) );
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'admin_bar_menu', array( &$this, 'notification_button' ), 100 );

			// Styles and Scripts.
			add_action( 'admin_enqueue_scripts', array( &$this, 'register_contentking_adminbar_styles' ) );
			add_action( 'wp_enqueue_scripts', array( &$this, 'register_contentking_adminbar_styles' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'register_icon_styles' ) );

			// Instantiate async task class.
			add_action( 'plugins_loaded', array( &$this, 'instantiate_async' ) );
			// Register for save_post action.
			add_action( 'wp_async_save_post', array( &$this, 'send_to_api' ) );
			// Register for contentking_updated_sitemap action.
			add_action( 'wp_async_contentking_updated_sitemap', array( &$this, 'send_to_api' ) );
			// Register for wp_trash_post action.
			add_action( 'wp_async_wp_trash_post', array( &$this, 'send_to_api' ) );
			// Register for client token updates.
			add_action( 'update_option_contentking_client_token', array( &$this, 'check_new_token' ), 10, 3 );
			add_action( 'add_option_contentking_client_token', array( &$this, 'check_newly_added_token' ), 10, 2 );

			// Create REST API endpoint.
			add_action( 'rest_api_init', array( &$this, 'rest_admin_edit_url' ) );
			// Get post id from URL.
			add_action( 'template_redirect', array( &$this, 'get_post_id_from_url' ) );
			// Send WP version after upgrading.
			add_action( 'upgrader_process_complete', array( &$this, 'after_upgrade_tasks' ), 90, 2 );
			// Define new action for XML sitemap.
			add_action( 'admin_init', array( &$this, 'define_action_contentking_updated_sitemap' ) );

		} // END public function __construct.

		/**
		 * Instantiate WP_Async_Task for each hook.
		 */
		public function instantiate_async() {
			$async_save_post    = new ContentkingSavePost( WP_Async_Task::LOGGED_IN );
			$async_trash_post = new ContentkingTrashPost( WP_Async_Task::LOGGED_IN );
			$async_contentking_updated_sitemap = new ContentkingChangeSitemap( WP_Async_Task::LOGGED_IN );
		}

		/**
		 * Performs api calls to send URL to Contentking.
		 *
		 * @param array $urls array of URLs to be sent to Contentking.
		 * @return void
		 */
		public function send_to_api( $urls = null ) {

			if ( null === $urls ) {
				return;
			}

			$api = new ContentkingAPI();

			foreach ( $urls as $url ) :
				$result = $api->check_url( $url );
				if ( false === $result ) : // Bad token, deactivate sending to API.
					update_option( 'contentking_status_flag', '0' );
					break;
				endif;
			endforeach;

		}

		/**
		 * Activate the plugin
		 */
		public static function activate() {

			$flag = get_option( 'contentking_status_flag' );

			// Status flag was not found in DB - initialize it.
			if ( false === $flag ) :
				update_option( 'contentking_status_flag', '0' );

				// Status flag is 0 - check token and try to validate it.
			elseif ( '0' === $flag ) :
				if ( get_option( 'contentking_client_token' ) !== false ) :

					$api = new ContentkingAPI();
					$token = get_option( 'contentking_client_token' );
					if ( $api->update_status( $token, true ) === true ) :
						update_option( 'contentking_status_flag', '1' );
					else :
						update_option( 'contentking_status_flag', '0' );
					endif;

				endif;

			endif;

		} // END public static function activate.

		/**
		 * Deactivate the plugin
		 */
		public static function deactivate() {

			$api = new ContentkingAPI();
			$token = get_option( 'contentking_client_token' );
			$api->update_status( $token, false );

		} // END public static function deactivate().


		/**
		 * Hook into WP's admin_init action hook
		 */
		public function admin_init() {

			if ( version_compare( phpversion(), '5.5.0', '<' ) ) :
				deactivate_plugins( plugin_basename( __FILE__ ) );
				wp_die( esc_html( 'This plugin requires PHP Version 5.5. Your current version is ' . phpversion() ) );
			endif;

			// Register settings.
			add_settings_section(
				'contentking_setting_section',
				__( 'Settings section in ContentKing', 'contentking-plugin' ),
				array( &$this, 'contentking_setting_callback_function' ),
				'contentking'
			);

			add_settings_field(
				'contentking_client_token',
				'ContentKing API token',
				array( &$this, 'contentking_setting_callback_function' ),
				'contentking',
				'contentking_setting_section'
			);

			register_setting( 'contentking_setting_section', 'contentking_client_token', 'sanitization_token' );
		} // END public function admin_init.


		/**
		 * Sanitize token on save
		 *
		 * @param string $option - Option string to be sanitized.
		 * @return string - Sanitized option.
		 */
		public function sanitization_token( $option ) {

			$option = sanitize_text_field( $option );

			return $option;

		}

		/**
		 * Just empty callback.
		 */
		public function contentking_setting_callback_function() {}

			/**
			 * Performs api call to check API token on save.
			 *
			 * @param string $option Option name.
			 * @param mixed  $value  The new option value.
			 * @return void
			 */
		public function check_newly_added_token( $option, $value ) {

			// sending request to Contentking API.
			$api = new ContentkingAPI();
			if ( true === $api->update_status( $value, true ) ) :
				update_option( 'contentking_status_flag', '1' );
			else :
				update_option( 'contentking_status_flag', '0' );
			endif;
		}
			/**
			 * Performs api call to check API token on save.
			 *
			 * @param mixed  $old_value The old option value.
			 * @param mixed  $new_value The new option value.
			 * @param string $option    Option name.
			 * @return void
			 */
		public function check_new_token( $old_value, $new_value, $option ) {

			// sending request to Contentking API.
			$api = new ContentkingAPI();
			if ( true === $api->update_status( $new_value, true ) ) :
				update_option( 'contentking_status_flag', '1' );
			else :
				update_option( 'contentking_status_flag', '0' );
			endif;

		}

		/**
		 * Check token after there was any upgrade of ContentKing plugin or WordPress
		 *
		 * @param object $upgrader WP_Upgrader instance.
		 * @param array  $hook_extra Array with information on upgrade being performed.
		 * @return void
		 */
		public function after_upgrade_tasks( $upgrader, $hook_extra ) {

			if ( ( ( 'plugin' === $hook_extra['type'] ) && in_array( plugin_basename( __FILE__ ), $hook_extra['package'], true ) ) || ( 'core' === $hook_extra['type'] ) ) :

				$api = new ContentkingAPI();
				$token = get_option( 'contentking_client_token' );

				if ( $api->update_status( $token, true ) === true ) :
					update_option( 'contentking_status_flag', '1' );
				else :
					update_option( 'contentking_status_flag', '0' );
				endif;

			endif;

		}

		/**
		 * Define action for update XML sitemap
		 */
		public function define_action_contentking_updated_sitemap() {

			if ( isset( $_POST['wpseo_xml'] ) ) :// WPCS: input var ok, CSRF ok.

				do_action( 'contentking_updated_sitemap' );

			endif;
		}

		/**
		 * Create menu item in WP admin
		 */
		public function add_menu() {

			global $menu, $submenu;
			add_submenu_page( 'options-general.php', __( 'ContentKing plugin', 'contentking-plugin' ), __( 'ContentKing', 'contentking-plugin' ), 'manage_options', 'contentking', array( &$this, 'view_plugin_screen' ) );

		}

		/**
		 * Screens
		 */
		public function view_plugin_screen() {

			if ( ! current_user_can( 'manage_options' ) ) :
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'contentking-plugin' ) );
			endif;
			// if this fails, check_admin_referer() will automatically print a "failed" page and die.
			if ( ! empty( $_POST ) && check_admin_referer( 'contentking_validate_token', 'ck_validate_token' ) ) {// WPCS: input var ok.
				if ( isset( $_POST['validate_contentking_token'] ) && '1' === $_POST['validate_contentking_token'] ) :// WPCS: input var ok.
					// Attempt to validate token.
					$api = new ContentkingAPI();
					$token = get_option( 'contentking_client_token' );

					if ( $api->update_status( $token, true ) === true ) :
						update_option( 'contentking_status_flag', '1' );
						else :
							update_option( 'contentking_status_flag', '0' );
							endif;

				endif;
			}
			// Render the main page template.
			include( sprintf( '%s/screens/settings.php', dirname( __FILE__ ) ) );

		}

		/**
		 * Notification area
		 *
		 * @param object $wp_admin_bar - Admin Bar object.
		 */
		public function notification_button( $wp_admin_bar ) {

			if ( ! current_user_can( 'manage_options' ) ) : // Don't show contentking in admin bar.
				return;
			endif;

			if ( null === $wp_admin_bar ) {
				return;
			}

			$result = get_option( 'contentking_status_flag' );
			// Show green or red button in admin top bar.
			if ( '1' === $result ) :

				$args = [
					'id' => 'contentking',
					'title' => '<span>' . __( 'ContentKing', 'contentking-plugin' ) . '</span>',
					'href' => get_admin_url() . 'options-general.php?page=contentking',
					'meta' => [
						'title' => 'ContentKing',
						'class' => 'contentking-green-notification',
					],
				];

			else :

				$args = [
					'id' => 'contentking',
					'title' => '<span> ' . __( 'ContentKing', 'contentking-plugin' ) . '</span>',
					'href' => get_admin_url() . 'options-general.php?page=contentking',
					'meta' => [
						'title' => 'ContentKing',
						'class' => 'contentking-red-notification',
					],
				];

			endif;

			$wp_admin_bar->add_node( $args );

		}

		/**
		 * Echo post id for given URL or 0 when URL is not a single post or page.
		 *
		 * @return void
		 */
		public function get_post_id_from_url() {

			if ( ! empty( $_POST ) && isset( $_POST['ck_get_url'] ) ) :// WPCS: input var ok, CSRF ok.
				if ( is_single() || is_page() ) :
					global $post;
					echo wp_json_encode( $post->ID );
				else :
					echo wp_json_encode( 0 );
				endif;
				die();
			endif;

		}

		/**
		 * Creates REST API endpoint for ContentKing APP to get WP admin_url
		 * URL for given public post URL.
		 *
		 * @return void
		 */
		public function rest_admin_edit_url() {

			register_rest_route(
				'contentking/v1', '/admin_url/', array(

					'methods' => 'POST',
					'callback' => array( &$this, 'get_admin_url' ),

				)
			);

		}

		/**
		 * Callback function for contentking/v1/admin_url endpoint.
		 *
		 * @param object $data REST request data.
		 * @return mixed Admin URL or "false".
		 */
		public function get_admin_url( $data ) {

			$headers = $data->get_headers();
			// Verify token.
			if ( isset( $headers['contentking_token'] ) ) :
				if ( get_option( 'contentking_client_token' ) === $headers['contentking_token'][0] ) :

					$array_data = (array) $data->get_body();
					$decoded_data = json_decode( $array_data[0], true );

					if ( isset( $decoded_data['url'] ) ) :
						// Try to get post id from public URL.
						$args = array(
							'body' => array(
								'ck_get_url' => 'true',
							),
						);
						$response = wp_remote_post( $decoded_data['url'], $args );
						$post_id = json_decode( $response['body'] );

						if ( $post_id > 0 ) :
							return admin_url( 'post.php' ) . "?post=$post_id&action=edit";
						endif;

					endif;

					return false;

				endif;
			endif;
		}

		/**
		 * Styles for top admin bar notifcation area
		 */
		public function register_contentking_adminbar_styles() {

			wp_register_style( 'contentking-stylesheet', plugins_url( 'assets/css/admin.css', __FILE__ ) );
			wp_enqueue_style( 'contentking-stylesheet' );

		}

		/**
		 * Styles for icons at the setting page
		 *
		 * @param string $hook Slug of page we are on.
		 */
		public function register_icon_styles( $hook ) {

			if ( 'settings_page_contentking' !== $hook ) {
				return;
			}

			wp_register_style( 'fontello-stylesheet', plugins_url( 'assets/fonts/css/fontello.css', __FILE__ ) );
			wp_enqueue_style( 'fontello-stylesheet' );

			wp_register_style( 'icon-stylesheet', plugins_url( 'assets/css/icons.css', __FILE__ ) );
			wp_enqueue_style( 'icon-stylesheet' );
		}

	}//end class

endif;

if ( class_exists( 'WP_Contentking' ) ) :

	// instantiate the plugin class.
	$wp_contentking = new WP_Contentking();

endif;
