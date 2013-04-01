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

	selectVariationSelector: 'a.selectVariation',

	colorboxDialogOptions_addVariations: {
		maxHeight: "90%",
		maxWidth: "90%",
		loadingClass: "loading",
		iframe: false,
		model: true,
		onComplete: function (event) {
			EcomCart.reinit();
			EcomQuantityField.set_delegateRootSelector("#colorbox");
			EcomQuantityField.init();
			EcomQuantityField.unset_delegateRootSelector();
		}
	},

	colorboxDialogOptions_viewImages: {
	},

	imagePopupSelector: '.colorboxImagePopup',

	init: function(){
		jQuery(EcomProducts.selectVariationSelector).colorbox(
			EcomProducts.colorboxDialogOptions_addVariations
		);
		jQuery(EcomProducts.imagePopupSelector).colorbox(
			EcomProducts.colorboxDialogOptions_viewImages
		);
	}


}



