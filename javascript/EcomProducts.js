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

	imagePopupSelector: '.colorboxImagePopup',

	init: function(){
		//select all the a tag with name equal to modal
		jQuery(EcomProducts.selectVariationSelector).colorbox(
			EcomCart.simpleDialogOptions
		);
		jQuery(EcomProducts.imagePopupSelector).colorbox(
			{}
		);
	}


}



