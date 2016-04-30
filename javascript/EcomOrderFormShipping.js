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

    //================

    fieldArray: [],

    //================

    formSelector: "#OrderFormAddress_OrderFormAddress",

    shippingSectionSelector: ".shippingFieldsHeader, .shippingFields",

    useShippingDetailsSelector: "input[name='UseShippingAddress']",

    shippingGeoCodingFieldSelector: "input[name='ShippingEcommerceGeocodingField']",

    closed: false,

    //hides shipping fields
    //toggle shipping fields when "use separate shipping address" is ticked
    //update shipping fields, when billing fields are changed.
    init: function(){

        jQuery(this.formSelector+' input, '+this.formSelector+", select"+this.formSelector+" textarea").each(
            function(i, el){
                var name = jQuery(el).attr("name");
                if(typeof name !== 'undefined') {
                    var billingSelector = EcomOrderFormWithShippingAddress.billingSelector(name);
                    if(jQuery(billingSelector).length > 0) {
                        EcomOrderFormWithShippingAddress.fieldArray.push(name);
                    }
                }
                //your code here
        });

        //hide shipping fields

        if(jQuery(EcomOrderFormWithShippingAddress.useShippingDetailsSelector).length > 0) {
            if(jQuery(EcomOrderFormWithShippingAddress.useShippingDetailsSelector).is(":checked")) {
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
                        var firstShippingField = EcomOrderFormWithShippingAddress.fieldArray[0];
                        jQuery(EcomOrderFormWithShippingAddress.shippingFieldSelector(firstShippingField)).focus();
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
            var i;
            var originatorFieldSelector = " ";
            
            for (i = 0; i < EcomOrderFormWithShippingAddress.fieldArray.length; ++i) {
                if((i  + 1) < EcomOrderFormWithShippingAddress.fieldArray.length) {
                    originatorFieldSelector += ", ";
                }
                originatorFieldSelector += EcomOrderFormWithShippingAddress.billingFieldSelector(EcomOrderFormWithShippingAddress.fieldArray[i]);
            }
            if(i > 0) {
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
        }
        //why this????
        jQuery(EcomOrderFormWithShippingAddress.shippingGeoCodingFieldSelector).removeAttr("required");
    },

    updateFields: function() {
        //copy the billing address details to the shipping address details
        if(jQuery(EcomOrderFormWithShippingAddress.useShippingDetailsSelector).is(":checked")) {
            var billingFieldSelector = "";
            var shippingFieldSelector = "";
            var billingFieldValue = "";
            var shippingFieldValue = "";
            for (i = 0; i < EcomOrderFormWithShippingAddress.fieldArray.length; ++i) {
                billingFieldSelector = EcomOrderFormWithShippingAddress.billingFieldSelector(EcomOrderFormWithShippingAddress.fieldArray[i]);
                shippingFieldSelector = EcomOrderFormWithShippingAddress.shippingFieldSelector(EcomOrderFormWithShippingAddress.fieldArray[i]);
                billingFieldValue = jQuery(billingFieldSelector).val();
                shippingFieldValue = jQuery(shippingFieldSelector).val();
                if((!shippingFieldValue && billingFieldValue) || EcomOrderFormWithShippingAddress.closed) {
                    jQuery(shippingFieldSelector).val(billingFieldValue).change();
                }
            }
        }
    },


    billingFieldSelector: function(name) {
        return ""+
            EcomOrderFormWithShippingAddress.formSelector+" input[name='"+name+"'], "+
            EcomOrderFormWithShippingAddress.formSelector+" select[name='"+name+"'], "+
            EcomOrderFormWithShippingAddress.formSelector+" textarea[name='"+name+"']";
    },

    shippingFieldSelector: function(name) {
        name = name.replace("Billing", "");
        return ""+
            EcomOrderFormWithShippingAddress.formSelector+" input[name='Shipping"+name+"'], "+
            EcomOrderFormWithShippingAddress.formSelector+" select[name='Shipping"+name+"'], "+
            EcomOrderFormWithShippingAddress.formSelector+" textarea[name='Shipping"+name+"']";
    },

    billingSelector: function(name) {
        name = name.replace("Shipping", "");
        name = name.replace("Billing", "");
        return ""+
            EcomOrderFormWithShippingAddress.formSelector+"_"+name;
    },

    shippingSelector: function(name) {
        name = name.replace("Billing", "");
        name = name.replace("Shipping", "");
        return ""+
            EcomOrderFormWithShippingAddress.formSelector+"_Shipping"+name;
    },



    //this function exists, because FF was auto-completing
    //Shipping City as the username part of a password / username combination (password being the next field)
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
