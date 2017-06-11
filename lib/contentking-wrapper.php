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
      //TO DO: check post type is with public link
      return array(
          'post_id' => $data[0]
      );
  }

  /**
  * Run the asynchronous task
  *
  * Calls all functions hooked to async hook
  */
  protected function run_action() {
      if( isset( $_POST[ 'post_id' ] ) && 0 < absint( $_POST[ 'post_id' ] ) ){
          do_action( "wp_async_$this->action", get_permalink( $_POST[ 'post_id' ] ) );
      }

  }


}
