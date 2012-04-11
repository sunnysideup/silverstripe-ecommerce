/**
 *@author Nicolaas [at] sunnysideup.co.nz
 * adds JS functionality to the OrderForm
 *
 **/

;(function($) {
	$(document).ready(
		function() {
			EcomOrderForm.init();
		}
	);
})(jQuery);


var EcomOrderForm = {

	loadingClass: "loading",

	submitButtonSelector: ".Actions input",

	termsAndConditionsCheckBoxSelector: "#ReadTermsAndConditions input",

	tAndcMessage: 'You must agree with the terms and conditions to proceed.',

	init: function() {
		jQuery(EcomOrderForm.submitButtonSelector).click(
			function(e) {
				if(!EcomOrderForm.TandCcheck()) {
					e.preventDefault();
				}
			}
		);
		EcomOrderForm.ajaxifyForm();
	},

	TandCcheck: function() {
		if(jQuery(EcomOrderForm.termsAndConditionsCheckBoxSelector).length == 1){
			if(!jQuery(EcomOrderForm.termsAndConditionsCheckBoxSelector).is(":checked")) {
				jQuery(EcomOrderForm.termsAndConditionsCheckBoxSelector).focus();
				alert(EcomOrderForm.tAndcMessage);
				return false;
			}
		}
		return true;
	},

	ajaxifyForm: function() {
		jQuery("form").submit(
			function(e) {
				setTimeout(
					function() {
						jQuery(EcomOrderForm.submitButtonSelector).parent().addClass(EcomOrderForm.loadingClass).text("loading ...");
						jQuery(EcomOrderForm.submitButtonSelector).hide();
					},
					100
				);
			}
		);
	}

}
