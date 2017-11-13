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
	 * @param string $token API secret token to be validated.
	 * @param bool   $status plugin status (false - deactivated, true - activated).
	 * @return Bool
	 */
	public function update_status( $token, $status );

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
	 * @param array  $data input data.
	 * @param string $method name of request.
	 * @return array HTTP request data.
	 */
	public function prepare_request_data( $data = [], $method );
}
