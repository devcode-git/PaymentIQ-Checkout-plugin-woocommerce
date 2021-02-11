/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/js/admin/piq-checkout-admin.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./src/js/admin/piq-checkout-admin.js":
/*!********************************************!*\
  !*** ./src/js/admin/piq-checkout-admin.js ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("window.addEventListener('load', function () {\n  'use strict';\n\n  console.log('ADMIN SCRIPT');\n  registerManualCaptureHandler();\n});\n/* Via init_hooks we registered a manual capture button in the action panel for orders (provided its a transaction that has only been authorized)\n   Here we hook up to the click event - fetch the orderId and the start the capture flow\n*/\n\nfunction registerManualCaptureHandler() {\n  var $ = jQuery;\n  console.log('REGISTER CAPTURE HANDLER');\n  $('.inside').on('click', '#paymentiq-checkout-manual-capture', function (e) {\n    function justNumbers(string) {\n      var numsStr = string.replace(/[^0-9.]/g, '');\n      return parseInt(numsStr).toFixed(2);\n    }\n\n    var orderTotalAmounts = $('.wc-order-totals .total .woocommerce-Price-amount');\n    var maxAmount = justNumbers(orderTotalAmounts[orderTotalAmounts.length - 1].innerText); // currency & amount (kr59 for example)\n\n    var piqCapturedAmount = e.target.getAttribute(\"data-piq_captured_amount\");\n\n    if (!piqCapturedAmount) {\n      piqCapturedAmount = '0.00';\n    }\n\n    constructCustomCaptureBox($, maxAmount, piqCapturedAmount); // Since we're already in an order detail view - the order_id is present on the url as ?post=${order_id}\n    // redirect page with a get that we catch in the main class in paymentiq_checkout_actions\n    // We create our custom container that shows an input to set the capture amount + a capture via PIQ button\n    // When it's clicked (and a valid amount in entered), we'll redirect the page and catch the redirect\n  });\n}\n\nfunction constructCustomCaptureBox($, maxAmount, piqCapturedAmount) {\n  toggleShowOrderButtons($);\n  var orderActionsBar = $('.wc-order-bulk-actions');\n  var parentElement = orderActionsBar[0].parentElement;\n  maxAmount = (parseInt(maxAmount) - parseInt(piqCapturedAmount)).toFixed(2);\n  var markdown = \"\\n    <script>\\n      function handleCaptureAmountChange () {\\n        var value = document.getElementById('capture_amount').value\\n        document.getElementById('capture-amount').innerHTML = value\\n        document.getElementById('capture-amount').value = value\\n\\n        if (value > \".concat(maxAmount, \") {\\n          document.getElementById('piq-capture-button').disabled = true;\\n        } else {\\n          document.getElementById('piq-capture-button').disabled = false;\\n        }\\n      }\\n\\n      // If amount is set to 35, always return amount as 35.00\\n      // If passed in as 35.55, return as 35.55\\n      function addZeroes(num) {\\n        const dec = num.split('.')[1]\\n        const len = dec && dec.length > 2 ? dec.length : 2\\n        return Number(num).toFixed(len)\\n      }\\n\\n      function handlePiqCaptureClick () {\\n        var captureAmount = document.getElementById('capture-amount').innerText\\n        captureAmount = addZeroes(captureAmount)\\n        var params = \\\"&paymentiq_checkout_action=capture&amount=\\\" + captureAmount;\\n        var url = window.location.href + params;\\n        window.location.href = url;\\n      }\\n  </script>\\n\\n  \\n  <div class=\\\"wc-order-data-row wc-order-capture-items wc-order-data-row-toggle\\\">\\n    <p style='float: left; text-align: left; width: 500px; max-width: 50%;'>\\n      <b>Please note!</b>\\n      <br/>\\n      If the order was payed for using Santander invoice/pay-later, <b>only one</b> capture can be made in total.\\n      Partial capture is supported.\\n    </p>\\n\\n    <table class=\\\"wc-order-totals\\\">\\n      <tr>\\n        <td class=\\\"label\\\">Amount already captured:</td>\\n        <td class=\\\"total\\\">-<span class=\\\"woocommerce-Price-amount amount\\\"><span class=\\\"woocommerce-Price-currencySymbol\\\">kr</span>\").concat(piqCapturedAmount, \"</span></td>\\n      </tr>\\n      <tr>\\n        <td class=\\\"label\\\">Total available to capture:</td>\\n        <td class=\\\"total\\\"><span class=\\\"woocommerce-Price-amount amount\\\"><span class=\\\"woocommerce-Price-currencySymbol\\\">kr</span>\").concat(maxAmount, \"</span></td>\\n      </tr>\\n      <tr>\\n        <td class=\\\"label\\\">\\n          <label for=\\\"refund_amount\\\">\\n            <span class=\\\"woocommerce-help-tip\\\"></span>\\t\\t\\t\\t\\tCapture amount:\\n          </label>\\n        </td>\\n        <td class=\\\"total\\\">\\n          <input\\n            onkeyup='handleCaptureAmountChange()'\\n            type=\\\"number\\\"\\n            id=\\\"capture_amount\\\"\\n            value=\\\"\").concat(maxAmount, \"\\\"\\n            min=\\\"1\\\"\\n            max=\\\"\").concat(maxAmount, \"\\\"\\n            name=\\\"capture_amount\\\"\\n            class=\\\"wc_input_price\\\">\\n\\n          <div class=\\\"clear\\\"></div>\\n        </td>\\n      </tr>\\n    </tbody></table>\\n    <div class=\\\"clear\\\"></div>\\n    <div class=\\\"capture-actions\\\">\\n      <button onclick=\\\"handlePiqCaptureClick()\\\" id='piq-capture-button' type=\\\"button\\\" class=\\\"button button-primary\\\">\\n        Capture\\n        <span class=\\\"wc-order-refund-amount\\\">\\n          <span class=\\\"woocommerce-Price-amount amount\\\">\\n            <span class=\\\"woocommerce-Price-currencySymbol\\\">\\n              kr\\n            </span>\\n            <span id='capture-amount'>\").concat(maxAmount, \"</span>\\n          </span>\\n        </span> \\n        via PaymentIQ Checkout\\n      </button>\\n      \\n      <button type=\\\"button\\\" class=\\\"button cancel-action\\\">Cancel</button>\\n      <div class=\\\"clear\\\"></div>\\n    </div>\\n  </div>\");\n  $(parentElement).append(markdown);\n}\n\nfunction toggleShowOrderButtons($) {\n  $('.wc-order-bulk-actions').toggle();\n}\n\n//# sourceURL=webpack:///./src/js/admin/piq-checkout-admin.js?");

/***/ })

/******/ });