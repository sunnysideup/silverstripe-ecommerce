/**
 * helps in EcommercePayment Selection
 *
 **/

if (
  (
    document.getElementById('OrderForm_OrderForm_PaymentMethod') !== null &&
    typeof document.getElementById('OrderForm_OrderForm_PaymentMethod') !== 'undefined'
  ) || (
    document.getElementById('OrderFormPayment_PaymentForm_PaymentMethod') !== null &&
    typeof document.getElementById('OrderFormPayment_PaymentForm_PaymentMethod') !== 'undefined'
  )
) {
  (function (jQuery) {
    window.jQuery(document).ready(function () {
      EcomPayment.init()
    })
  })(window.jQuery)

  var EcomPayment = {
    paymentInputsSelectorParent: '#OrderForm_OrderForm_PaymentMethod',

    paymentInputsSelector:
      '#OrderForm_OrderForm_PaymentMethod input[type=radio], #OrderFormPayment_PaymentForm_PaymentMethod input[type=radio]',

    paymentFieldSelector: 'div.paymentfields',

    paymentMethodPrefix: '.methodFields_',

    init: function () {
      EcomPayment.ecomForm = window.jQuery(EcomPayment.paymentInputsSelector).closest(
        'form'
      )

      if (window.jQuery('#OrderForm_OrderForm_PaymentMethod').length) {
        EcomPayment.paymentInputsSelectorParent =
          '#OrderForm_OrderForm_PaymentMethod'
      } else if (window.jQuery('#OrderFormPayment_PaymentForm_PaymentMethod').length) {
        EcomPayment.paymentInputsSelectorParent =
          '#OrderFormPayment_PaymentForm_PaymentMethod'
      }

      var paymentInputs = window.jQuery(EcomPayment.paymentInputsSelector)
      var methodFields = window.jQuery(EcomPayment.paymentFieldSelector)
      methodFields.hide()

      paymentInputs.each(function (e) {
        if (window.jQuery(this).attr('checked') === true) {
          window.jQuery(
            EcomPayment.paymentMethodPrefix + window.jQuery(this).attr('value')
          ).show()
        }
      })

      paymentInputs.click(function (e) {
        methodFields.hide()
        window.jQuery(
          EcomPayment.paymentMethodPrefix + window.jQuery(this).attr('value')
        ).show()
      })

      if (
        window.jQuery(EcomPayment.paymentInputsSelectorParent + ' input:radio:checked')
          .length
      ) {
        // if an option has already been selected make sure it stays selected
        window.jQuery(
          EcomPayment.paymentInputsSelectorParent + ' input:checked'
        ).click()
      } else {
        window.jQuery(EcomPayment.paymentInputsSelector).first().click()
      }

      if (window.jQuery(EcomPayment.paymentInputsSelector).length === 1) {
        window.jQuery(EcomPayment.paymentInputsSelectorParent).hide()
      }
    }
  }

  window.jQuery(EcomPayment.paymentFieldSelector).hide()
}
