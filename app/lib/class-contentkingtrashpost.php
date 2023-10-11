<?php
/**
 * ContentKing Trash Post event actions.
 *
 * @package contentking-plugin
 */

/**
 * Class ContentkingTrashPost.
 *
 * @package contentking-plugin
 */
class ContentkingTrashPost extends WP_Async_Task
{

	/**
	 * Action fires before a post is sent to the trash
	 *
	 * @var string
	 */
	protected $action = 'wp_trash_post';

	/**
	 * Priority to fire intermediate action.
	 *
	 * @var int
	 */
	protected $priority = 9999;

	/**
	 * Array of urls to be sent to API
	 *
	 * @var array<mixed>
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
		$wpPost = get_post($data[0]);

		if ($wpPost === NULL) {
			throw new LogicException(
				sprintf(
					'The variable has to be instance of %s',
					WP_Post::class
				)
			);
		}

		$postTypeData = get_post_type_object($wpPost->post_type);

		if (
			$postTypeData !== NULL
			&& (
				intval($postTypeData->public) === 1
				|| intval($postTypeData->publicly_queryable ) === 1
			)
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
		if (isset($_POST['urls'])) {
			do_action("wp_async_$this->action", $_POST['urls']);
		}
	}

}
