# PaymentIQ-Checkout-Woocommerce

Wordpress/Woocommerce extension that bootstraps PaymentIQ Checkout to your webshop & integrates PaymentIQ to WooCommerce's admin features.

## Supports

* Refund
* Capture
* Void

## Development

Requires a wordpress environment with Woocommerce installed.

* Setup via docker here: https://github.com/Yoast/plugin-development-docker Requires docker 2.4 (2.5 was not working in nov 2020)
* git clone this repo into plugin-development-docker/plugins/paymentiq-checkout
* npm start
* start docker containers

Php-files require no compilation. Edit the files, then just reload to get your changes.

Javascript files are transpiled and minified using webpack and placed in `/assets`.

During development, `npm start` or `npm run build` will compile it for you.

## Deploy
Deployment is done via Wordpress plugin's offical svn - you need a svn client

Url: https://plugins.svn.wordpress.org/paymentiq-checkout__;!!KtIQeTNdMUQ6!sLkjoBGSKuzU42dsv-ZEpYionE-rl-g6wvKG3UMNoL6JCoq3fc4EMgKnKeRZEm4ROtQ$

You need to be a registered committer or use the paymentiq user: **devopspaymentiq**

1) Update the changelog in [readme.txt](https://github.com/devcode-git/PaymentIQ-Checkout-Plugin-Woocommerce/blob/main/readme.txt)
2) Copy your updated code into /trunk - everyting except node_modules
3) Create a new folder under /tags for the version
4) Copy all files and folders except **/node_modules** & **/src**
5) Commit your changes to publish the new version

## Demo

http://woocommerce-demo.paymentiq.io/
