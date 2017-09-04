<?php

interface ContentkingAPIInterface{

  public function update_status( $token, $method );
  public function check_url( $url );
  public function prepare_request_data( $data = [], $method );
}
