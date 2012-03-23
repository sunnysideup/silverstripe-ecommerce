/**
 *@author Nicolaas [at] sunnysideup.co.nz
 *
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
	}
}
