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
class ContentkingChangeSitemap extends WP_Async_Task
{

	/**
	 * Action which will react if there are any changes with sitemap.
	 *
	 * @var string
	 */
	protected $action = 'contentking_updated_sitemap';

	/**
	 * Priority to fire intermediate action.
	 *
	 * @var int
	 */
	protected $priority = 9999;

	/**
	 * Prepare POST data to send to session that processes the task
	 *
	 * @param array<mixed> $data Params from hook.
	 * @return array{wpseo_xml: array<string>}|NULL
	 */
	protected function prepare_data($data)
	{
		if (class_exists('WPSEO_Sitemaps_Router') === FALSE) {
			return NULL;
		}

		return [
			'wpseo_xml' => [WPSEO_Sitemaps_Router::get_base_url('sitemap_index.xml')],
		];
	}

	/**
	 * Run the asynchronous task
	 * Calls all functions hooked to async hook
	 *
	 * @return void
	 */
	protected function run_action()
	{
		if (isset($_POST['wpseo_xml'])) {
			do_action("wp_async_$this->action", $_POST['wpseo_xml']);
		}
	}

}
