<?php

if( ! defined('WP_UNINSTALL_PLUGIN') )
	die;

delete_option( 'contentking_client_token' );
delete_option( 'contentking_status_flag' );

?>
