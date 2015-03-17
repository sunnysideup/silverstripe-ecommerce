/**
 *@author Nicolaas [at] sunnysideup.co.nz
 * adds JS functionality to the checkout page
 * makes all the previous and next buttons and forms ajax
 *
 **/

;(function($) {
	$(document).ready(
		function() {
			EcomCheckoutPage.init();
		}
	);
})(jQuery);


var EcomCheckoutPage = {

	nextPreviousOverviewListSelector: "ol.steps a",

	nextPreviousButtonsSelector: ".checkoutStepPrevNextHolder a",

	outerHolderSelector: "#Checkout",

	loadingSelector: ".loading",

	init: function(){
		EcomCheckoutPage.makeAllPreviousAndNextAjaxified();
		EcomCheckoutPage.makeAllFormsAjaxified();
	},

	makeAllPreviousAndNextAjaxified: function(){
		jQuery("body").on(
			"click",
			EcomCheckoutPage.nextPreviousOverviewListSelector+", "+EcomCheckoutPage.nextPreviousButtonsSelector,
			function(event){
				event.preventDefault();
				var href = jQuery(this).attr("href");
				jQuery(EcomCheckoutPage.outerHolderSelector).fadeOut(
					function() {
						jQuery(EcomCheckoutPage.outerHolderSelector).addClass(EcomCheckoutPage.loadingSelector);
						var jqxhr = jQuery.ajax(
							{
								url: href,
								settings: {
									cache: false
								}
							}
						)
						.done(
							function( data, textStatus, jqXHR ) {
								var headers = EcomCheckoutPage.parseResponseHeaders(jqXHR.getAllResponseHeaders());
								console.debug(headers);
								var CSSArray = headers["X-Include-CSS"].split(",");
								console.debug(headers["X-Include-CSS"]);
								console.debug(CSSArray);
								if(CSSArray.length > 0) {
									jQuery.each(
										CSSArray,
										function( index, value ) {
											jQuery('<link>')
												.appendTo('head')
												.attr({type : 'text/css', rel : 'stylesheet'})
												.attr('href', value);
										}
									);
								}
								var JSArray = headers["X-Include-JS"].split(",");
								if(JSArray.length > 0) {
									jQuery.each(
										JSArray,
										function( index, value ) {
											jQuery.getScript( value);
										}
									);
								}
								jQuery(EcomCheckoutPage.outerHolderSelector).html(data);
								EcomQuantityField.reinit();
							}
						)
						.fail(
							function() {
								window.location = href;
							}
						)
						.always(
							function() {
								jQuery(EcomCheckoutPage.outerHolderSelector).fadeIn(
									function() {
										jQuery(EcomCheckoutPage.outerHolderSelector).removeClass(EcomCheckoutPage.loadingSelector);
									}
								);
							}
						);
					}
				);
				return false;
			}
		);
	},

	makeAllFormsAjaxified: function(){

	},

	/**
	 * XmlHttpRequest's getAllResponseHeaders() method returns a string of response
	 * headers according to the format described here:
	 * http://www.w3.org/TR/XMLHttpRequest/#the-getallresponseheaders-method
	 * This method parses that string into a user-friendly key/value pair object.
	 */
	parseResponseHeaders: function(headerStr) {
		var headers = {};
		if (!headerStr) {
			return headers;
		}
		var headerPairs = headerStr.split('\u000d\u000a');
		for (var i = 0; i < headerPairs.length; i++) {
			var headerPair = headerPairs[i];
			// Can't use split() here because it does the wrong thing
			// if the header value has the string ": " in it.
			var index = headerPair.indexOf('\u003a\u0020');
			if (index > 0) {
				var key = headerPair.substring(0, index);
				var val = headerPair.substring(index + 2);
				headers[key] = val;
			}
		}
		return headers;
	}

}
