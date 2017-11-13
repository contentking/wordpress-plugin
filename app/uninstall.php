<?php
/**
 * This file is used instead of hooked method.
 *
 * @file
 * Actions fired when plugin is removed.
 * @package contentking-plugin
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

delete_option( 'contentking_client_token' );
delete_option( 'contentking_status_flag' );
