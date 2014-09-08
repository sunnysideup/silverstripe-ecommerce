jQuery(document).ready(
	function() {
		EcomEcommerceGeocodingField.init();
	}
);


var EcomEcommerceGeocodingField = {

	/**
	 * should we use the sensor on mobile
	 * phones to help?
	 * @var Boolean
	 */
	useSensor: false,

	/**
	 *
	 * @var autocomplete object provided by Google
	 */
	autocomplete: null,

	/**
	 * name of the html field (e.g. MyInputField)
	 * this is provided by PHP using a small customScript
	 *
	 * @var String
	 */
	fieldName: "",

	/**
	 * object that is being used to find the address.
	 * This is set in the init method
	 * @var jQueryObject
	 */
	entryField: null,

	/**
	 * based on format FormField: [GeocodingAddressType: format]
	  Address1: {'street_number': 'short_name', 'route': 'long_name'},
		Address2: {'locality': 'long_name'},
		City: {'administrative_area_level_1': 'short_name'},
		Country: {'country': 'long_name'},
		PostcalCode: {'postal_code': 'short_name'}
	 *
	 * @var JSON
	 */
	relatedFields: {},

	/**
	 *
	 * @var String
	 */
	errorMessageMoreSpecific: "Error: please enter a more specific location.",

	/**
	 *
	 * @var String
	 */
	errorMessageAddressNotFound: "Error: sorry, address could not be found.",

	/**
	 *
	 * this method sets up all the listeners
	 * and the basic state.
	 */
	init: function () {

		//clean up affected fields
		EcomEcommerceGeocodingField.clearFields();
		EcomEcommerceGeocodingField.hideFields();

		//set basic classes for input field
		EcomEcommerceGeocodingField.entryField = jQuery('input[name="'+EcomEcommerceGeocodingField.fieldName+'"]');
		EcomEcommerceGeocodingField.setResults("no");
		EcomEcommerceGeocodingField.updateEntryFieldStatus();

		//set up auto-complete stuff
		var fieldID = EcomEcommerceGeocodingField.entryField.attr("id");
		EcomEcommerceGeocodingField.autocomplete = new google.maps.places.Autocomplete(
			document.getElementById(fieldID),
			{ types: [ 'geocode' ] }
		);
		google.maps.event.addListener(
			EcomEcommerceGeocodingField.autocomplete,
			'place_changed',
			function() {
				EcomEcommerceGeocodingField.fillInAddress();
			}
		);

		//add listeners
		EcomEcommerceGeocodingField.entryField
			.focus(
				function(){
					EcomEcommerceGeocodingField.hideFields();
					//use sensor ..
					if(EcomEcommerceGeocodingField.useSensor) {
						if (navigator.geolocation) {
							navigator.geolocation.getCurrentPosition(
								function(position) {
									var geolocation = new google.maps.LatLng(position.coords.latitude,position.coords.longitude);
									EcomEcommerceGeocodingField.autocomplete .setBounds(new google.maps.LatLngBounds(geolocation, geolocation));
								}
							);
						}
					}
					EcomEcommerceGeocodingField.updateEntryFieldStatus();
				}
			)
			.focusout(
				function(){
					if(EcomEcommerceGeocodingField.hasResults()) {
						EcomEcommerceGeocodingField.showFields();
					}
					EcomEcommerceGeocodingField.updateEntryFieldStatus();
				}
			)
			.keypress(
				function(e){
					var code = e.which;
					if (code == 13 ) return false;
				}
			)
			.on(
				'input propertychange paste',
				function(e){
					//tab
					if(EcomEcommerceGeocodingField.hasResults()) {
						EcomEcommerceGeocodingField.clearFields();
						EcomEcommerceGeocodingField.setResults( "no");
						EcomEcommerceGeocodingField.updateEntryFieldStatus();
					}
					//or...if ( e.which == 13 ) e.preventDefault();
				}
			);
	},

	fillInAddress: function() {
		var updated = false;
		var place = EcomEcommerceGeocodingField.autocomplete.getPlace();
		EcomEcommerceGeocodingField.entryField.attr("data-has-result", "no");
		//console.log(place);
		var placeIsSpecificEnough = false;
		for (var i = 0; i < place.types.length; i++) {
			if(place.types[i] == "street_address") {
				placeIsSpecificEnough = true;
			}
		}
		if(placeIsSpecificEnough) {
			if(place && place.address_components) {
				for (var formField in EcomEcommerceGeocodingField.relatedFields) {
					var previousValues = [];
					//reset field and show it...
					jQuery("#"+formField).show().find("input, select").val("");
					//console.debug("- checking form field: "+formField);
					for (var j = 0; j < place.address_components.length; j++) {
						////console.debug("-- provided information: "+place.address_components[j]);
						for (var k = 0; k < place.address_components[j].types.length; k++) {
							var googleType = place.address_components[j].types[k];
							//console.debug("---- found Google Info for: "+googleType);
							////console.log(EcomEcommerceGeocodingField.relatedFields[formField]);
							for (var fieldType in EcomEcommerceGeocodingField.relatedFields[formField]) {
								//console.debug("-------- with form field checking: "+fieldType+" is the same as google type: "+googleType);
								if (fieldType == googleType) {
									var googleVariable = EcomEcommerceGeocodingField.relatedFields[formField][fieldType];
									var value = place.address_components[j][googleVariable];
									if(jQuery.inArray(value, previousValues) == -1) {
										previousValues.push(value);
										//console.debug("------------ setting: "+formField+" to "+value+", using "+googleVariable+" in google address");
										previousValueForThisFormField = "";
										if(jQuery('input[name="'+formField+'"]').length) {
											var previousValueForThisFormField = jQuery('input[name="'+formField+'"]').val();
										}
										if(previousValueForThisFormField) {
											value = previousValueForThisFormField + " " + value;
										}
										jQuery('input[name="'+formField+'"], select[name="'+formField+'"]').val(previousValueForThisFormField+ " "+value);
										EcomEcommerceGeocodingField.setResults("yes");
									}
									else {
										//console.debug("-------- data already used: "+value);
									}
								}
							}
						}
					}
				}
			}
			else {
				EcomEcommerceGeocodingField.entryField.val(EcomEcommerceGeocodingField.errorMessageAddressNotFound);
			}
		}
		else {
			EcomEcommerceGeocodingField.entryField.val(EcomEcommerceGeocodingField.errorMessageMoreSpecific);
		}
		EcomEcommerceGeocodingField.updateEntryFieldStatus();
	},

	showFields: function(){
		//hide fields to be completed for now...
		for (var formField in EcomEcommerceGeocodingField.relatedFields) {
			jQuery("#"+formField).show();
		}
	},

	hideFields: function(){
		//hide fields to be completed for now...
		for (var formField in EcomEcommerceGeocodingField.relatedFields) {
			jQuery("#"+formField).hide();
		}
	},

	clearFields: function(){
		//hide fields to be completed for now...
		for (var formField in EcomEcommerceGeocodingField.relatedFields) {
			jQuery("#"+formField).find("select, input, textarea").val("");
		}
	},

	/**
	 * tells us if results have been found
	 * @param string
	 */
	setResults: function(resultAsYesOrNo) {
		return  EcomEcommerceGeocodingField.entryField.attr("data-has-result", resultAsYesOrNo);
	},

	/**
	 * tells us if results have been found
	 * @return Boolean
	 */
	hasResults: function() {
		return EcomEcommerceGeocodingField.entryField.attr("data-has-result") == "yes" ? true : false
	},

	/**
	 * sets up all the various class options based on the current status
	 */
	updateEntryFieldStatus: function() {
		var hasResult =  EcomEcommerceGeocodingField.hasResults();
		var hasText = EcomEcommerceGeocodingField.entryField.val().length > 1;
		if(hasResult) {
			EcomEcommerceGeocodingField.entryField.addClass("selected");
			EcomEcommerceGeocodingField.entryField.removeClass("useMe");
		}
		else{
			EcomEcommerceGeocodingField.entryField.removeClass("selected");
			EcomEcommerceGeocodingField.entryField.addClass("useMe");
		}
		if(hasText) {
			EcomEcommerceGeocodingField.entryField.addClass("hasText");
		}
		else{
			EcomEcommerceGeocodingField.entryField.removeClass("hasText");
		}
	}

}
