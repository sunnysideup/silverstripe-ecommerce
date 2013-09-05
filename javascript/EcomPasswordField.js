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

	passwordFieldInputSelectors: "#PasswordCheck1, #PasswordCheck2",

	choosePasswordLinkSelector: ".choosePassword, .updatePasswordLink",

	stringLength : 7,

	//toggles password selection and enters random password so that users still end up with a password
	//even if they do not choose one.
	init: function() {
		var yesLabel = jQuery(EcomPasswordField.choosePasswordLinkSelector).text();
		if(!jQuery(EcomPasswordField.choosePasswordLinkSelector).attr("datayes")) {
			jQuery(EcomPasswordField.choosePasswordLinkSelector).attr("datayes", yesLabel);
		}
		if(jQuery(EcomPasswordField.passwordFieldInputSelectors).length) {
			jQuery(EcomPasswordField.choosePasswordLinkSelector).click(
				function() {
					jQuery(EcomPasswordField.passwordFieldInputSelectors).slideToggle(
						function(){
							if(jQuery(EcomPasswordField.passwordFieldInputSelectors).is(':visible')) {
								var newPassword = '';
								var newLabel = jQuery(EcomPasswordField.choosePasswordLinkSelector).attr("datano");
							}
							else{
								var newPassword = EcomPasswordField.passwordGenerator();
								var newLabel = jQuery(EcomPasswordField.choosePasswordLinkSelector).attr("datayes");
							}
							jQuery(EcomPasswordField.choosePasswordLinkSelector).text(newLabel);
							jQuery(EcomPasswordField.passwordFieldInputSelectors).each(
								function(i, el) {
									jQuery(el).find("input").val(newPassword);
								}
							);
						}
					);
					return false;
				}
			);
			jQuery(EcomPasswordField.choosePasswordLinkSelector).click();
		}
	},

	//generates random password
	passwordGenerator: function() {
		return '';
		var randomstring = '';
		for (var i=0; i < this.stringLength; i++) {
			var rnum = Math.floor(Math.random() * this.chars.length);
			randomstring += this.chars.substring(rnum,rnum+1);
		}
		return randomstring;
	}


}
