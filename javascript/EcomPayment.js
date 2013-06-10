/**
 * helps in EcommercePayment Selection
 *
 **/
(function(jQuery){
	jQuery(window).load(function() {
		EcomPayment.init();
	});
})(jQuery);

var EcomPayment = {

	paymentInputsSelector: '#PaymentMethod input[type=radio]',

	paymentFieldSelector: 'div.paymentfields',

	paymentMethodPrefix: '.methodFields_',

	init: function () {
		var paymentInputs = jQuery(EcomPayment.paymentInputsSelector);
		var methodFields = jQuery(EcomPayment.paymentFieldSelector);

		methodFields.hide();

		paymentInputs.each(function(e) {
			if(jQuery(this).attr('checked') == true) {
				jQuery(EcomPayment.paymentMethodPrefix + jQuery(this).attr('value')).show();
			}
		});

		paymentInputs.click(function(e) {
			methodFields.hide();
			jQuery(EcomPayment.paymentMethodPrefix + jQuery(this).attr('value')).show();
		});

		jQuery(EcomPayment.paymentInputsSelector).first().click();

	}


}
