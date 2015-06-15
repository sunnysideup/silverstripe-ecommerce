jQuery(document).ready(
	function(){
		EcomCreditCardValidation.init();
	}
);

var EcomCreditCardValidation = {

	init: function(){
		if(jQuery(".ecommercecreditcard .message").length){
			jQuery(".creditCardField input").on(
				"keyup",
				function(){
					value = "";
					jQuery(".creditCardField input").each(
						function(i, el) {
							value += jQuery(el).val();
						}
					);
					if(EcomCreditCardValidation.validateCreditCard(value)) {
						jQuery(".ecommercecreditcard .message").hide();
						jQuery(".ecommercecreditcard ").removeClass("holder-bad");
					}
				}
			)
		}
	},

	validateCreditCard: function(value) {
		// accept only digits, dashes or spaces
		if (/[^0-9-\s]+/.test(value)) return false;

		// The Luhn Algorithm. It's so pretty.
		var nCheck = 0, nDigit = 0, bEven = false;
		value = value.replace(/\D/g, "");

		for (var n = value.length - 1; n >= 0; n--) {
			var cDigit = value.charAt(n),
					nDigit = parseInt(cDigit, 10);

			if (bEven) {
				if ((nDigit *= 2) > 9) nDigit -= 9;
			}
			nCheck += nDigit;
			bEven = !bEven;
		}
		return (nCheck % 10) == 0;
	},

	numberFilter: [0,8,9,13,16,17,18,37,38,39,40,46],

	NumbersOnly: function(e) {
		var keyCode = e.keyCode;
		return ((keyCode>=48) && (keyCode<=57)) || EcomCreditCardValidation.numberFilter.indexOf(keyCode);
	},

	PreventEnter: function (e) {
		var keyCode = e.keyCode;
		return e.keyCode != 13;
	},

	AutoTab: function (current,to){
		if (current.getAttribute &&  current.value.length == current.getAttribute("maxlength")) {
			to.focus()
		}
	}

}
