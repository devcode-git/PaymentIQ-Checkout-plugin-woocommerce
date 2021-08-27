=== PaymentIQ Checkout ===
Contributors: Devcode/PaymentIQ/Bambora
Tags: woocommerce, woo commerce, payment, payment gateway, gateway, paymentiq, bambora, checkout, integration, woocommerce bambora, woocommerce paymentiq checkout, psp
Requires at least: 3.0.0
Tested up to: 5.7.1
Stable tag: 1.0.6
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Sourcecode: https://github.com/devcode-git/PaymentIQ-Checkout-Woocommerce
WC requires at least: 4.0
WC tested up to: 5.1.0

Integrates PaymentIQ Checkout into your WooCommerce installation - inline

== Description ==
With PaymentIQ Checkout for WooCommerce, you get an inline checkout in your web shop. Secure payments availalbe through PaymentIQ via hundreds of payment methods.

= Features =
* Easy end-user information fetching/form & quick login for returning customers
* Receive payments securely through the inline PaymentIQ Checkout
* Get an overview over the status for your payments directly from your WooCommerce order page.
* Capture your payments directly from your WooCommerce order page.
* Refund your payments directly from your WooCommerce order page.
* Void your payments directly from your WooCommerce order page.
* Supports WooCommerce 3.0 and up.

== Installation ==
1. Go to your WordPress administration page and log in. Example url: https://www.yourshop.com/wp-admin

2. In the left menu click **Plugins** -> **Add new**.

3. Click the **Upload plugin** button to open the **Add Plugins** dialog.

4. Click **Choose File** and browse to the folder where you saved the file from step 1. Select the file and click **Open**.

5. Click **Install Now**

6. Click **Activate Plugin** 

7. Click "Settings" in the PaymentIQ Checkout plugin

8. Fill out the configuration fields (check with your support agent for details)

9. Click save changes and your are ready to start using PaymentIQ Checout

== Changelog ==

= 1.0.7 =
* Omit debuggin logs in screen from echo statements.

= 1.0.6 =
* Santander pricing widget can now be added by the plugin
* Plugin settings now include a settings panel for the Santander pricing widget
* Calculator at cart page and product page
* Bug fix for merchantId for admin API

= 1.0.5 =
* bugfixes

= 1.0.4 =
* Navigate to the Woocommerce configured order-received endpoint - i.e not hardcoded to /checkout/order-received
* Cleaned up the helper methods used for the hardcoded navigation

= 1.0.3 =
* Trigger checkout reload when cancelling a transaction to generate a new order - changed what callbacks we use to trigger this

= 1.0.2 =
* Update supported versions

= 1.0.1 =
* Bugfix to amounts used when reporting order details
* Bugfix to use total amount when making a capture (include tax, shipping etc)
* Upgraded to paymentiq-bootstrapper 1.3.5

= 1.0.0 =
* First version