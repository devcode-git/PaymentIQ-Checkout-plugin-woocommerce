<?php

/**
 * @package PaymentIQ Checkout Plugin for Woocommerce
 * 
 * Defined global variables other classes can use (when extending this BaseController) 
 * 
 * @plugin_path      - keeping track of the root folder when importing classes
 * @plugin_url       - keeping track of the baseUrl of the plugin when importing assets
 * @plugin           - keeping track of the plugin name (wordpress plugins' name is unique and is used for referencing this unique plugin )
 */


 class Piq_Co_BaseController {
   public $plugin_path;

   public $plugin_url;
   
   public $plugin;

   public function __construct () {
     // 2 folders down from this file we find the plugin main php file where we can get the actual __FILE__ (just php magic...)
     $this->plugin_path = plugin_dir_path( dirname( __FILE__, 2 ) );
     $this->plugin_url = plugin_dir_url( dirname( __FILE__, 2 ) );
     $this->plugin = plugin_basename( dirname( __FILE__, 3 ) . '/paymentiq-checkout.php' );
   }
 }