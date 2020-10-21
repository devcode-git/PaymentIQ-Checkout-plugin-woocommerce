<?php

/**
* @package PaymentIQ Checkout Plugin for Woocommerce
*
*  Handles communication with PIQ Admin Api
*     - Refunds
*     - Capture
*     - Void (cancel order)
*
*     Each one starts out with a basic auth against PIQ - returns an access_token used in the requests
*/

include_once PIQ_WC_PLUGIN_PATH . '/inc/piq-co-admin-utils.php';

class Piq_Co_Admin_Api {

  const GET  = 'GET';
  const POST = 'POST';
  const DELETE = 'DELETE';

  private $piq_api_auth = null;

  /**
   * Constructor
   *
   * @param mixed $api_key
  */
  public function __construct() {
    $authResponse = $this->make_piq_init_auth();
    $this->piq_api_auth = array_key_exists( 'access_token', $authResponse ) ? $authResponse['access_token'] : false;
    if ( !$this->piq_api_auth ) {
      throw new \WP_Error( 'paymentiq_checkout_error', 'Failed authenticating with PaymentIQ Admin');
    }
  }

  /*
   Return api-endpoint to PIQ depending on configured environment, test or production
  */
  private function getEnvironmentBaseUrl () {
    $environment = PIQ_CHECKOUT_WC()->piqEnvironment;
    switch ( $environment ) {
      case 'production':
        return 'https://api.paymentiq.io';
      case 'test':
        return 'https://test-api.paymentiq.io';
    }
  }

  /**
  *  Init request in PIQ Auth flow
  *  Pass in base64 encode client code + secret
  *  Returns bearer JSON containing token, expiry which then is to be used
  *  in admin-api requests as header: Authorization: bearer $bearerToken
  *  @return JSON
  *  {
  *    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOlsiYmFja29mZmljZV9hcGkiXSwic2NvcGUiOlsiZGVmYXVsdCJdLCJleHAiOjE1MjM2MzE3NDcsImF1dGhvcml0aWVzIjpbIlJPTEVfSUlOIiwiUk9MRV9BR0VOVCIsIlJPTEVfRVhQRVJJTUVOVEFMX0ZFQVRVUkVTIiwiUk9MRV9GSVJTVF9BUFBST1ZFUiIsIlJPTEVfQU5BTFlUSUNTIiwiUk9MRV9FRElUX1VTRVJfUFNQX0FDQ09VTlQiLCJST0xFX0FETUlOX0FQUFJPVkVSIiwiUk9MRV9NRVJDSEFOVF9BRE1JTiIsIlJPTEVfSUlOX0FETUlOIiwiUk9MRV9BTkFMWVRJQ1NfQURNSU4iXSwianRpIjoiY2U1NWQ1N2MtMDE0Zi00YjBmLTkzZGItNDMwNzc5MTQzZTE0IiwiY2xpZW50X2lkIjoiY2E0YWNiODY5ZjYwNDU1NWFlNTdlYTkzM2FiZDZhNjUifQ.C9SYwzo4yEJ_fCoXMe26qZh8sYL80Wjgy7fzkQoOtKQ",
  *    "token_type": "bearer",
  *    "expires_in": 86399,
  *    "scope": "default",
  *    "jti": "ce55d57c-014f-4b0f-93db-430779143e14"
  *  }
  */

  public function make_piq_init_auth () {
    $baseUrl = $this->getEnvironmentBaseUrl();
    $endpoint = '/paymentiq/oauth/token?grant_type=client_credentials';
    $authUrl = $baseUrl . $endpoint;

    $instance = PIQ_CHECKOUT_WC();

    $piqApiClientId = array_key_exists( 'piqApiClientId', $instance->settings ) ? $instance->settings['piqApiClientId'] : '';
    $piqApiClientSecret = array_key_exists( 'piqApiClientSecret', $instance->settings ) ? $instance->settings['piqApiClientSecret'] : '';
    $piqEnvironment = array_key_exists( 'piqEnvironment', $instance->settings ) ? $instance->settings['piqEnvironment'] : '';

    if ( !isset($piqApiClientId) || !isset($piqApiClientId) ) {
      throw new WP_Error( 'paymentiq_checkout_error', 'PIQ API Client / Secret missing');
    }

    $authorization = base64_encode($piqApiClientId . ':' . $piqApiClientSecret);

    $headers = array(
      'Content-Type: application/x-www-form-urlencoded',
      'Authorization: Basic ' . $authorization
    );

    try {

      $data = array(
        'grant_type' => 'client_credentials'
      );

      $postData = "";
      foreach( $data as $key => $val ) {
        $postData .=$key."=".$val."&";
      }
      $postData = rtrim($postData, "&");


      $response = wp_remote_post( $authUrl, array(
        'body'    => $postData,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $piqApiClientId . ':' . $piqApiClientSecret ),
        ),
      ) );

      $result = $response['body'];

      $statusCode = wp_remote_retrieve_response_code($response);
      if ( $statusCode != '200' || is_wp_error( $response ) ) {
       $error_message = wp_remote_retrieve_response_message($response);
       throw new \Exception($error_message);
       return new \WP_Error( 'paymentiq_checkout_error', $error_message);
     }

      /* Process $content here */

      return json_decode($result, true); //return as array so php is happy
    } catch (Exception $e) {
      return new \WP_Error( 'paymentiq_checkout_error', $e );
    }
  }

  /**
    * Call the PIQ admin rest service to make a refund
    *
    * @param string  $order_id
    * @param integer $amount
    * @param string  $note
    * @return mixed
  */

  public function refund_transaction ( $order_id, $amount, $note ) {
    try {
      $order = json_decode(wc_get_order( $order_id ), true);

      $piqTxId =  array_key_exists( 'transaction_id', $order ) ? $order['transaction_id'] : false;

      if ( !$piqTxId || $piqTxId == '' ) {
        return new \WP_Error( 'paymentiq_checkout_error', 'Refund failed - No PIQ transaction id found');
      }

      $piqMerchantId = PIQ_CHECKOUT_WC()->merchantId;

      $baseUrl = $this->getEnvironmentBaseUrl();
      $endpoint = '/paymentiq/admin/v1/payments/refund/';
      $refundUrl = $baseUrl . $endpoint . $piqTxId . '?merchantId=' . $piqMerchantId;

      $data = array();
      $data['info'] = $note;
      $data['txAmount'] = $amount;
      $json_data = wp_json_encode( $data );
      
      $result = $this->call_rest_service( $refundUrl, $json_data, self::POST );
      if ( is_wp_error( $result ) ) {
        return $result;
      } else if ( $result && json_decode($result, true) ) {
        /*
        * Depending on $result we either return true when successful refund or a WP error
        *
        */
        $parsedResult = json_decode($result, true);
        if ($parsedResult['state'] && $parsedResult['state'] == 'SUCCESSFUL') {
          return true; // successful so return true
        } else {
          $message = 'PaymentIQ refund failed, please contact support (txId: ' . $piqTxId . ')';
          return new \WP_Error( 'paymentiq_checkout_error', $message);
        }
      }
    } catch (Exception $e) {
      $message = 'PaymentIQ refund failed, please contact support (' . $piqTxId || $order_id . ')';
      return new \WP_Error( 'paymentiq_checkout_error', $message);
    }
  }
  
  /**
    * Call the PIQ admin rest service to make a capture
    *
    * @param string  $order_id
    * @param integer $amount
    * @param string  $note
    * @return mixed
  */

  public function capture_transaction ( $order_id, $amount, $note ) {
    try {
      $order = wc_get_order( $order_id );
      $order_json = json_decode($order, true);

      $piqTxId = array_key_exists( 'transaction_id', $order_json ) ? $order_json['transaction_id'] : false;

      if ( !$piqTxId || $piqTxId == '' ) {
        return new \WP_Error( 'paymentiq_checkout_error', 'Capture failed - No PIQ transaction id found');
      }

      $piqMerchantId = PIQ_CHECKOUT_WC()->merchantId;

      $baseUrl = $this->getEnvironmentBaseUrl();
      $endpoint = '/paymentiq/admin/v1/payments/capture/';
      $captureUrl = $baseUrl . $endpoint . $piqTxId . '?merchantId=' . $piqMerchantId;

      $data = array();
      // $data['info'] = $note; // need to add the option to add a message in the admin panel. Optional parameter in PIQ so not needed
      $data['txAmount'] = $amount;
      $json_data = wp_json_encode( $data );
      
      $result = $this->call_rest_service( $captureUrl, $json_data, self::POST );
      if ( $result &&  json_decode($result, true) ) {
        /*
        * Depending on $result we either return true when successful capture or a WP error
        */
        $parsedResult = json_decode($result, true);
        
        if ($parsedResult['state'] && $parsedResult['state'] == 'SUCCESSFUL') {
          try {
            /*
              To be able to show the already captured amount
              Some payment methods support multiple partial captures
            */
            $piq_captured_amount = $order->get_meta('piq_captured_amount');
            $total_captured_amount = $amount;
            if ( $piq_captured_amount && $piq_captured_amount !== "" ) {
              $total_captured_amount = $piq_captured_amount + $amount;
            }
            
            // Set a meta field with captured amount / already captured amount + new amount
            update_post_meta($order_id, 'piq_captured_amount', $total_captured_amount);
            return true;

          } catch (Exception $e) {
            new \WP_Error( 'paymentiq_checkout_error', 'Capture successful but failed to update Woo captured amount');
          }
        }

        if ( $captureFailed ) {
          $message = 'PaymentIQ capture failed, please contact support (txId: ' . $piqTxId . ')';
          return new \WP_Error( 'paymentiq_checkout_error', $message);
        }

        /* CHANGE THIS TO TRUE */
        return true;
      }
    } catch (Exception $e) {
      $message = 'PaymentIQ capture failed, please contact support (' . $piqTxId || $order_id . ')';
      return new \WP_Error( 'paymentiq_checkout_error', $message);
    }
  }

  /**
    * Call the PIQ admin rest service to make a void (cancel transaction)
    *
    * @param string  $order_id
    * @param string  $note
    * @return mixed
  */  
  public function void_transaction ( $order_id, $note ) {
    try {
      $order = json_decode(wc_get_order( $order_id ), true);

      $piqTxId = array_key_exists( 'transaction_id', $order ) ? $order['transaction_id'] : false;

      if ( !$piqTxId || $piqTxId == '' ) {
        return new \WP_Error( 'paymentiq_checkout_error', 'Void failed - No PIQ transaction id found');
      }

      $piqMerchantId = PIQ_CHECKOUT_WC()->merchantId;

      $baseUrl = $this->getEnvironmentBaseUrl();
      $endpoint = '/paymentiq/admin/v1/payments/void/';
      $voidUrl = $baseUrl . $endpoint . $piqTxId . '?merchantId=' . $piqMerchantId;

      $data = array();
      $data['info'] = $note;
      $json_data = wp_json_encode( $data );
      
      $result = $this->call_rest_service( $voidUrl, $json_data, self::POST );
      if ( $result &&  json_decode($result, true) ) {
        /*
        * Depending on $result we either return successful void a WP error
        *
        */
        $parsedResult = json_decode($result, true);
        if ($parsedResult['state'] && $parsedResult['state'] == 'SUCCESSFUL') {
          return true;
        } else {
          $message = 'PaymentIQ void failed, please contact support (txId: ' . $piqTxId . ')';
          return new \WP_Error( 'paymentiq_checkout_error', $message);
        }
      }
    } catch (Exception $e) {
      $message = 'PaymentIQ void failed, please contact support (' . $piqTxId || $order_id . ')';
      return new \WP_Error( 'paymentiq_checkout_error', $message);
    }
  }

  /**
    * Using Wp HTTP client
    *
    * @param string url required
    * @param integer required  $amount
    * @param string optional $note
    * @return mixed
  */
  private function call_rest_service( $url, $json_data, $method ) {
    if ( ! isset($this->piq_api_auth)) {
      throw new \WP_Error( 'paymentiq_checkout_error', 'PIQ authentication not setup');
    }

    try {
      $content_length = isset( $json_data ) ? strlen( $json_data ) : 0;
      $headers = array(
        'Content-Type' => 'application/json',
        'Content-Length' => $content_length,
        'Accept' => 'application/json',
        'Authorization' => 'bearer ' . $this->piq_api_auth
      );

      $response = wp_remote_post( $url, array(
        'method'      => $method,
        'body'        => $json_data,
        'timeout'     => 60,
        'redirection' => 5,
        'blocking'    => true,
        'httpversion' => '1.0',
        'sslverify'   => false,
        'data_format' => 'body',
        'headers'     => $headers
      ) );

      $statusCode = wp_remote_retrieve_response_code($response);
      // If request returned with anything other than 200 OK
      if ( $statusCode != '200' || is_wp_error( $response ) ) {
        $error_message = wp_remote_retrieve_response_message($response);
        throw new \Exception($error_message);
        return new \WP_Error( 'paymentiq_checkout_error', $error_message);
      }

      return wp_remote_retrieve_body($response);
    } catch (Exception $e) {
      $message = 'PaymentIQ http request failed, please contact support (' . $e . ')';
      return new \WP_Error( 'paymentiq_checkout_error', $message);
    }
  }

} // end of class
