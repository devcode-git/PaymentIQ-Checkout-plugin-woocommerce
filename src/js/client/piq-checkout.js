import _PaymentIQCashier from 'paymentiq-cashier-bootstrapper'

window.addEventListener('message', function (e) {
  if (e.data && e.data.eventType) {
    const { eventType, payload } = e.data
    switch (eventType) {
      case '::wooCommerceSetupPIQCheckout':
        return setupCheckout(payload)
      case '::wooCommerceSetupPIQReceipt':
        return setupReceipt(payload)
      default: 
        return
    }
  }
})

function setupCheckout (payload) {
  // allow deletion of object properties - strict mode blocks this in some browsers, so we create a new object
  let appConfig = {
    ...payload
  }

  const orderReceivedPath = appConfig.orderReceivedPath
  delete appConfig.orderReceivedPath

  const orderId = appConfig.attributes.orderId
  
  let orderItems = appConfig.orderItems
  orderItems = JSON.parse(orderItems) // for some reason delete payload.orderItems fails otherwise in safari
  delete appConfig.orderItems
  
  const freightFee = appConfig.freightFee
  delete appConfig.freightFee
  
  const checkDeviceId = appConfig.checkUserDevice
  delete appConfig.checkUserDevice
  
  const country = appConfig.country
  delete appConfig.country
  
  const didClientId = appConfig.didClientId
  delete appConfig.didClientId
  
  const buttonsColor = appConfig.buttonsColor
  delete appConfig.buttonsColor

  const lookupConfig = {
    didClientId,
    country: country,
    identifyFields: 'zip,email',
    environment: appConfig.environment,
    checkUserDevice: checkDeviceId,
    owner: 'Santander'
  }
  
  const config = {
    userId: 'PayTestSE',
    environment: appConfig.environment,
    "showAccounts": "inline",
    "globalSubmit": true,
    "showListHeaders": true,
    "mode": "ecommerce",
    "font": 'custom,santander,santander',
    "loaderType": "content",
    "showReceipt": false, // we redirect to order-received page right away instead
    "fetchConfig": true,
    "containerHeight": 'auto',
    "containerMinHeight": '600px',
    // lookupConfig: {
    //   ...lookupConfig
    // },
    theme: {
      buttons: {
        color: buttonsColor
      }
    },
    ...appConfig
  }

  renderCheckout({ config, orderItems, orderReceivedPath, orderId, freightFee })
}

function renderCheckout ({ config, orderItems, orderReceivedPath, orderId, freightFee }) {
  if (!_PaymentIQCashier) {
    setTimeout(function () {
      renderCheckout({ config, orderItems, orderReceivedPath, orderId, freightFee })
    }, 100)
  } else {
    // We need to keep track when the user cancels a provider flow. When that happens, we're gonna end up with the same
    // orderId but we're gonna have a transaction in PIQ already tied to that orderId. Initiating a second transaction
    // will cause them to get mixed up. So, when that happens, reload the page to trigger a new orderId.
    let providerWasOpened = false

    new _PaymentIQCashier('#piq-checkout', config, (api) => {
      api.on({
        cashierInitLoad: () => {
          api.set({
            order: {
              orderItems: orderItems,
              freightFee
            }
          })
          document.getElementById('lookupIframe').scrollIntoView()
        },
        success: data => {
          notifyOrderStatus('success', orderReceivedPath, orderId, data)
        },
        failure: data => notifyOrderStatus('failure', orderReceivedPath, orderId, data),
        pending: data => notifyOrderStatus('pending', orderReceivedPath, orderId, data),
        transactionInit: data => {
          providerWasOpened = true
        },
        validationFailed: data => {
          providerWasOpened = false
        },
        newProviderWindow: data => {
          providerWasOpened = true
          if (data.data === 'NEW_IFRAME') {
            document.getElementById('cashierIframe').scrollIntoView()
          }
        },
        navigate: data => {
          if (providerWasOpened && data.data.path === '/') {
            // user clicked back during the provider flow, meaning we end up with an orderId + an initiated transaction in PIQ
            // In this case, we must generate a new orderId which we can do by reloading the page.
            location.reload()
          }
        }
      });
    })
  }
}

/* We need to give back control to the script in the php-code
   We do this via a postMessage back (templates/Checkout/paymentiq-checkout.php)
*/
function notifyOrderStatus (status, orderReceivedPath, orderId, data) {
  function getParameterByName(name) {
    const url = document.querySelector('#cashierIframe').src

    name = name.replace(/[\[\]]/g, '\\$&');
    const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)')
    const results = regex.exec(url);
    
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
  }
  
  const userId = getParameterByName('userId')

  let payload = {}
  switch (status) {
    case 'success':

      payload = {
        eventType: '::wooCommercePaymentSuccess',
        payload: {
          orderId,
          userId,
          txId: data.data.payload.txRefId,
          ...data
        }
      }
      // Navigate to order-received page
      window.location.href = orderReceivedPath
      break
    case 'failure':
      payload = {
        eventType: '::wooCommercePaymentFailure',
        payload: {
          orderId,
          ...data
        }
      }
      break
    case 'pending':
      payload = {
        eventType: '::wooCommercePaymentPending',
        payload: {
          orderId,
          ...data
        }
      }
      // Navigate to order-received page
      window.location.href = orderReceivedPath
      break
    default:
      return
  }
  window.postMessage(payload, '*')
}

/* SETUP RECEIPT */

function setupReceipt (payload) {
  const config = {
    // userId: payload.userId,
    merchantId: payload.merchantId,
    userId: payload.userId,
    environment: 'test',
    showReceipt: 'only',
    containerHeight: '100%',
    containerWidth: '100%',
    txRefId: payload.txId,
    receiptExcludeKeys: [
      'receiptDepositTax',
      'receiptDepositTxAmount',
      'receiptDepositPspRefId',
      'receiptDepositTax',
      'receiptDepositFee',
      'Insättningsavgift'
    ],
    theme: {
      buttons: {
        color: payload.buttonsColor
      }
    }
  }

  renderReceipt({
    config,
    containerId: payload.containerId,
    orderItems: payload.orderItems,
    freightFee: payload.freightFee,
  })
}

function renderReceipt ({ config, containerId, orderItems, freightFee }) {
  if (!_PaymentIQCashier) {
    setTimeout(function () {
      renderReceipt({ config, containerId })
    }, 100)
  } else {
    new _PaymentIQCashier(`#${containerId}`, config, (api) => {
      api.on({
        cashierInitLoad: () => {
          api.set({
            order: {
              orderItems: JSON.parse(orderItems),
              freightFee
            }
          })
        }
      })

      api.css(`
        .details-container {
          width: 600px !important;
        }

        .footer {
          display: none;
        }
      `)


    })
  }
}
