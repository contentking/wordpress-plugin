<?php
/**
 * Contentking API interface.
 *
 * @package contentking-plugin
 */

/**
 * Interface for class ContentkingAPI
 */
interface ContentkingAPIInterface
{

	/**
	 * Prepare api call to update status.
	 *
	 * @param bool $status plugin status (false - deactivated, true - activated).
	 * @param string $token API secret token to be validated.
	 * @return bool
	 */
	public function update_status($status, $token);

	/**
	 * Performs api call to send URL to Contentking.
	 *
	 * @param string $url URL to be sent to Contentking.
	 * @return bool
	 */
	public function check_url($url);

	/**
	 * Prepare HTTP request data for API call
	 *
	 * @param string $method name of request.
	 * @param array<mixed> $data input data.
	 * @return array<mixed> HTTP request data.
	 */
	public function prepare_request_data($method, $data);

}
