/**
 *@author Nicolaas [at] sunnysideup.co.nz
 *
 * TO DO: set up a readonly system
 *
 **/

;(function($) {
	$(document).ready(
		function() {
			EcomPasswordField.init();
		}
	);
})(jQuery);



var EcomPasswordField = {

	chars : "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz",

	passwordFieldInputSelectors: "#Password, #PasswordDoubleCheck",

	choosePasswordLinkSelector: ".choosePassword, .updatePasswordLink",

	stringLength : 7,

	//toggles password selection and enters random password so that users still end up with a password
	//even if they do not choose one.
	init: function() {
		if(jQuery(EcomPasswordField.passwordFieldInputSelectors).length) {
			jQuery(EcomPasswordField.choosePasswordLinkSelector).click(
				function() {
					jQuery(EcomPasswordField.passwordFieldInputSelectors).slideToggle();
					if(jQuery(EcomPasswordField.passwordFieldInputSelectors).is(':visible')) {
						var newPassword = '';
					}
					else{
						var newPassword = EcomPasswordField.passwordGenerator();
					}
					jQuery(EcomPasswordField.passwordFieldInputSelectors).val(newPassword);
					return false;
				}
			);
			jQuery(EcomPasswordField.choosePasswordLinkSelector).click();
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
	}


}
