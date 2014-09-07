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
	 * @var String
	 */
	fieldName: "",

	/**
	 * ID of the html field (e.g. MyInputField_BLABLA)
	 * calculated....
	 * @var ID
	 */
	fieldID: "",

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

	init: function () {

		//hide fields to be completed for now...
		for (var formField in EcomEcommerceGeocodingField.relatedFields) {
			jQuery("#"+formField).hide();
		}
		//set class for input field
		jQuery('input[name="'+EcomEcommerceGeocodingField.fieldName+'"]');

		//get field id for input field
		EcomEcommerceGeocodingField.fieldID = jQuery('input[name="'+EcomEcommerceGeocodingField.fieldName+'"]').attr("id");

		//set up auto-complete stuff
		EcomEcommerceGeocodingField.autocomplete = new google.maps.places.Autocomplete(
			document.getElementById(EcomEcommerceGeocodingField.fieldID),
			{ types: [ 'geocode' ] }
		);
		google.maps.event.addListener(
			EcomEcommerceGeocodingField.autocomplete,
			'place_changed',
			function() {
				EcomEcommerceGeocodingField.fillInAddress();
			}
		);

		//on focus for the field...
		jQuery('input[name="'+EcomEcommerceGeocodingField.fieldName+'"]')
			.addClass("useMe")
			.focus(
				function(){
					for (var formField in EcomEcommerceGeocodingField.relatedFields) {
						jQuery("#"+formField).hide();
					}

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
					jQuery('input[name="'+EcomEcommerceGeocodingField.fieldName+'"]').removeClass("selected").removeClass("useMe").addClass("unselected");
				}
			)
			.focusout(
				function(){
					if(jQuery('input[name="'+EcomEcommerceGeocodingField.fieldName+'"]').hasClass("selected")) {
						for (var formField in EcomEcommerceGeocodingField.relatedFields) {
							jQuery("#"+formField).show();
						}
					}
					else {
						jQuery('input[name="'+EcomEcommerceGeocodingField.fieldName+'"]').addClass("useMe");
					}
				}
			)
			.keypress(
				function(e){
					if ( e.which == 13 ) return false;
					//or...if ( e.which == 13 ) e.preventDefault();
				}
			);
	},

	fillInAddress: function() {
		var place = EcomEcommerceGeocodingField.autocomplete.getPlace();
		//console.log(place);
		//console.debug("filling address");
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
								var previousValueForThisFormField = jQuery('input[name="'+formField+'"]').val();
								jQuery('input[name="'+formField+'"]').val(previousValueForThisFormField+ " "+value)
							}
							else {
								//console.debug("-------- data already used: "+value);
							}
						}
					}
				}
			}
		}
		jQuery('input[name="'+EcomEcommerceGeocodingField.fieldName+'"]').removeClass("unselected").removeClass("useMe").addClass("selected");
	}

}
