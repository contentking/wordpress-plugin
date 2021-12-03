<?php
/**
 * Contentking API interface.
 *
 * @package contentking-plugin
 */

/**
 * Interface for class ContentkingAPI
 */
interface ContentkingAPIInterface {

	/**
	 * Prepare api call to update status.
	 *
	 * @param bool   $status plugin status (false - deactivated, true - activated).
	 * @param string $token API secret token to be validated.
	 * @return Bool
	 */
	public function update_status( $status, $token );

	/**
	 * Performs api call to send URL to Contentking.
	 *
	 * @param string $url URL to be sent to Contentking.
	 * @return Bool
	 */
	public function check_url( $url );

	/**
	 * Prepare HTTP request data for API call
	 *
	 * @param string $method name of request.
	 * @param array  $data input data.
	 * @return array HTTP request data.
	 */
	public function prepare_request_data( $method, $data = [] );
}
