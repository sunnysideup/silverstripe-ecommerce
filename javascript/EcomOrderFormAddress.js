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

	chars : "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz",

	stringLength : 8,

	passwordFieldInputSelectors: "#PasswordGroup input",

	choosePasswordLinkSelector: ".choosePassword",

	passwordGroupHolderSelector: "#PasswordGroup",

	postalCodeLink: ".postalCodeLink",

	postalCodeLinkTarget: "_postalcode",

	setReadOnly: function(fieldName) {
		jQuery("name=['"+fieldName+"']").attr("disabled", true);
		jQuery("name=['"+fieldName+"']").attr("readonly", true);
	},

	undoReadOnly: function(fieldName) {
		jQuery("name=['"+fieldName+"']").attr("disabled", false);
		jQuery("name=['"+fieldName+"']").attr("readonly", false);
	},

	init: function() {
		this.passwordInitalisation();
		this.postalCodeLinkSetup();
	},

	//toggles password selection and enters random password so that users still end up with a password
	//even if they do not choose one.
	passwordInitalisation: function() {
		if(jQuery(EcomOrderFormAddress.passwordFieldInputSelectors).length) {
			jQuery(EcomOrderFormAddress.choosePasswordLinkSelector).click(
				function() {
					jQuery(EcomOrderFormAddress.passwordGroupHolderSelector).toggle();
					if(jQuery(EcomOrderFormAddress.passwordFieldInputSelectors).is(':visible')) {
						var newPassword = '';
					}
					else{
						var newPassword = EcomOrderFormAddress.passwordGenerator();
					}
					jQuery(EcomOrderFormAddress.passwordFieldInputSelectors).val(newPassword);
					return false;
				}
			);
			jQuery(EcomOrderFormAddress.choosePasswordLinkSelector).click();
		}
	},

	//generates random password
	passwordGenerator: function() {
		var randomstring = '';
		for (var i=0; i < this.stringLength; i++) {
			var rnum = Math.floor(Math.random() * this.chars.length);
			randomstring += this.chars.substring(rnum,rnum+1);
		}
		return randomstring;
	},

	postalCodeLinkSetup: function() {
		jQuery(EcomOrderFormAddress.postalCodeLink).attr("target", EcomOrderFormAddress.postalCodeLinkTarget);
	}
}
