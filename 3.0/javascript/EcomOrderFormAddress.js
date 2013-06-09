/**
 *@author Nicolaas [at] sunnysideup.co.nz
 *
 * TO DO: set up a readonly system
 *
 **/

;(function($) {
	$(document).ready(
		function() {
			EcomOrderFormAddress.init();
		}
	);
})(jQuery);



var EcomOrderFormAddress = {

	postalCodeLink: ".postalCodeLink",

	postalCodeLinkTarget: "_postalcode",

	formSelector: "#OrderFormAddress_OrderFormAddress",

	setReadOnly: function(fieldName) {
		jQuery("name=['"+fieldName+"']").attr("disabled", true);
		jQuery("name=['"+fieldName+"']").attr("readonly", true);
	},

	undoReadOnly: function(fieldName) {
		jQuery("name=['"+fieldName+"']").attr("disabled", false);
		jQuery("name=['"+fieldName+"']").attr("readonly", false);
	},

	init: function() {
		jQuery(this.formSelector).attr('autocomplete', 'off');
		this.postalCodeLinkSetup();
	},


	postalCodeLinkSetup: function() {
		jQuery(EcomOrderFormAddress.postalCodeLink).attr("target", EcomOrderFormAddress.postalCodeLinkTarget);
	}
}
