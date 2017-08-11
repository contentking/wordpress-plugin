<?php

if( ! defined('WP_UNINSTALL_PLUGIN') )
	die;

$api = new ContentkingAPI();
$api->check_token( get_option( 'contentking_client_token' ), 'uninstall' );
delete_option( 'contentking_client_token' );
delete_option( 'contentking_status_flag' );

?>		