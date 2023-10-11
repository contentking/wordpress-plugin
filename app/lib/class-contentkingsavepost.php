<?php
/**
 * ContentKing Save Post event actions.
 *
 * @package contentking-plugin
 */

/**
 * Class ContentkingSavePost.
 *
 * @package contentking-plugin
 */
class ContentkingSavePost extends WP_Async_Task
{

	/**
	 * Action triggered whenever a post (even custom) or page is created or updated, which could be from an import, post/page edit form, xmlrpc, or post by email.
	 *
	 * @var string
	 */
	protected $action = 'save_post';

	/**
	 * Priority to fire intermediate action.
	 *
	 * @var int
	 */
	protected $priority = 9999;

	/**
	 * Array of urls to be sent to API
	 *
	 * @var array<string>
	 */
	private $urls = [];

	/**
	 * Prepare POST data to send to session that processes the task
	 *
	 * @param array<mixed> $data Params from hook.
	 * @return array<mixed>|NULL
	 */
	protected function prepare_data($data)
	{
		if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
			return NULL;
		}

		if ($data[1]->post_status === 'publish') {
			$post_type_data = get_post_type_object($data[1]->post_type);

			if ($post_type_data === NULL) {
				return NULL;
			}

			if (
				intval($post_type_data->public) === 1
				|| intval($post_type_data->publicly_queryable) === 1
			) {
				$url = get_permalink($data[0]);

				if ($url !== FALSE) {
					$fixed_url = str_replace('__trashed', '', $url);
					$this->urls[] = $fixed_url;
				}

				return [
					'urls' => $this->urls,
				];
			}
		}

		return NULL;
	}

	/**
	 * Run the asynchronous task
	 *
	 * Calls all functions hooked to async hook
	 *
	 * @return void
	 */
	protected function run_action()
	{
		if (isset($_POST['urls']) ) {
			do_action("wp_async_$this->action", $_POST['urls']);
		}
	}

}
