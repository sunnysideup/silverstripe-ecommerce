

;(function($) {
	$(document).ready(
		function() {
			EcomSelectOrderAddressField.init();
		}
	);
})(jQuery);



var EcomSelectOrderAddressField = {

	fieldSelector: ".selectorderaddress input[class='radio']",

	removeLinkSelector: ".noLongerInUse",

	areYouSureMessage: "Are you sure you want to remove this address?",

	data: [],
		set_data: function(i, object) {EcomSelectOrderAddressField.data[i] = object; },

	init: function() {
		EcomSelectOrderAddressField.setupAddressChanges();
		EcomSelectOrderAddressField.setupNoLongerInUseLinks();
	},

	setupAddressChanges: function(){
		jQuery(EcomSelectOrderAddressField.fieldSelector).change(
			function(e) {
				id = jQuery(this).val();
				var data = EcomSelectOrderAddressField.data[id];
				if(data) {
					jQuery.each(
						data,
						function(i, n){
							jQuery("input[name='"+i+"']").val(n);
						}
					);
				}
			}
		);
	},

	setupNoLongerInUseLinks: function(){
		jQuery(EcomSelectOrderAddressField.removeLinkSelector).click(
			function(e) {
				e.preventDefault();
				var id = jQuery(this).attr("rel");
				url = jQuery(this).attr("href");
				jQuery.get(
					url,
					function(data){
						jQuery(".val"+id).addClass("removed");
						jQuery(".val"+id+" input").remove();
						jQuery(".val"+id+" label").html(data);
					}
				);
			}
		)
	}



}
