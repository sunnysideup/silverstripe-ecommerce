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
			EcomProducts.reinit();
		}
	);
})(jQuery);

EcomProducts = {

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

	//see: http://www.jacklmoore.com/colorbox/
	colorboxDialogOptions_viewImages: {},

	selectVariationSelector: 'a.selectVariation',

	imagePopupSelector: '.colorboxImagePopup',

	openCloseSectionLinkSelector: "a.openCloseMySectionLink",

	init: function(){
		jQuery(document).on(
			"click",
			EcomProducts.selectVariationSelector,
			function (e) {
				EcomProducts.colorboxDialogOptions_addVariations.href = jQuery(this).attr('href');
				EcomProducts.colorboxDialogOptions_addVariations.open = true;
				jQuery.colorbox(EcomProducts.colorboxDialogOptions_addVariations);
				return false;
			}
		);
		jQuery(document).on(
			"click",
			EcomProducts.imagePopupSelector,
			function (e) {
				EcomProducts.imagePopupSelector.href = jQuery(this).attr('href');
				EcomProducts.imagePopupSelector.open = true;
				jQuery.colorbox(EcomProducts.imagePopupSelector);
				return false;
			}
		);
		jQuery(document).on(
			"click",
			EcomProducts.openCloseSectionLinkSelector,
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
	},


	reinit: function(){
		var thereIsOnlyOne = false;
		if(jQuery(EcomProducts.openCloseSectionLinkSelector).length == 1) {
			thereIsOnlyOne = true;
		}
		jQuery(EcomProducts.openCloseSectionLinkSelector).each(
			function(i, el) {
				//must be last
				if(thereIsOnlyOne) {
					jQuery(el).addClass("open");
				}
				else {
					jQuery(el).addClass("open").click();
				}
			}

		);
	}


}



