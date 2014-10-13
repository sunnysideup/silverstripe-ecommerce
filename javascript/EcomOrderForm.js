/**
 *@author Nicolaas [at] sunnysideup.co.nz
 * adds JS functionality to the OrderForm
 *
 * Adds the following function to the OrderForm (final form in Checkout):
 * - T&C ticked CHECK
 * - T&C link target is another window / tab
 * - disallow clicking twice.
 * -
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

	orderFormSelector: "#OrderForm_OrderForm",

	submitButtonSelector: ".Actions input",

	termsAndConditionsCheckBoxSelector: "#ReadTermsAndConditions input",

	termsAndConditionsLinkSelector: "#ReadTermsAndConditions a",

	TermsAndConditionsMessage: 'You must agree with the terms and conditions to proceed.',
		set_TermsAndConditionsMessage: function(s) {EcomOrderForm.TermsAndConditionsMessage = s;},

	processingMessage: "processing ...",
		set_processingMessage: function(s){EcomOrderForm.processingMessage = s;},

	clicked: false,

	init: function() {
		jQuery(document).on(
			"click",
			EcomOrderForm.submitButtonSelector,
			function(e) {
				if(!EcomOrderForm.TandCcheck()) {
					e.preventDefault();
				}
				if(EcomOrderForm.clicked) {
					e.preventDefault();
				}
			}
		);
		EcomOrderForm.ajaxifyForm();
		EcomOrderForm.TandCclick();
	},

	TandCclick: function() {
		jQuery(EcomOrderForm.termsAndConditionsLinkSelector).attr("target", "termsandconditions");
	},

	TandCcheck: function() {
		if(EcomOrderForm.TermsAndConditionsMessage) {
			if(jQuery(EcomOrderForm.termsAndConditionsCheckBoxSelector).length == 1){
				if(!jQuery(EcomOrderForm.termsAndConditionsCheckBoxSelector).is(":checked")) {
					jQuery(EcomOrderForm.termsAndConditionsCheckBoxSelector).focus();
					alert(EcomOrderForm.TermsAndConditionsMessage);
					return false;
				}
			}
		}
		return true;
	},

	ajaxifyForm: function() {
		jQuery(document).on(
			"submit",
			EcomOrderForm.orderFormSelector,
			function(e) {
				EcomOrderForm.clicked = true;
				setTimeout(
					function() {
						jQuery(EcomOrderForm.submitButtonSelector).parent().addClass(EcomOrderForm.loadingClass).text(EcomOrderForm.processingMessage);
						jQuery(EcomOrderForm.submitButtonSelector).hide();
					},
					100
				);
			}
		);
	}

}
