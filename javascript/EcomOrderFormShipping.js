/**
 *@author nicolaas[at]sunnysideup.co.nz
 * This adds functionality to the shipping address section of the checkout form
 *
 **/
;(function($) {
	$(document).ready(
		function() {
			EcomOrderFormWithShippingAddress.init();
			EcomOrderFormWithShippingAddress.removeEmailFromShippingCityHack();
		}
	);

})(jQuery);


var EcomOrderFormWithShippingAddress = {

	firstNameSelector: "#FirstName input",

	shippingFirstNameSelector: "#ShippingFirstName input",

	//

	surnameSelector: "#Surname input",

	shippingSurnameSelector: "#ShippingSurname input",

	//

	addressSelector: "#Address input",

	shippingAddressSelector: "#ShippingAddress input",

	//

	extraAddressSelector: "#Address2 input",

	shippingExtraAddressSelector: "#ShippingAddress2 input",

	//

	citySelector: "#City input",

	shippingCitySelector: "#ShippingCity input",

	//

	postalCodeSelector: "#PostalCode input",

	shippingPostalCodeSelector: "#ShippingPostalCode input",

	//

	regionCodeSelector: "#RegionCode input",

	shippingRegionCodeSelector: "#ShippingRegionCode input",

	//


	regionIDCodeSelector: "#RegionID select",

	shippingRegionIDSelector: "#ShippingRegionID select",

	//

	countrySelector: "#Country select",

	shippingCountrySelector: "#ShippingCountry select",

	//

	phoneSelector: "#Phone input",

	shippingPhoneSelector: "#ShippingPhone input",

	//

	//mobilePhoneSelector: "#MobilePhone input",

	//shippingMobilePhoneSelector: "#ShippingMobilePhone input",

	//================

	shippingSectionSelector: ".shippingFieldsHeader, .shippingFields",

	useShippingDetailsSelector: "input[name='UseShippingAddress']",

	shippingGeoCodingFieldSelector: "input[name='ShippingEcommerceGeocodingField']",

	closed: false,

	//hides shipping fields
	//toggle shipping fields when "use separate shipping address" is ticked
	//update shipping fields, when billing fields are changed.
	init: function(){
		//hide shipping fields
		if(jQuery(EcomOrderFormWithShippingAddress.useShippingDetailsSelector).length > 0) {
			if(jQuery(EcomOrderFormAddress.useShippingDetailsSelector).is(":checked")) {
			}
			else {
				jQuery(EcomOrderFormWithShippingAddress.shippingSectionSelector).hide();
				EcomOrderFormWithShippingAddress.closed = true;
			}
			//turn-on shipping details toggle
			jQuery(EcomOrderFormWithShippingAddress.useShippingDetailsSelector).change(
				function(){
					if(jQuery(EcomOrderFormWithShippingAddress.useShippingDetailsSelector).is(":checked")) {
						jQuery(EcomOrderFormWithShippingAddress.shippingSectionSelector).slideDown();
						jQuery(EcomOrderFormWithShippingAddress.shippingFirstNameSelector).focus();
						jQuery(EcomOrderFormWithShippingAddress.shippingGeoCodingFieldSelector).attr("required", "required");
						EcomOrderFormWithShippingAddress.updateFields();
						EcomOrderFormWithShippingAddress.closed = false;
					}
					else {
						jQuery(EcomOrderFormWithShippingAddress.shippingSectionSelector).slideUp();
						jQuery(EcomOrderFormWithShippingAddress.shippingGeoCodingFieldSelector).removeAttr("required");
						EcomOrderFormWithShippingAddress.closed = true;
					}
				}
			);
			//update on change
			var originatorFieldSelector =
					EcomOrderFormWithShippingAddress.firstNameSelector+", "+
					EcomOrderFormWithShippingAddress.surnameSelector+", "+
					EcomOrderFormWithShippingAddress.addressSelector+" ,"+
					EcomOrderFormWithShippingAddress.extraAddressSelector+", "+
					EcomOrderFormWithShippingAddress.citySelector+", "+
					EcomOrderFormWithShippingAddress.postalCodeSelector+", "+
					EcomOrderFormWithShippingAddress.regionCodeSelector+", "+
					EcomOrderFormWithShippingAddress.regionIDCodeSelector+", "+
					EcomOrderFormWithShippingAddress.countrySelector+", "+
					EcomOrderFormWithShippingAddress.phoneSelector;
			jQuery(originatorFieldSelector).change(
				function() {
					EcomOrderFormWithShippingAddress.updateFields();
				}
			);
			jQuery(originatorFieldSelector).focus(
				function() {
					EcomOrderFormWithShippingAddress.updateFields();
				}
			);
		}
		jQuery(EcomOrderFormWithShippingAddress.shippingGeoCodingFieldSelector).removeAttr("required");
	},

	//copy the billing address details to the shipping address details
	updateFields: function() {

		//mobile phone
		//var MobilePhone = jQuery(EcomOrderFormWithShippingAddress.mobilePhoneSelector).val();
		//var ShippingMobilePhone = jQuery(EcomOrderFormWithShippingAddress.shippingMobilePhoneSelector).val();
		//if((!ShippingMobilePhone && MobilePhone) || EcomOrderFormWithShippingAddress.closed) {
			//jQuery(EcomOrderFormWithShippingAddress.shippingMobilePhoneSelector).val(MobilePhone).change();
		//}

		//phone
		var Phone = jQuery(EcomOrderFormWithShippingAddress.phoneSelector).val();
		var ShippingPhone = jQuery(EcomOrderFormWithShippingAddress.shippingPhoneSelector).val();
		if((!ShippingPhone && Phone) || EcomOrderFormWithShippingAddress.closed) {
			jQuery(EcomOrderFormWithShippingAddress.shippingPhoneSelector).val(Phone).change();
		}

		//region Code
		var RegionCode = jQuery(EcomOrderFormWithShippingAddress.regionCodeSelector).val();
		var ShippingRegionCode = jQuery(EcomOrderFormWithShippingAddress.shippingRegionCodeSelector).val();
		if((!ShippingRegionCode && RegionCode) || EcomOrderFormWithShippingAddress.closed) {
			jQuery(EcomOrderFormWithShippingAddress.shippingRegionCodeSelector).val(RegionCode).change();
		}

		//region ID
		var RegionID = jQuery(EcomOrderFormWithShippingAddress.regionIDCodeSelector).val();
		var ShippingRegionID = jQuery(EcomOrderFormWithShippingAddress.shippingRegionIDSelector).val();
		if((!ShippingRegionID && RegionID) || EcomOrderFormWithShippingAddress.closed) {
			jQuery(EcomOrderFormWithShippingAddress.shippingRegionIDSelector).val(RegionID).change();
		}

		//postal code
		var PostalCode = jQuery(EcomOrderFormWithShippingAddress.postalCodeSelector).val();
		var ShippingPostalCode = jQuery(EcomOrderFormWithShippingAddress.shippingPostalCodeSelector).val();
		if((!ShippingPostalCode && PostalCode) || EcomOrderFormWithShippingAddress.closed) {
			jQuery(EcomOrderFormWithShippingAddress.shippingPostalCodeSelector).val(PostalCode).change();
		}

		//country
		var Country = jQuery(EcomOrderFormWithShippingAddress.countrySelector).val();
		var ShippingCountry = jQuery(EcomOrderFormWithShippingAddress.shippingCountrySelector).val();
		if(((!ShippingCountry || ShippingCountry == "AF") && Country) || EcomOrderFormWithShippingAddress.closed) {
			jQuery(EcomOrderFormWithShippingAddress.shippingCountrySelector).val(Country);
		}

		//city
		var City = jQuery(EcomOrderFormWithShippingAddress.citySelector).val();
		var ShippingCity = jQuery(EcomOrderFormWithShippingAddress.shippingCitySelector).val();
		if((!ShippingCity && City) || EcomOrderFormWithShippingAddress.closed) {
			jQuery(EcomOrderFormWithShippingAddress.shippingCitySelector).val(City).change();
		}
		//address
		var Address = jQuery(EcomOrderFormWithShippingAddress.addressSelector).val();
		var ShippingAddress = jQuery(EcomOrderFormWithShippingAddress.shippingAddressSelector).val();
		if((!ShippingAddress && Address) || EcomOrderFormWithShippingAddress.closed) {
			jQuery(EcomOrderFormWithShippingAddress.shippingAddressSelector).val(Address).change();
		}
		//address 2
		var AddressLine2 = jQuery(EcomOrderFormWithShippingAddress.extraAddressSelector).val();
		var ShippingAddress2 = jQuery(EcomOrderFormWithShippingAddress.shippingExtraAddressSelector).val();
		if((!ShippingAddress2 && AddressLine2) || EcomOrderFormWithShippingAddress.closed) {
			jQuery(EcomOrderFormWithShippingAddress.shippingExtraAddressSelector).val(AddressLine2).change();
		}

		//surname
		var Surname = jQuery(EcomOrderFormWithShippingAddress.surnameSelector).val();
		var ShippingSurname = jQuery(EcomOrderFormWithShippingAddress.shippingSurnameSelector).val();
		if((!ShippingSurname && Surname) || EcomOrderFormWithShippingAddress.closed) {
			jQuery(EcomOrderFormWithShippingAddress.shippingSurnameSelector).val(Surname).change();
		}

		//first name
		var FirstName = jQuery(EcomOrderFormWithShippingAddress.firstNameSelector).val();
		var ShippingFirstName = jQuery(EcomOrderFormWithShippingAddress.shippingFirstNameSelector).val();
		if((!ShippingFirstName && FirstName) || EcomOrderFormWithShippingAddress.closed) {
			jQuery(EcomOrderFormWithShippingAddress.shippingFirstNameSelector).val(FirstName).change();
		}

	},

	//this function exists, because FF was auto-completing Shipping City as the username part of a password / username combination (password being the next field)
	removeEmailFromShippingCityHack: function() {
		var pattern=/^([a-zA-Z0-9_.-])+@([a-zA-Z0-9_.-])+\.([a-zA-Z])+([a-zA-Z])+/;
		var shippingCitySelectorValue = jQuery(EcomOrderFormWithShippingAddress.shippingCitySelector).val();
		if(pattern.test(shippingCitySelectorValue)){
			jQuery(EcomOrderFormWithShippingAddress.shippingCitySelector).val(jQuery(EcomOrderFormWithShippingAddress.citySelector).val()).change();
		}
		else{
			//do nothing
		}

	}
}
