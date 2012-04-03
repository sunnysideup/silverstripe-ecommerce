/**
  *@description: update cart using AJAX
  */

(function($){
	$(document).ready(
		function() {
			EcomQuantityField.init();
		}
	);

})(jQuery);

EcomQuantityField = {

	quantityFieldSelector: ".ecomquantityfield input.ajaxQuantityField",

	removeSelector: ".ecomquantityfield a.removeOneLink",

	addSelector: ".ecomquantityfield a.addOneLink",

	URLSegmentHiddenFieldSelectorAppendix: "_SetQuantityLink",

	//todo: auto-re-attach
	init: function () {
		jQuery(EcomQuantityField.removeSelector).live(
			"click",
			function(e) {
				e.preventDefault();
				var inputField = jQuery(this).siblings(EcomQuantityField.quantityFieldSelector);
				jQuery(inputField).val(parseFloat(jQuery(inputField).val())-1).change();
				return false;
			}
		);
		jQuery(EcomQuantityField.addSelector).live(
			"click",
			function(e) {
				e.preventDefault();
				var inputField = jQuery(this).siblings(EcomQuantityField.quantityFieldSelector);
				jQuery(inputField).val(parseFloat(jQuery(inputField).val())+1).change();
				return false;
			}
		);
		jQuery(EcomQuantityField.quantityFieldSelector).live(
			"change",
			function() {
				var URLSegment = EcomQuantityField.getSetQuantityURLSegment(this);
				if(URLSegment.length > 0) {
					if(! this.value) {
						this.value = 0;
					}
					else {
						this.value = this.value.replace(/[^0-9.]+/g, '');
					}
					var url = jQuery('base').attr('href') + URLSegment + '?quantity=' + this.value;
					EcomCart.getChanges(url, null);
				}
			}
		);
		jQuery(EcomQuantityField.quantityFieldSelector).removeAttr('disabled');
	},

	getSetQuantityURLSegment: function (inputField) {
		var name = jQuery(inputField).attr('name')+EcomQuantityField.URLSegmentHiddenFieldSelectorAppendix ;
		if(jQuery('[name=' + name + ']').length > 0) {
			return jQuery('[name=' + name + ']').val();
		};
		//backup!
		return jQuery(inputField).attr("rel");
	},

	debug: function() {
		jQuery(EcomQuantityField.addSelector).css("border", "3px solid red");
		jQuery(EcomQuantityField.removeSelector).css("border", "3px solid red");
		jQuery(EcomQuantityField.quantityFieldSelector).css("border", "3px solid red");
	}
}
