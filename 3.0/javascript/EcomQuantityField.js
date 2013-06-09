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

	mainSelector: ".ecomquantityfield",

	quantityFieldSelector: "input.ajaxQuantityField",

	removeSelector: " a.removeOneLink",

	addSelector: " a.addOneLink",

	completedClass: "ajaxCompleted",

	URLSegmentHiddenFieldSelectorAppendix: "_SetQuantityLink",

	updateFX: [],

	lastValue: [],

	//todo: auto-re-attach
	init: function () {
		jQuery(EcomQuantityField.delegateRootSelector).find(EcomQuantityField.mainSelector).each(
			function(i, el) {
				if(!jQuery(el).hasClass(EcomQuantityField.completedClass)) {
					if(EcomQuantityField.hidePlusAndMinues) {
						jQuery(el).find(EcomQuantityField.removeSelector).hide();
					}
					else {
						jQuery(el).delegate(
							EcomQuantityField.removeSelector,
							"click",
							function(e) {
								EcomQuantityField.updateFX[jQuery(this).attr("name")] = null;
								e.preventDefault();
								var inputField = jQuery(this).siblings(EcomQuantityField.quantityFieldSelector);
								jQuery(inputField).val(parseFloat(jQuery(inputField).val())-1).change();
								return false;
							}
						);
					}
					if(EcomQuantityField.hidePlusAndMinues) {
						jQuery(el).find(EcomQuantityField.addSelector).hide();
					}
					else {
						jQuery(el).delegate(
							EcomQuantityField.addSelector,
							"click",
							function(e) {
								e.preventDefault();
								EcomQuantityField.updateFX[jQuery(this).attr("name")] = null;
								var inputField = jQuery(this).siblings(EcomQuantityField.quantityFieldSelector);
								jQuery(inputField).val(parseFloat(jQuery(inputField).val())+1).change();
								return false;
							}
						);
					}
					jQuery(el).delegate(
						EcomQuantityField.quantityFieldSelector,
						"focus",
						function(){
							EcomQuantityField.lastValue[jQuery(this).attr("name")] = jQuery(this).val();
						}
					);
					jQuery(el).delegate(
						EcomQuantityField.quantityFieldSelector,
						"keydown",
						function(){
							var el = this;
							EcomQuantityField.updateFX[jQuery(this).attr("name")] = window.setTimeout(
								function(){
									if(EcomQuantityField.lastValue[jQuery(el).attr("name")] != jQuery(el).val()) {
										jQuery(el).change();
									}
								},
								1000
							);
						}
					);
					jQuery(el).delegate(
						EcomQuantityField.quantityFieldSelector,
						"change",
						function() {
							EcomQuantityField.updateFX[jQuery(this).attr("name")] = null;
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
								if(EcomQuantityField.lastValue[jQuery(this).attr("name")] != jQuery(this).val()) {
									EcomQuantityField.lastValue[jQuery(this).attr("name")] = jQuery(this).val();
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
						}
					);
					jQuery(el).delegate(
						EcomQuantityField.quantityFieldSelector,
						"blur",
						function() {
							EcomQuantityField.updateFX[jQuery(this).attr("name")] = null;
						}
					);
					jQuery(el).addClass(EcomQuantityField.completedClass);
					jQuery(el).find(EcomQuantityField.quantityFieldSelector).removeAttr("disabled");
				}
			}
		);
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
