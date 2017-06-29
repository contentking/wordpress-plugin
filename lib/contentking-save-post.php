<?php

class ContentkingSavePost extends WP_Async_Task{

	/**
	* Action to use to trigger this task
	*
	* @var string
	*/
	protected $action = 'save_post'; //action triggered whenever a post (even custom) or page is created or updated, which could be from an import, post/page edit form, xmlrpc, or post by email

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

		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ):
			return null;
		endif;

		if( $data[1]->post_status === 'publish' ): //Post is published

			$post_type_data = get_post_type_object( $data[1]->post_type );
			if( intval( $post_type_data->public ) === 1 || intval( $post_type_data->publicly_queryable ) === 1 ): //Post has public URL
				$url = get_permalink( $data[0] );
				$fixed_url = str_replace( '__trashed', '', $url); //Fix url in case parent page was thrashed recently.
				array_push( $this->urls, $fixed_url ); //Only data from last call will be used in async task

				return [
					'urls' => $this->urls,
				];

			endif;
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
