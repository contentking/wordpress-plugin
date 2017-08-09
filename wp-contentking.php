<?php
/**
 * Plugin Name:     ContentKing
 * Description:     Real-time SEO auditing and content tracking for your website.
 * Author:          ContentKing
 * Author URI:      https://www.contentkingapp.com/
 * Text Domain:     contentking-plugin
 * Domain Path:
 * Version:         1.1.0
 *
 * @package         contentking-plugin
 */



// Prevent direct access to this file.
defined( 'ABSPATH' )  or die('This file should not be accessed directly!');

// Plugin constants
define( 'CKP_ROOT_DIR', str_replace( '\\', '/', dirname(__FILE__) ) );
define( 'CKP_ROOT_URL', rtrim( plugin_dir_url(__FILE__), '/' ) );

// Include libraries
require_once CKP_ROOT_DIR . '/vendor/autoload.php';
require_once CKP_ROOT_DIR . '/lib/contentking-save-post.php';
require_once CKP_ROOT_DIR . '/lib/contentking-trash-post.php';
require_once CKP_ROOT_DIR . '/lib/contentking-api-interface.php';
require_once CKP_ROOT_DIR . '/lib/contentking-api.php';


if( !class_exists( 'WP_Contentking' ) ){

	class WP_Contentking{

		/**
		* Construct the plugin object
		*/
		public function __construct(){

			/*Prevents curl fatal error when creating async task via wp-async-task*/
			add_filter( 'https_local_ssl_verify', '__return_false' );

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
			//Register for save_post action
			add_action( 'wp_async_wp_trash_post', array( &$this, 'send_to_api' ) );
			//Register for client token updates
			add_action( 'update_option_contentking_client_token', array( &$this, 'check_new_token' ) );
			//Create REST API endpoint
			add_action( 'rest_api_init', array( &$this,'rest_admin_edit_url' ) );
			// Get post id from URL
			add_action( 'template_redirect', array( &$this,'get_post_id_from_url' )  );

		} // END public function __construct

		/**
		* Instantiate WP_Async_Task for each hook.
		*/
		public function instantiate_async(){
			$async_save_post 	= new ContentkingSavePost(WP_Async_Task::LOGGED_IN);
			$async_trash_post = new ContentkingTrashPost(WP_Async_Task::LOGGED_IN);
		}

		/*
		* Performs api calls to send URL to Contentking.
		*
		* @param array $urls array of URLs to be sent to Contentking.
		* @return void
		*/
		public function send_to_api( $urls = NULL ){

			if( $urls === NULL )
				return;

			$api = new ContentkingAPI();

			foreach( $urls as $url ):
				$result = $api->check_url( $url );
				if( $result === false ): //Bad token, deactivate sending to API
					update_option('contentking_status_flag', '0');
					break;
				endif;
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

			if ( version_compare( phpversion(), '5.5.0', '<' ) ):
				deactivate_plugins( plugin_basename( __FILE__ ) );
				wp_die( 'This plugin requires PHP Version 5.5. Your current version is '. phpversion() );
			endif;

			//Register settings
			add_settings_section(
				'contentking_setting_section',
				__( 'Settings section in ContentKing', 'contentking-plugin' ),
				array( &$this,'contentking_setting_callback_function' ),
				'contentking'
			);

			add_settings_field(
				'contentking_client_token',
				'ContentKing API token',
				 array( &$this,'contentking_setting_callback_function' ),
				'contentking',
				'contentking_setting_section'
			);

			register_setting( 'contentking_setting_section', 'contentking_client_token', 'sanitization_token' );
		} // END public function admin_init


		/**
     * Sanitize token on save
     */
    public function sanitization_token($option) {

    	$option = sanitize_text_field($option);

      return $option;

    }

		public function contentking_setting_callback_function() {}

			/*
			* Performs api call to check API token on save.
			*
			* @param string $old_value Old token value
			* @param string $new_value New token value
			* @param string $option  Option name
			* @return Bool
			*/
		public function check_new_token($old_value, $value, $option) {

			//sending request to Contentking API
			$api = new ContentkingAPI();

			if( $api->check_token( $value ) === true):
				update_option('contentking_status_flag', '1');
			else:
				update_option('contentking_status_flag', '0');
			endif;

		}

		/*Create menu item in WP admin*/
		public function add_menu(){

			global $menu, $submenu;
			add_submenu_page( 'options-general.php', __('ContentKing plugin', 'contentking-plugin'), __('ContentKing', 'contentking-plugin'), 'manage_options', 'contentking', array( &$this, 'view_plugin_screen' ));

		}

		/*Screens*/
		public function view_plugin_screen(){

			if( !current_user_can( 'manage_options' ) ):
				wp_die( __( 'You do not have sufficient permissions to access this page.', 'contentking-plugin' ) );
			endif;
			// if this fails, check_admin_referer() will automatically print a "failed" page and die.
			if ( ! empty( $_POST ) && check_admin_referer( 'contentking_validate_token', 'ck_validate_token' ) ) {
   			if( isset( $_POST['validate_contentking_token'] ) && $_POST['validate_contentking_token'] === '1' ):
					//Attempt to validate token
					$api = new ContentkingAPI();

					if( $api->check_token() === true):
						update_option('contentking_status_flag', '1');
					else:
						update_option('contentking_status_flag', '0');
					endif;

				endif;
			}
			// Render the main page template
			include( sprintf("%s/screens/settings.php", dirname( __FILE__ ) ) );

		}

		/*Notification area */
		public function notification_button( $wp_admin_bar ){

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
					'title' => '<span>'.__( 'ContentKing', 'contentking-plugin' ).'</span>',
					'href' => get_admin_url() . 'options-general.php?page=contentking',
					'meta' => [
						'title' => 'ContentKing',
						'class' => 'contentking-green-notification',
					],
				];


			else:

				$args = [
					'id' => 'contentking',
					'title' => '<span> '.__( 'ContentKing', 'contentking-plugin' ).'</span>',
					'href' => get_admin_url() . 'options-general.php?page=contentking',
					'meta' => [
						'title' => 'ContentKing',
						'class' => 'contentking-red-notification'
					],
				];

			endif;

			$wp_admin_bar->add_node( $args );

		}

		/*
		* Return post id for given URL.
		*
		* @return Int post id or 0 when URL is not a single post or page
		*/
		public function get_post_id_from_url(){

			if(!empty($_POST) && isset($_POST['ck_get_url']) ):
				if( is_single() || is_page() ):
					global $post;
					echo json_encode($post->ID);
				else:
					echo json_encode(0);
				endif;
				die();
			endif;

		}

		/*
		* Creates REST API endpoint for ContentKing APP to get WP admin_url
		* URL for given public post URL.
		* @return void
		*/
		public function rest_admin_edit_url(){

			register_rest_route( 'contentking/v1', '/admin_url/', array(

					'methods' => 'POST',
					'callback' => array( &$this,'get_admin_url' ),

				)
			);

		}

		/*
		* Callback function for contentking/v1/admin_url endpoint.
		*
		* @param WP_REST_Request $data REST request data
		* @return string Admin URL or "false".
		*/
		public function get_admin_url( $data ){

			$headers = $data->get_headers();
			//Verify token
			if ( isset($headers['contentking_token']) ):
				if ($headers['contentking_token'][0] === get_option('contentking_client_token')):

					$array_data = (array) $data->get_body();
					$decoded_data = json_decode( $array_data[0], true ) ;

					if( isset( $decoded_data['url'] ) ):
						//Try to get post id from public URL
						$args = array( 'body' => array(  'ck_get_url' => 'true' ) );
						$response = wp_remote_post($decoded_data['url'], $args);
						$post_id = json_decode($response['body']);

						if($post_id > 0):
							return json_encode( admin_url('post.php') . "?post=$post_id&action=edit" );
						endif;

					endif;

					return json_encode(false);

				endif;
			endif;
		}

		/*Styles for top admin bar notifcation area*/
		public function register_contentking_adminbar_styles(){

			wp_register_style( 'contentking-stylesheet', plugins_url( 'assets/css/admin.css', __FILE__) );
			wp_enqueue_style( 'contentking-stylesheet' );

		}

		/*Styles for icons at the setting page*/
		public function register_icon_styles($hook){

			if ( $hook != 'settings_page_contentking')
				return;

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
