<?php
/**
 * Contentking API.
 *
 * @file
 * Handling API requests.
 * @package contentking-plugin
 */

/**
 * Class ContentkingAPI.
 *
 * @package contentking-plugin
 */
class ContentkingAPI implements ContentkingAPIInterface
{

	/**
	 * API endpoint
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.contentkingapp.com/v1/';

	/**
	 * Prepare api call to update status.
	 *
	 * @param bool $is_plugin_active
	 * @param string $client_token
	 * @return bool
	 */
	public function update_status($is_plugin_active, $client_token = '')
	{
		if ($client_token === '') {
			$client_token = get_option('contentking_client_token');
		}

		$data = $this->prepare_request_data(
			'update_status',
			[
				'token' => $client_token,
				'status' => $is_plugin_active,
			]
		);

		if ($data === FALSE) {
			return FALSE;
		}

		$response = wp_remote_post($this->api_url . 'update_status', $data);

		if (is_wp_error($response)) {
			return FALSE;
		}

		return intval($response['response']['code']) === 200;
	}

	/**
	 * Performs api call to send URL to Contentking.
	 *
	 * @param string $url
	 * @return bool
	 */
	public function check_url($url)
	{
		$data = $this->prepare_request_data(
			'check_url',
			[
				'url' => $url,
			]
		);

		if ($data === FALSE) {
			return FALSE;
		}

		$response = wp_remote_post($this->api_url . 'check_url', $data);

		if (is_wp_error($response)) {
			return FALSE;
		}

		return intval($response['response']['code']) === 200;
	}

	/**
	 * Prepare HTTP request data for API call
	 *
	 * @param string $request_method
	 * @param array<mixed> $input_data
	 * @return array{
	 *   headers: array{
	 *     Content-Type: non-empty-string,
	 *     Authorization: non-empty-string,
	 *   },
	 *   body: string,
	 * }|FALSE
	 */
	public function prepare_request_data($request_method, $input_data)
	{
		if ($request_method === 'check_url' && isset($input_data['url'])) {
			$body = [
				'url' => $input_data['url'],
			];
		} elseif ($request_method === 'update_status') {
			$helper = new ContentkingHelper();

			$body = [
				'features' => $helper->get_features(),
				'status' => $input_data['status'],
				'type' => 'wordpress',
				'websites' => $helper->get_websites(),
			];
		} else {
			throw new BadMethodCallException($request_method);
		}

		$bodyJson = wp_json_encode($body);

		if ($bodyJson === FALSE) {
			return FALSE;
		}

		return [
			'body' => $bodyJson,
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'token ' . (
					isset($input_data['token']) ? $input_data['token'] : get_option('contentking_client_token')
				),
			],
		];
	}

}
