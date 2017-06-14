<?php


class LoggerFile implements LoggerInterface{


	protected $log_file = CKP_ROOT_DIR . '/log.txt';

	public function __construct(){}

	public function log( string $input = '' ){
		global $argv;
		$ip = '';

		if( isset( $_SERVER['REMOTE_ADDR'] ) ):
			$ip = $_SERVER['REMOTE_ADDR'];
		elseif( isset($argv[0]) ):
			$ip = $argv[0];
		endif;

		error_log( "CK Plugin: " . date("d.m.Y H:i:s ") . " ($ip): " . $input . "\n" , 3, $this->log_file );

	}
}

