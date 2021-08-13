<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;

include_once PIQ_WC_PLUGIN_PATH . '/inc/piq-co-utils.php';
?>


<?php
$piq_user_id = $order->get_meta('_piq_user_id');
$piq_tx_id = $order->get_meta('_piq_tx_id');

Piq_Co_Utils::piq_wc_empty_cart();

?>

<div class="woocommerce-order">

	<?php
	if ( $order ) :
    do_action( 'woocommerce_before_thankyou', $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$piqTxId =  array_key_exists( 'transaction_id', $order ) ? $order['transaction_id'] : false;
		$order_items = array();

		foreach ( $order->get_items() as $item_id => $item ) {
			$current_item = array();

			$name = $item->get_name();
			$number = $item->get_product_id();
			$sub_tax = $item->get_subtotal_tax();
			$sub_total = $item->get_subtotal();
			$quantity = $item->get_quantity();

			$current_item['label'] = $name;
			$current_item['price'] = $sub_total / $quantity; // sub_total is the price * quantity, meaning total per item. We want the price per product
			$current_item['vat'] = $sub_tax / $quantity;
			$current_item['quantity'] = $quantity;
			$current_item['number'] = $number;

			array_push($order_items, $current_item);
		}
		
		// return json-formatteted array of objects
		$order_data = wp_json_encode( $order_items );

		$shipping_total = $order->get_shipping_total();
		$shipping_tax = $order->get_shipping_tax();
		$order_freight_fee = $shipping_total + $shipping_tax;

    wc_empty_cart();
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
				<?php endif; ?>
			</p>

		<?php else : ?>

			<div id='cashier-receipt' style='height: 75vh; min-height: 250px; margin-bottom: -100px; position: relative; top: -100px;'></div>
			<script>

					// We let the javascript know that it's time to setup the checkout
					// We pass along the configured settings in payload
					setTimeout(() => {
						window.postMessage({
							eventType: '::wooCommerceSetupPIQReceipt',
							payload: {
								containerId: 'cashier-receipt',
								merchantId: <?php Piq_Co_Utils::getPiqMerchantId(); ?>,
								userId: '<?php echo $piq_user_id; ?>',
								txId: '<?php echo $piq_tx_id ?>',
								environment: '<?php strval( Piq_Co_Utils::getPiqEnvironment() ); ?>',
								buttonsColor: '<?php strval( Piq_Co_Utils::getPiqButtonsColor() ); ?>',
								locale: '<?php strval( Piq_Co_Utils::getSelectedLocale() ); ?>',
								orderItems: '<?php echo $order_data; ?>',
								freightFee: '<?php echo $order_freight_fee; ?>'
							}
						}, '*')
					}, 250)


			</script>


			<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

				<li class="woocommerce-order-overview__order order">
					<?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<li class="woocommerce-order-overview__date date">
					<?php esc_html_e( 'Date:', 'woocommerce' ); ?>
					<strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
					<li class="woocommerce-order-overview__email email">
						<?php esc_html_e( 'Email:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>
				<?php endif; ?>

				<li class="woocommerce-order-overview__total total">
					<?php esc_html_e( 'Total:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( $order->get_payment_method_title() ) : ?>
					<li class="woocommerce-order-overview__payment-method method">
						<?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
						<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
					</li>
				<?php endif; ?>

			</ul>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

	<?php else : ?>

		<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

	<?php endif; ?>

</div>
