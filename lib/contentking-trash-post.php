<?php

class ContentkingTrashPost extends WP_Async_Task{

	/**
	* Action to use to trigger this task
	*
	* @var string
	*/
	protected $action = 'wp_trash_post'; //Fires before a post is sent to the trash.

	/**
	 * Priority to fire intermediate action.
	 *
	 * @var int
	 */
	protected $priority = 9999; //We need this to have pretty high to make sure all other actions are done.

	/**
	* Array of urls to be sent to API
	*
	* @var array
	*/
	private $urls = [];

	/**
	* Prepare POST data to send to session that processes the task
	*
	* @param array $data Params from hook
	*
	* @return array
	*/
	protected function prepare_data($data){



			$post_obj = get_post( $data[0] );

			$post_type_data = get_post_type_object( $post_obj->post_type );
			if( intval( $post_type_data->public ) === 1 || intval( $post_type_data->publicly_queryable ) === 1 ): //Post has public URL

				$url = get_permalink( $data[0] );
				$fixed_url = str_replace( '__trashed', '', $url); //Fix url in case parent page was thrashed recently.
				array_push( $this->urls, $fixed_url ); //Only data from last call will be used in async task

				return [
					'urls' => $this->urls,
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

		if( isset( $_POST[ 'urls' ] ) ):
			do_action( "wp_async_$this->action", $_POST[ 'urls' ] );
		endif;

	}

}
