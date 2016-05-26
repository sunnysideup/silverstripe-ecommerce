/**
 * helps in EcommercePayment Selection
 *
 **/

;
if(
    (document.getElementById("PaymentMethod") !== null && typeof document.getElementById("PaymentMethod") !== "undefined")
) {
    (function(jQuery){
        jQuery(window).load(function() {
            EcomPayment.init();
        });
    })(jQuery);



    var EcomPayment = {

        paymentInputsSelectorParent: '#PaymentMethod',

        paymentInputsSelector: '#PaymentMethod input[type=radio]',

        paymentFieldSelector: 'div.paymentfields',

        paymentMethodPrefix: '.methodFields_',

        init: function () {
            var paymentInputs = jQuery(EcomPayment.paymentInputsSelector);
            var methodFields = jQuery(EcomPayment.paymentFieldSelector);

            methodFields.hide();

            paymentInputs.each(
                function(e) {
                    if(jQuery(this).attr('checked') == true) {
                        jQuery(EcomPayment.paymentMethodPrefix + jQuery(this).attr('value')).show();
                    }
                }
            );

            paymentInputs.click(
                function(e) {
                    methodFields.hide();
                    jQuery(EcomPayment.paymentMethodPrefix + jQuery(this).attr('value')).show();
                }
            );

            jQuery(EcomPayment.paymentInputsSelector).first().click();
            if(jQuery(EcomPayment.paymentInputsSelector).length == 1) {
                jQuery(EcomPayment.paymentInputsSelectorParent).hide();
            }

        }


    }

    jQuery(EcomPayment.paymentFieldSelector).hide();

}
