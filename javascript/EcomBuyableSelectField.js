(function($){

	jQuery(document).ready(
		function() {
			EcomBuyableSelectField.init();
		}
	);
})(jQuery);


EcomBuyableSelectField = {

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

	selectedFieldID: 0,
		set_selectedFieldID: function(i) {this.selectedFieldID = i;},

	init: function() {
		jQuery( "#"+EcomBuyableSelectField.fieldName+"-FindBuyable").autocomplete({
			 delay: 500,
			 source: function(request, response) {
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
					jQuery("input[name=\'selectedFieldID\']").length == 0 ||
					jQuery("input[name=\'BuyableClassName\']").length  == 0 ||
					jQuery("input[name=\'Version\']").length  == 0
				) {
					alert("Error: can not find selectedFieldID or BuyableClassName or Version field");
				}
				else {
					jQuery("input[name=\'selectedFieldID\']").val(ui.item.id);
					jQuery("input[name=\'BuyableClassName\']").val(ui.item.className);
					jQuery("input[name=\'Version\']").val(ui.item.version);
					jQuery("input[name=\'"+EcomBuyableSelectField.selectedFieldName+"\']").val(ui.item.title);
					jQuery("span#"+EcomBuyableSelectField.selectedFieldID+"").text(ui.item.title);
				}
				jQuery("body").css("cursor", "auto");
			}
		});
	}

}
