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

	//see: http://www.jacklmoore.com/colorbox/
	colorboxDialogOptions_addVariations: {
		height: "95%",
		width: "95%",
		maxHeight: "95%",
		maxWidth: "95%",
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
		jQuery("a.openCloseMySectionLink").click(
			function(event) {
				event.preventDefault();
				var id = jQuery(this).attr("href");
				var idLength = id.length;
				var hashPosition = id.indexOf("#");
				id = id.substr(id.indexOf("#"), idLength - hashPosition);
				jQuery(this).toggleClass("close").toggleClass("open");
				jQuery(id).slideToggle().toggleClass("close").toggleClass("open");
			}
		);
		jQuery("a.openCloseMySectionLink").each(
			function(i, el) {
				var id = jQuery(el).attr("href");
				var idLength = id.length;
				var hashPosition = id.indexOf("#");
				id = id.substr(id.indexOf("#"), idLength - hashPosition);
				jQuery(id).addClass("open");
				//must be last
				jQuery(el).addClass("open").click();
			}
		);
		if(jQuery(".openCloseMySectionLink").length == 1) {
			jQuery(".openCloseMySectionLink").click();
		}
	}


}



