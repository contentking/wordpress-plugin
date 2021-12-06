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
class ContentkingAPI implements ContentkingAPIInterface {

	/**
	 * API endpoint
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.contentkingapp.com/v1/';

	/**
	 * Prepare api call to update status.
	 *
	 * @param bool   $status plugin status (false - deactivated, true - activated).
	 * @param string $token API secret token to be validated.
	 * @return Bool
	 */
	public function update_status( $status, $token = '' ) {

		if ( '' === $token ) :
			$token = get_option( 'contentking_client_token' );
		endif;

		$data     = $this->prepare_request_data(
			'update_status',
			array(
				'token'  => $token,
				'status' => $status,
			)
		);
		$response = wp_remote_post( $this->api_url . 'update_status', $data );
		if ( is_wp_error( $response ) ) :
			return false;

		elseif ( isset( $response['response']['code'] ) && 200 === intval( $response['response']['code'] ) ) :
			return true;
		endif;

		return false;
	}


	/**
	 * Performs api call to send URL to Contentking.
	 *
	 * @param string $url URL to be sent to Contentking.
	 * @return Bool
	 */
	public function check_url( $url = '' ) {

		$data     = $this->prepare_request_data(
			'check_url',
			array(
				'url' => $url,
			)
		);
		$response = wp_remote_post( $this->api_url . 'check_url', $data );

		if ( ! is_wp_error( $response ) ) :
			if ( isset( $response['response']['code'] ) ) :
				if ( 200 === intval( $response['response']['code'] ) ) :
					return true;
				elseif ( 401 === intval( $response['response']['code'] ) ) :
					if ( isset( $response['body']['code'] ) && 'auth_failed' === $response['body']['code'] ) :
						return false;
					endif;
				endif;
			endif;

		endif;

		return true;

	}

	/**
	 * Prepare HTTP request data for API call
	 *
	 * @param string $method name of request.
	 * @param array  $data input data.
	 * @return Array HTTP request data.
	 */
	public function prepare_request_data( $method, $data = array() ) {

		if ( empty( $data ) ) {
			return array();
		}

		if ( isset( $data['token'] ) ) :
			$token = $data['token'];
		else :
			$token = get_option( 'contentking_client_token' );
		endif;

		$prepared_data = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'token ' . $token,
			),
		);

		if ( 'check_url' === $method ) :

			$prepared_data['body'] = wp_json_encode( array() );
			if ( isset( $data['url'] ) ) :
				$prepared_data['body'] = wp_json_encode(
					array(
						'url' => $data['url'],
					)
				);
			endif;

		elseif ( 'update_status' === $method ) :
			$body_data             = array();
			$body_data['status']   = $data['status'];
			$body_data['type']     = 'wordpress'; // phpcs:ignore spelling ok.
			$helper                = new ContentkingHelper();
			$body_data['websites'] = $helper->get_websites();
			$body_data['features'] = $helper->get_features();
			$prepared_data['body'] = wp_json_encode( $body_data );
		endif;

		return $prepared_data;
	}



}
