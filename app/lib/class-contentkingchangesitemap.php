<?php
/**
 * Sitemap-related actions.
 *
 * @file
 * Handling sitemap actions.
 * @package contentking-plugin
 */

 /**
  * Class ContentkingChangeSitemap.
  *
  * @class
  * Extends WordPress Async Task.
  * @package contentking-plugin
  */
class ContentkingChangeSitemap extends WP_Async_Task {

	/**
	 * Action to use to trigger this task
	 *
	 * @var string
	 */
	protected $action = 'contentking_updated_sitemap'; // Action, which will react, if there're any changes with sitemap.
	/**
	 * Priority to fire intermediate action.
	 *
	 * @var int
	 */
	protected $priority = 9999; // We need this to have pretty high to make sure all other actions are done.

	/**
	 * Prepare POST data to send to session that processes the task
	 *
	 * @param array $data Params from hook.
	 *
	 * @return array|NULL
	 */
	protected function prepare_data( $data ) {

		if ( class_exists( 'WPSEO_Sitemaps_Router' ) ) :

			$url = WPSEO_Sitemaps_Router::get_base_url( 'sitemap_index.xml' );

			return [
				'wpseo_xml' => [ $url ],
			];

		endif;

		return null;
	}

	/**
	 * Run the asynchronous task
	 *
	 * Calls all functions hooked to async hook
	 */
	protected function run_action() {

		if ( isset( $_POST['wpseo_xml'] ) ) : // WPCS: input var ok, CSRF ok.
			do_action( "wp_async_$this->action", $_POST['wpseo_xml'] );// WPCS: input var ok, sanitization ok CSRF ok.
		endif;

	}

}
