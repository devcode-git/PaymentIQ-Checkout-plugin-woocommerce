<?php
/**
 * PaymentIQ Checkout page
 *
 * Overrides /checkout/form-checkout.php.
 *
 * @package paymentiq-checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once PIQ_WC_PLUGIN_PATH . '/inc/piq-co-utils.php';

wc_print_notices();

do_action( 'piq_co_wc_before_checkout_form' );
?>

<form name="checkout" class="checkout woocommerce-checkout">
	<div id="piq-checkout-wrapper">
		<div id="piq-checkout"></div>
		<?php Piq_Co_Utils::piq_wc_show_checkout(); ?>
		<?php woocommerce_order_review(); ?>
	</div>
  <script>
		// We let the javascript know that it's time to setup the checkout
		// We pass along the configured settings in payload
		setTimeout(() => {
			window.postMessage({
				eventType: '::wooCommerceSetupPIQCheckout',
				payload: {
					orderReceivedPath: '<?php Piq_Co_Utils::getOrderReceivedPath(); ?>',
					merchantId: <?php Piq_Co_Utils::getPiqMerchantId(); ?>,
					didClientId: '<?php Piq_Co_Utils::getDidClientId(); ?>',
					environment: '<?php strval( Piq_Co_Utils::getPiqEnvironment() ); ?>',
					buttonsColor: '<?php strval( Piq_Co_Utils::getPiqButtonsColor() ); ?>',
					amount: <?php Piq_Co_Utils::getPiqTotalAmount(); ?>,
					country: '<?php strval( Piq_Co_Utils::getSelectedCountry() ); ?>',
					locale: '<?php strval( Piq_Co_Utils::getSelectedLocale() ); ?>',
					checkUserDevice: <?php Piq_Co_Utils::rememberUserDevice(); ?>,
					orderItems: '<?php Piq_Co_Utils::getOrderItems(); ?>',
					freightFee: '<?php Piq_Co_Utils::getShippingTotal(); ?>',
					attributes: {
						orderId: <?php Piq_Co_Utils::getOrderId(); ?>
					}
				}
			}, '*')
		}, 250)

		/* We need to create an action hook to get back to the php code (out of our template/script file)
			 When we receive the status update of the transaction via postMessage - we make an ajax request,
			 wordpress' way of triggering an action hook via request.			 
		*/

		window.addEventListener('message', function (e) {
			if (e.data && e.data.eventType) {
				const { eventType, payload } = e.data
				switch (eventType) {
					case '::wooCommercePaymentSuccess':
						try {
							var data = {
								action: 'ACTION_NAME',
								_ajax_nonce: '<?php echo wp_create_nonce( 'piq_co_tx_status_update_nonce' ); ?>', /* wordpress way of determining validity of ajax-request from plugin */
								status: 'success',
								orderId: payload.orderId,
								...payload.data
							};
						
							/* Specific url required in order to trigger an action hooks:
								wp_ajax_{ACTION_NAME}
								wp_ajax_nopriv_{ACTION_NAME}
								in /Inc/piq-co-utils.php
							*/
							var url = '<?php echo admin_url('admin-ajax.php'); ?>';
							jQuery.post(url, data, function(response) {
									console.log('jquery callback')
							});
						} catch (err) {
							console.error(err)
							console.log('PIQCheckout: Unable to trigger ajax action hook')
						}
						break
					default:
						return
				}	
			}
		})
  </script>
</form>

<?php
require_once(dirname(__FILE__) . '/../../inc/piq-co-utils.php');
$Piq_Co_Utils = new Piq_Co_Utils();
$calculatorWidget = $Piq_Co_Utils->getCalculatorWidget();
$minPrice = intval($Piq_Co_Utils->getCalculatorWidgetMinPrice()); // If amount is bigger than 1990 NOK and the setting is turned on.
?>

<?php if($calculatorWidget && Piq_Co_Utils::getPiqTotalAmount() >= $minPrice) : ?>

<div class="products_meta">
  
  <!-- PaymentIQ Developed Widget -->
  <div id='santander-checkout-widget'></div>
  <script>

    function renderWidget () {
      const checkout_widget = new window.SANTANDER_CHECKOUT_WIDGET(
        'santander-checkout-widget',
        {
          mode: 'modern',
          environment: 'production',
          iframeHeight: '250px',
					localeId: '<?php echo $Piq_Co_Utils::getSelectedLocale(); ?>',
          country: '<?php echo $Piq_Co_Utils::getSelectedCountry(); ?>',
          theme: {
						raised: '<?php echo intval($Piq_Co_Utils->getCalculatorRaised()); ?>',
            background: '<?php echo $Piq_Co_Utils->getCalculatorBackground(); ?>',
            border: '<?php echo $Piq_Co_Utils->getCalculatorBorderColor(); ?>',
            text: '<?php echo $Piq_Co_Utils->getCalculatorTextColor(); ?>',
            borderRadius: '<?php echo $Piq_Co_Utils->getCalculatorBorderRadius(); ?>',
          },
          paymentDetails: {
						loanAmount: <?php Piq_Co_Utils::getPiqTotalAmount(); ?>
          }
        }
      );
    }

    function initWidget () {
      if (window.SANTANDER_CHECKOUT_WIDGET) {
        renderWidget()
      } else {
        setTimeout(() => {
          initWidget()
        }, 150);
      }
    }

    initWidget()
  </script>


  <!-- END PaymentIQ Developed Widget -->

</div>

<?php endif; ?>
