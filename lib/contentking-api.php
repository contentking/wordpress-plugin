<?php

class ContentkingAPI implements ContentkingAPIInterface {

	protected $api_url = 'https://api.contentkingapp.com/v1/';

	/*
	* Prepare api call to update status.
	*
	* @param string $token API secret token to be validated.
	* @param bool $status plugin status (false - deactivated, true - activated)
	* @return Bool
	*/
	public function update_status( $token = '', $status ) {

		if( $token === '' ):
			$token = get_option( 'contentking_client_token' );
		endif;

		$data = $this->prepare_request_data( ['token' => $token, 'status' => $status], 'update_status' );
		$response = wp_remote_post( $this->api_url . 'update_status', $data );
		if ( is_wp_error( $response ) ):
			return false;

		elseif( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) === 200 ):
			return true;
		endif;

		return false;
	}


	/*
	* Performs api call to send URL to Contentking.
	*
	* @param string $url URL to be sent to Contentking.
	* @return Bool
	*/
	public function check_url( $url = '' ){

		$data = $this->prepare_request_data( ['url'=> $url], 'check_url' );
		$response = wp_remote_post( $this->api_url . 'check_url', $data );

		if ( !is_wp_error( $response ) ):
			if( isset( $response['response']['code'] ) ):
				if(intval( $response['response']['code'] ) === 200):
					return true;
				elseif( intval( $response['response']['code'] ) === 401 ):
					if( isset($response['body']['code']) && $response['body']['code'] === 'auth_failed' ):
						return false;
					endif;
				endif;
			endif;

		endif;

		return true;

	}

	/*
	* Prepare HTTP request data for API call
	*
	* @param array $data input data.
	* @param string $method name of request.
	* @return Array HTTP request data.
	*/
	public function prepare_request_data( $data = [], $method ){

		if( empty($data) )
			return [];

		if( isset( $data['token'] ) ):
			$token = 	$data['token'];
		else:
			$token = get_option( 'contentking_client_token' );
		endif;


		$prepared_data = [
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'token ' . $token
			]
		];

		if ( $method  === 'check_url' ):

			$prepared_data['body'] = json_encode([]);
			if( isset( $data['url'] ) ):
				$prepared_data['body'] = json_encode(['url' => $data['url']]);
			endif;

		elseif ( $method === 'update_status' ):
			$body_data = [];
			$body_data['status'] = $data['status'];
			$body_data['type'] = 'wordpress';
			$helper = new ContentkingHelper();
			$body_data['websites'] = $helper->get_websites();
			$body_data['features']  = $helper->get_features();
			$prepared_data['body'] = json_encode($body_data);
		endif;

		return $prepared_data;
	}



}
