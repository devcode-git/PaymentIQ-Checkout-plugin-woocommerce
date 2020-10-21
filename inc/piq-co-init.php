<?php

/**
 * @package PaymentIQ Checkout Plugin for Woocommerce
 * 
 * In this class we just register and setup the classes this plugin will use
 * getServices returns an array of the classes we use
 * Each service is initialized in registerServices via instantiate and then the function register is
 * called for each class.
 */

include( PIQ_WC_PLUGIN_PATH . '/inc/Base/piq-co-settings-links.php' );
include( PIQ_WC_PLUGIN_PATH . '/inc/Base/piq-co-enqueue.php' );

 final class Piq_Co_Init {
  public static function getServices() {
    return [
      Piq_Co_Settings_Links::class,
      Piq_Co_Enqueue::class
    ];
  }

  /* Loop the services from self.getServices
     Run each one through self.instantiate and then call the register function in that class
     The register function will setup whats needed for that class.
  */
  public static function registerServices() {
    foreach( self::getServices() as $class ) {
      $service = self::instantiate( $class );
      if ( method_exists( $service, 'register' ) ) {
        $service->register();
      }
    }
  }

  /*  Initialize the received class
      @param class $class          - class from array in getServices
      @return class instance       - new instance of the class
  */
  private static function instantiate ( $class ) {
    return new $class();
  }

 }