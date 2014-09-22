
var EcomEcommerceGeocodingField = function(fieldName) {

	var geocodingFieldVars = {

		/**
		 * name of the html field (e.g. MyInputField)
		 * this is provided by PHP using a small customScript
		 *
		 * @var String
		 */
		fieldName: fieldName,

		/**
		 * object that is being used to find the address.
		 * basically the jquery object of the input field in html
		 * This is set in the init method
		 * @var jQueryObject
		 */
		entryField: null,

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
		errorMessageMoreSpecific: "",

		/**
		 *
		 * @var String
		 */
		errorMessageAddressNotFound: "",

		/**
		 * @var Boolean
		 */
		debug: false,

		/**
		 *
		 * this method sets up all the listeners
		 * and the basic state.
		 */
		init: function () {

			//clean up affected fields
			//geocodingFieldVars.clearFields();
			geocodingFieldVars.hideFields();

			//set basic classes for input field
			geocodingFieldVars.entryField = jQuery('input[name="'+geocodingFieldVars.fieldName+'"]');
			geocodingFieldVars.setResults("no");
			geocodingFieldVars.updateEntryFieldStatus();

			//set up auto-complete stuff
			var fieldID = geocodingFieldVars.entryField.attr("id");
			geocodingFieldVars.autocomplete = new google.maps.places.Autocomplete(
				document.getElementById(fieldID),
				{ types: [ 'geocode' ] }
			);
			google.maps.event.addListener(
				geocodingFieldVars.autocomplete,
				'place_changed',
				function() {
					geocodingFieldVars.fillInAddress();
				}
			);

			//add listeners
			geocodingFieldVars.entryField
				.focus(
					function(){
						geocodingFieldVars.hideFields();
						//use sensor ..
						if(geocodingFieldVars.useSensor) {
							if (navigator.geolocation) {
								navigator.geolocation.getCurrentPosition(
									function(position) {
										var geolocation = new google.maps.LatLng(position.coords.latitude,position.coords.longitude);
										geocodingFieldVars.autocomplete .setBounds(new google.maps.LatLngBounds(geolocation, geolocation));
									}
								);
							}
						}
						geocodingFieldVars.updateEntryFieldStatus();
					}
				)
				.focusout(
					function(){
						if(geocodingFieldVars.hasResults()) {
							geocodingFieldVars.showFields();
						}
						geocodingFieldVars.updateEntryFieldStatus();
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
						if(geocodingFieldVars.hasResults()) {
							geocodingFieldVars.clearFields();
							geocodingFieldVars.setResults( "no");
							geocodingFieldVars.updateEntryFieldStatus();
						}
						//or...if ( e.which == 13 ) e.preventDefault();
					}
				);
			//bypass
			jQuery("a.bypassGoogleGeocoding").click(
				function(e){
					e.preventDefault();
					geocodingFieldVars.showFields();
					jQuery("#"+geocodingFieldVars.fieldName).hide();
				}
			);
			if(geocodingFieldVars.alreadyHasValues()) {
				jQuery("a.bypassGoogleGeocoding").click();
			}
		},

		fillInAddress: function() {
			var updated = false;
			var place = geocodingFieldVars.autocomplete.getPlace();
			geocodingFieldVars.entryField.attr("data-has-result", "no");
			if(geocodingFieldVars.debug) {console.log(place);}
			var placeIsSpecificEnough = false;
			for (var i = 0; i < place.types.length; i++) {
				if(place.types[i] == "street_address") {
					placeIsSpecificEnough = true;
				}
			}
			if(placeIsSpecificEnough) {
				if(place && place.address_components) {
					for (var formField in geocodingFieldVars.relatedFields) {
						var previousValues = [];
						//reset field and show it...
						jQuery("#"+formField).show().find("input, select").val("");
						if(geocodingFieldVars.debug) {console.debug("- checking form field: "+formField);}
						for (var j = 0; j < place.address_components.length; j++) {
							if(geocodingFieldVars.debug) {console.debug("-- provided information: "+place.address_components[j]);}
							for (var k = 0; k < place.address_components[j].types.length; k++) {
								var googleType = place.address_components[j].types[k];
								if(geocodingFieldVars.debug) {console.debug("---- found Google Info for: "+googleType);}
								if(geocodingFieldVars.debug) {console.log(geocodingFieldVars.relatedFields[formField]);}
								for (var fieldType in geocodingFieldVars.relatedFields[formField]) {
									if(geocodingFieldVars.debug) {console.debug("-------- with form field checking: "+fieldType+" is the same as google type: "+googleType);}
									if (fieldType == googleType) {
										var googleVariable = geocodingFieldVars.relatedFields[formField][fieldType];
										var value = place.address_components[j][googleVariable];
										if(jQuery.inArray(value, previousValues) == -1) {
											previousValues.push(value);
											if(geocodingFieldVars.debug) {console.debug("------------ setting: "+formField+" to "+value+", using "+googleVariable+" in google address");}
											previousValueForThisFormField = "";
											if(jQuery('input[name="'+formField+'"]').length) {
												var previousValueForThisFormField = jQuery('input[name="'+formField+'"]').val();
											}
											if(previousValueForThisFormField) {
												value = previousValueForThisFormField + " " + value;
											}
											jQuery('input[name="'+formField+'"], select[name="'+formField+'"]').val(value);
											geocodingFieldVars.setResults("yes");
										}
										else {
											if(geocodingFieldVars.debug) {console.debug("-------- data already used: "+value);}
										}
									}
								}
							}
						}
					}
				}
				else {
					geocodingFieldVars.entryField.val(geocodingFieldVars.errorMessageAddressNotFound);
				}
			}
			else {
				geocodingFieldVars.entryField.val(geocodingFieldVars.errorMessageMoreSpecific);
			}
			geocodingFieldVars.updateEntryFieldStatus();
		},

		showFields: function(){
			//hide fields to be completed for now...
			for (var formField in geocodingFieldVars.relatedFields) {
				jQuery("#"+formField).show();
			}
		},

		hideFields: function(){
			//hide fields to be completed for now...
			for (var formField in geocodingFieldVars.relatedFields) {
				jQuery("#"+formField).hide();
			}
		},

		/**
		 *
		 * @return Boolean
		 */
		alreadyHasValues: function(){
			var empty = 0;
			var count = 0;
			//hide fields to be completed for now...
			for (var formField in geocodingFieldVars.relatedFields) {
				jQuery("#"+formField).find("select, input, textarea").each(
					function(i, el) {
						count++;
						if(jQuery(el).val() == "" || jQuery(el).val() == 0) {
							empty++;
						}
					}
				);
			}
			if(empty / count <= 0.25) {
				return true;
			}
			return false;
		},

		clearFields: function(){
			//hide fields to be completed for now...
			for (var formField in geocodingFieldVars.relatedFields) {
				jQuery("#"+formField).find("select, input, textarea").val("");
			}
		},

		/**
		 * tells us if results have been found
		 * @param string
		 */
		setResults: function(resultAsYesOrNo) {
			return  geocodingFieldVars.entryField.attr("data-has-result", resultAsYesOrNo);
		},

		/**
		 * tells us if results have been found
		 * @return Boolean
		 */
		hasResults: function() {
			return geocodingFieldVars.entryField.attr("data-has-result") == "yes" ? true : false
		},

		/**
		 * sets up all the various class options based on the current status
		 */
		updateEntryFieldStatus: function() {
			var hasResult =  geocodingFieldVars.hasResults();
			var hasText = geocodingFieldVars.entryField.val().length > 1;
			if(hasResult) {
				geocodingFieldVars.entryField.addClass("selected");
				geocodingFieldVars.entryField.removeClass("useMe");
			}
			else{
				geocodingFieldVars.entryField.removeClass("selected");
				geocodingFieldVars.entryField.addClass("useMe");
			}
			if(hasText) {
				geocodingFieldVars.entryField.addClass("hasText");
			}
			else{
				geocodingFieldVars.entryField.removeClass("hasText");
			}
		}
	}


	// Expose public API
	return {
		getVar: function( variableName ) {
			if ( geocodingFieldVars.hasOwnProperty( variableName ) ) {
				return geocodingFieldVars[ variableName ];
			}
		},
		setVar: function(variableName, value) {
			geocodingFieldVars[variableName] = value;
		},
		init: function(){
			geocodingFieldVars.init();
		}

	}

}
