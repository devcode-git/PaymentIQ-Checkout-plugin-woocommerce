<?php

/**
 * @package PaymentIQ Checkout Plugin for Woocommerce
 * 
 * Adds a custom link(s) to the plugin in the Admin view of wordpress
 * In this case a link to this plugin's settings page
 * 
 * Using global variabels:
 * @plugin -> Name reference to our plugin (See BaseController.php)
 */

include( PIQ_WC_PLUGIN_PATH . '/inc/Base/piq-co-base-controller.php' );

class Piq_Co_Settings_Links extends Piq_Co_BaseController {
  public function register () {
    add_filter( "plugin_action_links_" . $this->plugin, array( $this, 'settingsLink' ) );   
  }

  public function settingsLink ( $links ) {
    $settingsLink = "<a href='admin.php?page=wc-settings&tab=checkout&section=paymentiq-checkout'>Settings</a>";
    array_push( $links, $settingsLink );
    return $links;
  }
}