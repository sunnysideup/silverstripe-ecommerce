

;(function($) {
	$(document).ready(
		function() {
			EcomSelectOrderAddressField.init();
		}
	);
})(jQuery);



var EcomSelectOrderAddressField = {

	/**
	 * selector for the "select address field"
	 * @var String
	 */
	fieldSelector: ".selectorderaddress",

	/**
	 * selector for the "select address field" input element
	 * @var String
	 */
	inputSelector: "input[class='radio']",

	/**
	 * selector for the related address field holder
	 * @var String
	 */
	addressSelector: ".orderAddressHolder",

	/**
	 * selector for the link that removes an 'obsolete' address
	 * @var String
	 */
	removeLinkSelector: ".noLongerInUse",

	/**
	 * class used to show that something is being loaded
	 * @var String
	 */
	loadingClass: "loading",


	/**
	 * message shown before an address is removed
	 * @var String
	 */
	areYouSureMessage: "Are you sure you want to remove this address?",

	/**
	 * array of data connected to each "selectable" address
	 * @var Array
	 */
	data: [],
		set_data: function(i, object) {EcomSelectOrderAddressField.data[i] = object; },

	init: function() {
		EcomSelectOrderAddressField.setupAddressChanges();
		EcomSelectOrderAddressField.setupNoLongerInUseLinks();

	},

	setupAddressChanges: function(){
		jQuery(EcomSelectOrderAddressField.fieldSelector).each(
			function(i, el){
				//jQuery(el).next(EcomSelectOrderAddressField.addressSelector).hide();
				jQuery(el).find(EcomSelectOrderAddressField.inputSelector).each(
					function(i, el) {
						jQuery(el).change(
							function(e) {
								//jQuery(this).parents(EcomSelectOrderAddressField.fieldSelector).next(EcomSelectOrderAddressField.addressSelector).show();
								id = jQuery(this).val();
								jQuery(this).closest("ul").children("li").removeClass("selected");
								jQuery(this).closest("li").addClass("selected");
								var data = EcomSelectOrderAddressField.data[id];
								if(data) {
									jQuery.each(
										data,
										function(i, n){
											jQuery("input[name='"+i+"'], select[name='"+i+"']").val(n);
										}
									);
								}
							}
						);
					}
				);
				//must do the after setting change event.
				jQuery(this).find("input:first").click();
			}
		);
	},

	setupNoLongerInUseLinks: function(){
		jQuery(EcomSelectOrderAddressField.removeLinkSelector).click(
			function(e) {
				e.preventDefault();
				jQuery(this).addClass(EcomSelectOrderAddressField.loadingClass);
				var id = jQuery(this).attr("rel");
				url = jQuery(this).attr("href");
				jQuery.get(
					url,
					function(data){
						jQuery(".val"+id).addClass("removed");
						jQuery(".val"+id+" input").remove();
						jQuery(".val"+id+" label").html(data);
						jQuery(this).removeClass(EcomSelectOrderAddressField.loadingClass);
					}
				);
			}
		);
	}



}
