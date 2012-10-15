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

	//todo: make more specific! some selector that holds true for all cart holders.
	hidePlusAndMinues: true,

	delegateRootSelector: 'body',
		set_delegateRootSelector: function(s) {this.delegateRootSelector = s;},
		unset_delegateRootSelector: function() {this.delegateRootSelector = 'body';},

	quantityFieldSelector: ".ecomquantityfield input.ajaxQuantityField",

	removeSelector: ".ecomquantityfield a.removeOneLink",

	addSelector: ".ecomquantityfield a.addOneLink",

	URLSegmentHiddenFieldSelectorAppendix: "_SetQuantityLink",

	//todo: auto-re-attach
	init: function () {
		if(EcomQuantityField.hidePlusAndMinues) {
			jQuery(EcomQuantityField.delegateRootSelector).find(EcomQuantityField.removeSelector).hide();
		}
		else {
			jQuery(EcomQuantityField.delegateRootSelector).delegate(
				EcomQuantityField.removeSelector,
				"click",
				function(e) {
					e.preventDefault();
					var inputField = jQuery(this).siblings(EcomQuantityField.quantityFieldSelector);
					jQuery(inputField).val(parseFloat(jQuery(inputField).val())-1).change();
					return false;
				}
			);
		}
		if(EcomQuantityField.hidePlusAndMinues) {
			jQuery(EcomQuantityField.delegateRootSelector).find(EcomQuantityField.addSelector).hide();
		}
		else {
			jQuery(EcomQuantityField.delegateRootSelector).delegate(
				EcomQuantityField.addSelector,
				"click",
				function(e) {
					e.preventDefault();
					var inputField = jQuery(this).siblings(EcomQuantityField.quantityFieldSelector);
					jQuery(inputField).val(parseFloat(jQuery(inputField).val())+1).change();
					return false;
				}
			);
		}
		jQuery(EcomQuantityField.delegateRootSelector).delegate(
			EcomQuantityField.quantityFieldSelector,
			"change",
			function() {
				var URLSegment = EcomQuantityField.getSetQuantityURLSegment(this);
				if(URLSegment.length > 0) {
					this.value = this.value.replace(/[^0-9.]+/g, '');
					if(this.value == 0 || !this.value) {
						this.value = 1;
					}
					if(this.value < 2) {
						jQuery(this).siblings(EcomQuantityField.removeSelector).css("visibility", "hidden");
					}
					else {
						jQuery(this).siblings(EcomQuantityField.removeSelector).css("visibility", "visible");
					}
					if(URLSegment.indexOf("?") == -1) {
						URLSegment = URLSegment + "?";
					}
					else {
						URLSegment = URLSegment + "&";
					}
					var url = jQuery('base').attr('href') + URLSegment + 'quantity=' + this.value;
					url = url.replace("&amp;", "&");
					EcomCart.getChanges(url, null, this);
				}
			}
		);
		jQuery(EcomQuantityField.delegateRootSelector).find(EcomQuantityField.quantityFieldSelector).removeAttr("disabled");
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
