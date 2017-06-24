<?php

class ContentkingWrapper extends WP_Async_Task{

	/**
	* Action to use to trigger this task
	*
	* @var string
	*/
	protected $action = 'save_post'; //action triggered whenever a post (even custom) or page is created or updated, which could be from an import, post/page edit form, xmlrpc, or post by email

	/**
	* Prepare POST data to send to session that processes the task
	*
	* @param array $data Params from hook
	*
	* @return array
	*/
	protected function prepare_data($data){

		global $contentking_ids;
		
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ):
			return null;
		endif;
		
		if( $data[1]->post_status === 'publish' ): //Post is published

			$post_type_data = get_post_type_object( $data[1]->post_type );
			if( intval( $post_type_data->public ) === 1 || intval( $post_type_data->publicly_queryable ) === 1 ): //Post has public URL

				array_push( $contentking_ids, $data[0] ); //Only data from last call will be used in async task

				return [
					'post_id' => $data[0],
					'ids' => json_encode( $contentking_ids ),
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

		if( isset( $_POST[ 'ids' ] ) ):
			do_action( "wp_async_$this->action", $_POST[ 'ids' ] );
		endif;

	}

}
