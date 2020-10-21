<?php
/**
 * Util function for the plugin
 *
 * @package  PaymentIQ Checkout/inc/piq-co-admin-utils
 */

class Piq_Co_Admin_Utils {
  const PAYMENIQ_CHECKOUT_STATUS_MESSAGES = 'paymentiq_checkout_status_messages';
  const PAYMENIQ_CHECKOUT_STATUS_MESSAGES_KEEP_FOR_POST = 'paymentiq_checkout_status_messages_keep_for_post';
  const ERROR = 'error';
  const SUCCESS = 'success';

  /*
  *  $order
  *  Order supports capture
  */
  public static function supports_capture ($order) {
    $allowed_piq_capture_statuses = ['SUCCESS_WAITING_CAPTURE', 'SUCCESS_WAITING_AUTO_CAPTURE'];
    $piq_tx_status = $order->get_meta('piq_tx_status');
    return in_array($piq_tx_status, $allowed_piq_capture_statuses);
  }

  /**
  * Build the list of notices to display on the administration
  *
  * @param string $type
  * @param string $message
  * @param bool $keep_post
  */
  
  public static function add_admin_notices($type, $message, $keep_post = false) {
    $message = array( "type" => $type, "message" => $message);
    $messages = get_option(self::PAYMENIQ_CHECKOUT_STATUS_MESSAGES, false);
    if(!$messages) {
          update_option(self::PAYMENIQ_CHECKOUT_STATUS_MESSAGES, array($message));
      } else {
          array_push($messages, $message);
          update_option(self::PAYMENIQ_CHECKOUT_STATUS_MESSAGES, $messages);
      }
      update_option(self::PAYMENIQ_CHECKOUT_STATUS_MESSAGES_KEEP_FOR_POST, $keep_post);
  }

  /**
  * Echo the notices to the Administration
  *
  * @return void
  */
  public static function echo_admin_notices(){
      $messages = get_option( self::PAYMENIQ_CHECKOUT_STATUS_MESSAGES, false );
      if(!$messages) {
          return;
      }
      foreach($messages as $message) {
          echo Piq_Co_Admin_Utils::message_to_html( $message['type'], $message['message'] );
      }
      if(!get_option( self::PAYMENIQ_CHECKOUT_STATUS_MESSAGES_KEEP_FOR_POST, false )) {
          delete_option( self::PAYMENIQ_CHECKOUT_STATUS_MESSAGES );
      } else {
          delete_option( self::PAYMENIQ_CHECKOUT_STATUS_MESSAGES_KEEP_FOR_POST );
      }
  }

  /**
  * Convert message to HTML
  *
  * @param string $type
  * @param string $message
  * @return string
  * */
  public static function message_to_html( $type, $message ) {
    $class = '';
    if($type === self::SUCCESS) {
        $class = "notice-success";
    } else {
        $class = "notice-error";
    }

    $html = '<div id="message" class="'.$class.' notice"><p><strong>'.ucfirst($type).'! </strong>'.$message.'</p></div>';
    return ent2ncr( $html );
  }

  /**
  * Create a javascript console.log from a php object/array
  * */
  public static function debug_to_console($data) {
    echo "<script>console.log('Debug Objects: " . json_encode($data) . "' );</script>";
  }


} // end of class 