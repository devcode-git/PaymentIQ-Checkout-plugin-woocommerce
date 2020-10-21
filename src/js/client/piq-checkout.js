import { helloWorld } from './../utils'
import _PaymentIQCashier from 'paymentiq-cashier-bootstrapper'

window.addEventListener('load', function () {
  helloWorld()
});

console.log('Register event listener')
window.addEventListener('message', function (e) {
  if (e.data && e.data.eventType) {
    const { eventType, payload } = e.data
    switch (eventType) {
      case '::wooCommerceSetupPIQCheckout':
        return setupCheckout(payload)
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

  const orderId = appConfig.attributes.orderId
  const orderKey = appConfig.orderKey // need this to deal with the redirect to thank-you page
  delete appConfig.orderKey
  
  let orderItems = appConfig.orderItems
  orderItems = JSON.parse(orderItems) // for some reason delete payload.orderItems fails otherwise in safari
  delete appConfig.orderItems
  
  const checkDeviceId = appConfig.checkUserDevice
  delete appConfig.checkUserDevice
  
  const country = appConfig.country
  delete appConfig.country
  
  const didClientId = appConfig.didClientId
  delete appConfig.didClientId

  const lookupConfig = {
    didClientId,
    country: country,
    identifyFields: 'zip,email',
    environment: appConfig.environment.toString(),
    checkUserDevice: checkDeviceId
  }
  console.log(lookupConfig)
  const config = {
    environment: appConfig.environment.toString(),
    "showAccounts": "inline",
    "globalSubmit": true,
    "showListHeaders": true,
    "mode": "ecommerce",
    "font": 'custom,santander,santander',
    "showReceipt": false, // we redirect to order-received page right away instead
    "fetchConfig": true,
    "containerHeight": 'auto',
    "containerMinHeight": '600px',
    lookupConfig: {
      ...lookupConfig
    },
    ...appConfig
  }

  renderCheckout(config, orderItems, orderKey, orderId)
}

function renderCheckout (config, orderItems, orderKey, orderId) {
  if (!_PaymentIQCashier) {
    setTimeout(function () {
      renderCheckout(config, orderItems, orderKey, orderId)
    }, 100)
  } else {
    new _PaymentIQCashier('#piq-checkout', config, (api) => {
      api.on({
        lookupInitLoad: () => {
          console.log('lookup loaded')
        },
        cashierInitLoad: () => {
          api.set({
            order: {
              orderItems: orderItems
            }
          })
          document.getElementById('lookupIframe').scrollIntoView()
        },
        success: data => notifyOrderStatus('success', orderId, orderKey, data),
        failure: data => notifyOrderStatus('failure', orderId, orderKey, data),
        pending: data => notifyOrderStatus('pending', orderId, orderKey, data),
        newProviderWindow: data => {
          if (data.data === 'NEW_IFRAME') {
            document.getElementById('cashierIframe').scrollIntoView()
          }
        },
      });
    })
  }
}

/* We need to give back control to the script in the php-code
   We do this via a postMessage back (templates/Checkout/paymentiq-checkout.php)
*/
function notifyOrderStatus (status, orderId, orderKey, data) {
  console.log('notifyOrderStatus')
  let payload = {}
  switch (status) {
    case 'success':
      payload = {
        eventType: '::wooCommercePaymentSuccess',
        payload: {
          orderId,
          ...data
        }
      }
      window.location.href = `/checkout/order-received/${orderId}?key=${orderKey}`
      break
    case 'failure':
      payload = {
        eventType: '::wooCommercePaymentFailure',
        payload: {
          orderId,
          ...data
        }
      }
      // window.location.href = `/checkout/order-received/${orderId}?key=${orderKey}`
      break
    case 'pending':
      payload = {
        eventType: '::wooCommercePaymentPending',
        payload: {
          orderId,
          ...data
        }
      }
      window.location.href = `/checkout/order-received/${orderId}?key=${orderKey}`
      break
    default:
      return
  }
  window.postMessage(payload, '*')
}
