window.addEventListener('load', function () {
  'use strict';
  console.log('ADMIN SCRIPT')
  registerManualCaptureHandler()
});

/* Via init_hooks we registered a manual capture button in the action panel for orders (provided its a transaction that has only been authorized)
   Here we hook up to the click event - fetch the orderId and the start the capture flow
*/
function registerManualCaptureHandler() {
  const $ = jQuery
  console.log('REGISTER CAPTURE HANDLER')
  $('.inside').on('click', '#paymentiq-checkout-manual-capture', function (e) {

    function justNumbers(string) {
      var numsStr = string.replace(/[^0-9.]/g,'');
      return parseInt(numsStr).toFixed(2);
    }

    const orderTotalAmounts = $('.wc-order-totals .total .woocommerce-Price-amount')
    const maxAmount = justNumbers(orderTotalAmounts[orderTotalAmounts.length - 1].innerText) // currency & amount (kr59 for example)
    let piqCapturedAmount = e.target.getAttribute("data-piq_captured_amount");
    if (!piqCapturedAmount)  {
      piqCapturedAmount = '0.00'
    }
    constructCustomCaptureBox($, maxAmount, piqCapturedAmount)
    
    // Since we're already in an order detail view - the order_id is present on the url as ?post=${order_id}
    // redirect page with a get that we catch in the main class in paymentiq_checkout_actions
    // We create our custom container that shows an input to set the capture amount + a capture via PIQ button
    // When it's clicked (and a valid amount in entered), we'll redirect the page and catch the redirect
  })
}

function constructCustomCaptureBox ($, maxAmount, piqCapturedAmount) {
  toggleShowOrderButtons($)
  const orderActionsBar = $('.wc-order-bulk-actions');
  const parentElement = orderActionsBar[0].parentElement

  maxAmount = (Number(maxAmount) - Number(piqCapturedAmount))

  const markdown = `
    <script>
      function handleCaptureAmountChange () {
        var value = document.getElementById('capture_amount').value
        document.getElementById('capture-amount').innerHTML = value
        document.getElementById('capture-amount').value = value

        if (value > ${maxAmount}) {
          document.getElementById('piq-capture-button').disabled = true;
        } else {
          document.getElementById('piq-capture-button').disabled = false;
        }
      }

      // If amount is set to 35, always return amount as 35.00
      // If passed in as 35.55, return as 35.55
      function addZeroes(num) {
        const dec = num.split('.')[1]
        const len = dec && dec.length > 2 ? dec.length : 2
        return Number(num).toFixed(len)
      }

      function handlePiqCaptureClick () {
        var captureAmount = document.getElementById('capture-amount').innerText
        captureAmount = addZeroes(captureAmount)
        var params = "&paymentiq_checkout_action=capture&amount=" + captureAmount;
        var url = window.location.href + params;
        window.location.href = url;
      }
  </script>

  
  <div class="wc-order-data-row wc-order-capture-items wc-order-data-row-toggle">
    <p style='float: left; text-align: left; width: 500px; max-width: 50%;'>
      <b>Please note!</b>
      <br/>
      If the order was payed for using Santander invoice/pay-later, <b>only one</b> capture can be made in total.
      Partial capture is supported.
    </p>

    <table class="wc-order-totals">
      <tr>
        <td class="label">Amount already captured:</td>
        <td class="total">-<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">kr</span>${piqCapturedAmount}</span></td>
      </tr>
      <tr>
        <td class="label">Total available to capture:</td>
        <td class="total"><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">kr</span>${maxAmount}</span></td>
      </tr>
      <tr>
        <td class="label">
          <label for="refund_amount">
            <span class="woocommerce-help-tip"></span>					Capture amount:
          </label>
        </td>
        <td class="total">
          <input
            onkeyup='handleCaptureAmountChange()'
            type="number"
            id="capture_amount"
            value="${maxAmount}"
            min="1"
            max="${maxAmount}"
            name="capture_amount"
            class="wc_input_price">

          <div class="clear"></div>
        </td>
      </tr>
    </tbody></table>
    <div class="clear"></div>
    <div class="capture-actions">
      <button onclick="handlePiqCaptureClick()" id='piq-capture-button' type="button" class="button button-primary">
        Capture
        <span class="wc-order-refund-amount">
          <span class="woocommerce-Price-amount amount">
            <span class="woocommerce-Price-currencySymbol">
              kr
            </span>
            <span id='capture-amount'>${maxAmount}</span>
          </span>
        </span> 
        via PaymentIQ Checkout
      </button>
      
      <button type="button" class="button cancel-action">Cancel</button>
      <div class="clear"></div>
    </div>
  </div>`

  $(parentElement).append(markdown)
}

function toggleShowOrderButtons ($) {
  $('.wc-order-bulk-actions').toggle()
}