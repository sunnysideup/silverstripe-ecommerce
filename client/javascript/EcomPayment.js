/**
 * helps in EcommercePayment Selection
 *
 **/

if (
  document.getElementById('OrderForm_OrderForm_PaymentMethod') ||
  document.getElementById('OrderFormPayment_PaymentForm_PaymentMethod')
) {
  const EcomPayment = {
    paymentInputsSelectorParent: '#OrderForm_OrderForm_PaymentMethod',

    paymentInputsSelector:
      '#OrderForm_OrderForm_PaymentMethod input[type=radio], #OrderFormPayment_PaymentForm_PaymentMethod input[type=radio]',

    paymentFieldSelector: 'div.paymentfields',

    paymentMethodPrefix: '.methodFields_',

    init: function () {
      EcomPayment.ecomForm = window
        .jQuery(EcomPayment.paymentInputsSelector)
        .closest('form')

      if (window.jQuery('#OrderForm_OrderForm_PaymentMethod').length) {
        EcomPayment.paymentInputsSelectorParent =
          '#OrderForm_OrderForm_PaymentMethod'
      } else if (
        window.jQuery('#OrderFormPayment_PaymentForm_PaymentMethod').length
      ) {
        EcomPayment.paymentInputsSelectorParent =
          '#OrderFormPayment_PaymentForm_PaymentMethod'
      }

      const paymentInputs = window.jQuery(EcomPayment.paymentInputsSelector)
      const methodFields = window.jQuery(EcomPayment.paymentFieldSelector)
      // methodFields.hide()

      // if a payment method is already selected, show the fields for that one
      paymentInputs.each(function (e) {
        if (window.jQuery(this).attr('checked') === true) {
          methodFields.removeClass('active')
          window
            .jQuery(
              EcomPayment.paymentMethodPrefix +
                window.jQuery(this).attr('value')
            )
            .addClass('active')
        }
      })

      //
      paymentInputs.click(function (e) {
        methodFields.removeClass('active')
        window
          .jQuery(
            EcomPayment.paymentMethodPrefix + window.jQuery(this).attr('value')
          )
          .addClass('active')
      })

      jQuery(EcomPayment.paymentFieldSelector).click(function (e) {
        e.stopPropagation()
        const cls = Array.from(this.classList).find(c =>
          c.startsWith('methodFields_')
        )
        const result = cls ? cls.replace('methodFields_', '') : null
        if (result) {
          window
            .jQuery(EcomPayment.paymentInputsSelector)
            .filter(`[value="${result}"]`)
            .prop('checked', true)
            .trigger('click')
        }
      })

      window.jQuery(EcomPayment.paymentInputsSelectorParent).hide()
      if (window.jQuery(EcomPayment.paymentInputsSelector).length === 1) {
      }

      // select pre-selected or first payment method
      if (
        window.jQuery(
          EcomPayment.paymentInputsSelectorParent + ' input:radio:checked'
        ).length
      ) {
        // if an option has already been selected make sure it stays selected
        window
          .jQuery(EcomPayment.paymentInputsSelectorParent + ' input:checked')
          .trigger('click')
      } else {
        window
          .jQuery(EcomPayment.paymentInputsSelector)
          .first()
          .trigger('click')
      }
    }
  }

  // window.jQuery(EcomPayment.paymentFieldSelector).hide()

  jQuery(() => {
    EcomPayment.init()
  })
}
