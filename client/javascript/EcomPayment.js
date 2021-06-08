/**
 * helps in EcommercePayment Selection
 *
 **/

if (
  (document.getElementById("OrderForm_OrderForm_PaymentMethod") !== null &&
    typeof document.getElementById("OrderForm_OrderForm_PaymentMethod") !==
      "undefined") ||
  (document.getElementById("OrderFormPayment_PaymentForm_PaymentMethod") !==
    null &&
    typeof document.getElementById(
      "OrderFormPayment_PaymentForm_PaymentMethod"
    ) !== "undefined")
) {
  (function (jQuery) {
    jQuery(document).ready(function () {
      EcomPayment.init();
    });
  })(jQuery);

  var EcomPayment = {
    paymentInputsSelectorParent: "#OrderForm_OrderForm_PaymentMethod",

    paymentInputsSelector:
      "#OrderForm_OrderForm_PaymentMethod input[type=radio], #OrderFormPayment_PaymentForm_PaymentMethod input[type=radio]",

    paymentFieldSelector: "div.paymentfields",

    paymentMethodPrefix: ".methodFields_",

    init: function () {
      EcomPayment.ecomForm = jQuery(EcomPayment.paymentInputsSelector).closest(
        "form"
      );

      if (jQuery("#OrderForm_OrderForm_PaymentMethod").length) {
        EcomPayment.paymentInputsSelectorParent =
          "#OrderForm_OrderForm_PaymentMethod";
      } else if (jQuery("#OrderFormPayment_PaymentForm_PaymentMethod").length) {
        EcomPayment.paymentInputsSelectorParent =
          "#OrderFormPayment_PaymentForm_PaymentMethod";
      }

      var paymentInputs = jQuery(EcomPayment.paymentInputsSelector);
      var methodFields = jQuery(EcomPayment.paymentFieldSelector);
      methodFields.hide();

      paymentInputs.each(function (e) {
        if (jQuery(this).attr("checked") == true) {
          jQuery(
            EcomPayment.paymentMethodPrefix + jQuery(this).attr("value")
          ).show();
        }
      });

      paymentInputs.click(function (e) {
        methodFields.hide();
        jQuery(
          EcomPayment.paymentMethodPrefix + jQuery(this).attr("value")
        ).show();
      });

      if (
        jQuery(EcomPayment.paymentInputsSelectorParent + " input:radio:checked")
          .length
      ) {
        //if an option has already been selected make sure it stays selected
        jQuery(
          EcomPayment.paymentInputsSelectorParent + " input:checked"
        ).click();
      } else {
        jQuery(EcomPayment.paymentInputsSelector).first().click();
      }

      if (jQuery(EcomPayment.paymentInputsSelector).length == 1) {
        jQuery(EcomPayment.paymentInputsSelectorParent).hide();
      }
    },
  };

  jQuery(EcomPayment.paymentFieldSelector).hide();
}
