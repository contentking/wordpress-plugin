<?php
/**
 * Plugin Name:     Contentking
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          Contentking
 * Author URI:      https://www.contentkingapp.com/
 * Text Domain:     contentking-plugin
 * Domain Path:
 * Version:         0.4.0
 *
 * @package         contentking-plugin
 */



// Prevent direct access to this file.
defined( 'ABSPATH' )  or die('This file should not be accessed directly!');
define( 'CKP_ROOT_DIR', str_replace( '\\', '/', dirname(__FILE__) ) );
define( 'CKP_ROOT_URL', rtrim( plugin_dir_url(__FILE__), '/' ) );
require_once CKP_ROOT_DIR . '/vendor/autoload.php';
require_once CKP_ROOT_DIR . '/lib/contentking-wrapper.php';
require_once CKP_ROOT_DIR . '/lib/contentking-api-interface.php';
require_once CKP_ROOT_DIR . '/lib/contentking-api.php';
require_once CKP_ROOT_DIR . '/lib/loggerInterface.php';
require_once CKP_ROOT_DIR . '/lib/loggerFile.php';

$contentking_ids = [];
global $contentking_ids;
if( !class_exists( 'WP_Contentking' ) ){

	class WP_Contentking{

		/**
		* Construct the plugin object
		*/
		public function __construct(){

			load_textdomain('contentking-plugin', CKP_ROOT_DIR. '/languages/wp-contentking-' . get_locale() . '.mo');
			// Register hooks that are fired when the plugin is activated and deactivated.
			register_activation_hook( __FILE__, array( &$this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

			// Load plugin admin menu
			add_action( 'admin_menu', array( &$this, 'add_menu' ) );
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'admin_bar_menu', array( &$this, 'notification_button'), 100 );

			//Styles and Scripts
			add_action( 'admin_enqueue_scripts', array( &$this, 'register_contentking_adminbar_styles' ) );
			add_action( 'wp_enqueue_scripts', array( &$this, 'register_contentking_adminbar_styles' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'register_icon_styles' ) );


			//Instantiate async task class
			add_action('plugins_loaded', array(&$this, 'instantiate_async'));
			//Register for save_post action
			add_action( 'wp_async_save_post', array( &$this, 'send_to_api' ) );
			//Register for client token updates
			add_action( 'update_option_contentking_client_token', array( &$this, 'check_new_token' ) );

		} // END public function __construct

		public function instantiate_async(){
			$async = new ContentkingWrapper(WP_Async_Task::LOGGED_IN);
		}

		public function send_to_api( $ids = NULL ){

			if( $ids === NULL )
				return;

			$posts_ids = json_decode($ids);
			$api = new ContentkingAPI();

			foreach( $posts_ids as $id ):
				$url = get_permalink( $id );
				$result = $api->check_url( $url ); //ToDo: check $result
			endforeach;

		}

		/**
		* Activate the plugin
		*/
		public static function activate(){			

			$flag = get_option('contentking_status_flag');

			//Status flag was not found in DB - initialize it.
			if( $flag === false ):
				update_option('contentking_status_flag', '0');

			//Status flag is 0 - check token and try to validate it.
			elseif( $flag === '0' ):
				if( get_option('contentking_client_token') !== false ):

					$api = new ContentkingAPI();
					if( $api->check_token( ) === true):
						update_option('contentking_status_flag', '1');
					else:
						update_option('contentking_status_flag', '0');
					endif;

				endif;

			endif;

		} // END public static function activate

		/**
		* Deactivate the plugin
		*/
		public static function deactivate(){

			//nothing to do
			
		} // END public static function deactivate()


		/**
		* hook into WP's admin_init action hook
		*/
		public function admin_init(){

			//Register settings
			add_settings_section(
				'contentking_setting_section',
				__( 'Example settings section in contentking', 'contentking-plugin' ),
				array( &$this,'contentking_setting_callback_function' ),
				'contentking'
			);

			add_settings_field(
				'contentking_client_token',
				'Contentking token',
				 array( &$this,'contentking_setting_callback_function' ),
				'contentking',
				'contentking_setting_section'
			);

			register_setting( 'contentking_setting_section', 'contentking_client_token' );
		} // END public function admin_init

		public function contentking_setting_callback_function() {}

		public function check_new_token($old_value, $value, $option) {

			//sending request to Contentking API
			$api = new ContentkingAPI();

			if( $api->check_token( $value ) === true):
				update_option('contentking_status_flag', '1');
			else:
				update_option('contentking_status_flag', '0');
			endif;

		}


		public function add_menu(){

			global $menu, $submenu;
			add_submenu_page( 'options-general.php', __('Contentking plugin', 'contentking-plugin'), __('Contentking', 'contentking-plugin'), 'manage_options', 'contentking', array( &$this, 'view_plugin_screen' ));

		}

		/*Screens*/
		public function view_plugin_screen(){

			if( !current_user_can( 'manage_options' ) ):
				wp_die( __( 'You do not have sufficient permissions to access this page.', 'contentking-plugin' ) );
			endif;

			// Render the main page template
			include( sprintf("%s/screens/settings.php", dirname( __FILE__ ) ) );

		}

		/*Notification area */
		public function notification_button($wp_admin_bar){

			if( !current_user_can( 'manage_options' ) ):
				wp_die( __( 'You do not have sufficient permissions to access this page.', 'contentking-plugin' ) );
			endif;

			if ($wp_admin_bar === null)
				return;

			$result = get_option('contentking_status_flag');
			//Show green or red button in admin top bar
			if ($result === '1'):

				$args = [
					'id' => 'contentking',
					'title' => '<span>'.__( 'Contentking', 'contentking-plugin' ).'</span>',
					'href' => get_admin_url() . 'options-general.php?page=contentking',
					'meta' => [
						'title' => 'Contentking',
						'class' => 'contentking-green-notification',
					],
				];


			else:

				$args = [
					'id' => 'contentking',
					'title' => '<span> '.__( 'Contentking', 'contentking-plugin' ).'</span>',
					'href' => get_admin_url() . 'options-general.php?page=contentking',
					'meta' => [
						'title' => 'Contentking',
						'class' => 'contentking-red-notification'
					],
				];

			endif;

			$wp_admin_bar->add_node( $args );

		}

		/*Styles for top admin bar notifcation area*/
		public function register_contentking_adminbar_styles(){

			wp_register_style( 'contentking-stylesheet', plugins_url( 'assets/css/admin.css', __FILE__) );
			wp_enqueue_style( 'contentking-stylesheet' );

		}

		/*Styles for icons at the setting page*/
		public function register_icon_styles($hook){

			if ( $hook != 'settings_page_contentking'):

				return;
			
			endif;

			wp_register_style( 'fontello-stylesheet', plugins_url( 'assets/fonts/css/fontello.css', __FILE__) );
			wp_enqueue_style( 'fontello-stylesheet' );

			wp_register_style( 'icon-stylesheet', plugins_url( 'assets/css/icons.css', __FILE__) );
			wp_enqueue_style( 'icon-stylesheet' );
		}

	}// END class WP_Contentking{

}// END if( !class_exists( 'WP_Contentking' ) ){

if( class_exists( 'WP_Contentking' ) ){

	// instantiate the plugin class
	$WP_Contentking = new WP_Contentking();

} // END if( class_exists( 'WP_Contentking' ) ){
