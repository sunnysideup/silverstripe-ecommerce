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

	termsAndConditionsLinkSelector: "#ReadTermsAndConditions a",

	TermsAndConditionsMessage: 'You must agree with the terms and conditions to proceed.',
		set_TermsAndConditionsMessage: function(s) {EcomOrderForm.TermsAndConditionsMessage = s;},

	processingMessage: "processing ...",
		set_processingMessage: function(s){EcomOrderForm.processingMessage = s;},

	init: function() {
		jQuery(EcomOrderForm.submitButtonSelector).click(
			function(e) {
				if(!EcomOrderForm.TandCcheck()) {
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
		jQuery("form").submit(
			function(e) {
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
