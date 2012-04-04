/**
	* @description:
	* This class provides extra functionality for the
	* Product and ProductGroup Page.
	* @author nicolaas @ sunny side up . co . nz
	**/
(function($){
	$(document).ready(
		function() {
			EcomProducts.init();
		}
	);
})(jQuery);

EcomProducts = {

	selectVariationSelector: 					'a.selectVariation',
	maskID:														'SelectVariationMask',
	maskSelector:											'#SelectVariationMask',
	popupWindowID:										'SelectVarationWindow',
	popupWindowSelector: 							'#SelectVarationWindow',
	popupWindowCloseLinkSelector: 		'.selectVarationWindowCloseLink a',

	init: function(){
		//select all the a tag with name equal to modal
		jQuery(EcomProducts.selectVariationSelector).click(
			function(e) {
				//Cancel the link behavior
				e.preventDefault();
				//Get the A tag
				var url = jQuery(this).attr('href');
				if(jQuery(EcomProducts.maskSelector).length == 0) {
					jQuery("body").prepend('<div id="'+EcomProducts.maskID+'"></div>');
				}
				if(jQuery(EcomProducts.popupWindowID).length == 0) {
					jQuery("body").prepend('<div id="'+EcomProducts.popupWindowID+'" class="loading"></div>');
				}

				jQuery(document).keyup(EcomProducts.escKeyFunction);

				jQuery.get(
					url,
					function(data, success) {
						jQuery(EcomProducts.popupWindowSelector).html(data).removeClass("loading");
						//if close button is clicked
						jQuery(EcomProducts.popupWindowCloseLinkSelector).click(
							function (e) {
								e.preventDefault();
								EcomProducts.removeAll();
								return false;
							}
						);
						//if product variation is added
						jQuery(EcomProducts.popupWindowSelector+' a').click(
							function (e) {
								e.preventDefault();
								url = jQuery(this).attr("href");
								EcomCart.getChanges(url, null, this);
								EcomProducts.removeAll();
								return false;
							}
						);
					}
				);
				//Get the screen height and width
				var maskHeight = jQuery(document).height();
				var maskWidth = jQuery(window).width();

				//Set height and width to mask to fill up the whole screen
				jQuery(EcomProducts.maskSelector).css({'width':maskWidth,'height':maskHeight});

				//transition effect
				//jQuery(EcomProducts.maskSelector).fadeIn(1000);
				jQuery(EcomProducts.maskSelector).fadeTo(
					"slow",
					0.8,
					function(){
						//Get the window height and width
						var winH = jQuery(window).height();
						var winW = jQuery(window).width();
						//Set the popup window to center
						jQuery(EcomProducts.popupWindowSelector).css('top',  winH/2-jQuery(EcomProducts.popupWindowSelector).height()/2);
						jQuery(EcomProducts.popupWindowSelector).css('left', winW/2-jQuery(EcomProducts.popupWindowSelector).width()/2);
						//transition effect
						jQuery(EcomProducts.popupWindowSelector).fadeIn("slow");
						//if mask is clicked
						jQuery(EcomProducts.maskSelector).click(
							function () {
								EcomProducts.removeAll();
							}
						);
					}
				);
			}
		);

	},

	escKeyFunction: function(e) {
		if (e.keyCode == 27) {
			EcomProducts.removeAll();
		}
	},

	removeAll: function(){
		jQuery(document).unbind("keyup", EcomProducts.escKeyFunction);
		jQuery(EcomProducts.maskSelector).remove();
		jQuery(EcomProducts.popupWindowSelector).remove();
	}


}



