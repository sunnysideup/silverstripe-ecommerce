(function($){

	jQuery(document).ready(
		function() {
			EcomBuyableSelectField.init();
		}
	);
})(jQuery);


EcomBuyableSelectField = {

	loadingClass: "loading",
		set_loadingClass: function(s) {this.loadingClass = i;},

	requestTerm: "",
		set_requestTerm: function(s) {this.requestTerm = i;},

	fieldName: "",
		set_fieldName: function(s) {this.fieldName = s;},

	countOfSuggestions: 7,
		set_countOfSuggestions: function(i) {this.countOfSuggestions = i;},

	minLength: 2,
		set_minLength: function(i) {this.minLength = i;},

	selectedFieldName: "",
		set_selectedFieldName: function(s) {this.selectedFieldName = s;},

	selectedFieldID: "",
		set_selectedFieldID: function(s) {this.selectedFieldID = s;},

	init: function() {
		jQuery( "#"+EcomBuyableSelectField.fieldName+"-FindBuyable").autocomplete({
			 delay: 700,
			 source: function(request, response) {
				jQuery( "#"+EcomBuyableSelectField.fieldName+"-FindBuyable").addClass(EcomBuyableSelectField.loadingClass);
				jQuery("body").css("cursor", "progress");
				EcomBuyableSelectField.requestTerm = request.term;
				jQuery.ajax({
					type: "POST",
					url: "/ecommercebuyabledatalist/json/",
					dataType: "json",
					data: {
						term: request.term,
						countOfSuggestions: EcomBuyableSelectField.countOfSuggestions
					},
					error: function(xhr, textStatus, errorThrown) {
						alert("Error: " + xhr.responseText+errorThrown+textStatus);
					},
					success: function(data) {
						response(
							jQuery.map(
								data,
								function(c) {
									return {
										label: c.Title,
										value: EcomBuyableSelectField.requestTerm,
										title: c.Title,
										className: c.ClassName,
										id: c.ID,
										version: c.Version
									}
								}
							)
						);
					}
				});
			},
			minLength: EcomBuyableSelectField.minLength,
			select: function(event, ui) {
				if(
					jQuery("input[name=\'BuyableID\']").length == 0 ||
					jQuery("input[name=\'BuyableClassName\']").length  == 0 ||
					jQuery("input[name=\'Version\']").length  == 0
				) {
					alert("Error: can not find selectedFieldID or BuyableClassName or Version field");
				}
				else {
					jQuery("input[name=\'BuyableID\']").val(ui.item.id);
					jQuery("input[name=\'BuyableClassName\']").val(ui.item.className);
					jQuery("input[name=\'Version\']").val(ui.item.version);
					jQuery("input[name=\'"+EcomBuyableSelectField.selectedFieldName+"\']").val(ui.item.title);
					jQuery("span#"+EcomBuyableSelectField.selectedFieldID+"").text(ui.item.title);
				}
				jQuery( "#"+EcomBuyableSelectField.fieldName+"-FindBuyable").removeClass(EcomBuyableSelectField.loadingClass);
			}
		});
	}

}
