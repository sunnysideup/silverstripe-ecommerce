/**
 *@author nicolaas[at]sunnysideup.co.nz
 * This adds functionality to the shipping address section of the checkout form
 *
 **/
;
if(
      (document.getElementById("OrderFormAddress_OrderFormAddress") !== null && typeof document.getElementById("OrderFormAddress_OrderFormAddress") !== "undefined")
) {
    (function($) {
        $(document).ready(
            function() {
                EcomOrderFormWithShippingAddress.init();
                EcomOrderFormWithShippingAddress.removeEmailFromShippingCityHack();
            }
        );

    })(jQuery);


    var EcomOrderFormWithShippingAddress = {

        /**
         * array of field names
         * @var array
         */
        fieldArray: [],

        /**
         * array of selectors to select shipping fields
         * @var string
         */
        shippingFieldSelectors: "",

        /**
         * array of selectors to select billing fields
         * @var string
         */
        billingFieldSelectors: "",

        /**
         * selector for form
         * @var string
         */
        formSelector: "#OrderFormAddress_OrderFormAddress",

        /**
         * selector for shipping form section
         * @var string
         */
        shippingSectionSelector: ".shippingFieldsHeader, .shippingFields",

        /**
         * selector for the checkbox that shows wheter or not
         * the shipping address is different
         *
         * @var string
         */
        useShippingDetailsSelector: "input[name='UseShippingAddress']",

        /**
         * Geocoding field ...
         *
         * @var string
         */
        shippingGeoCodingFieldSelector: "input[name='ShippingEcommerceGeocodingField']",

        /**
         * is the shipping field closed...
         *
         */
        closed: false,

        //hides shipping fields
        //toggle shipping fields when "use separate shipping address" is ticked
        //update shipping fields, when billing fields are changed.
        init: function(){


            if(jQuery(EcomOrderFormWithShippingAddress.useShippingDetailsSelector).length > 0) {

                this.getListOfSharedFields();

                var i;
                for (i = 0; i < EcomOrderFormWithShippingAddress.fieldArray.length; ++i) {
                    if((i  + 1) < EcomOrderFormWithShippingAddress.fieldArray.length) {
                        EcomOrderFormWithShippingAddress.shippingFieldSelectors += ", ";
                        EcomOrderFormWithShippingAddress.billingFieldSelectors += ", ";
                    }
                    EcomOrderFormWithShippingAddress.shippingFieldSelectors += EcomOrderFormWithShippingAddress.shippingFieldSelector(EcomOrderFormWithShippingAddress.fieldArray[i]);
                    EcomOrderFormWithShippingAddress.billingFieldSelectors  += EcomOrderFormWithShippingAddress.billingFieldSelector(EcomOrderFormWithShippingAddress.fieldArray[i]);
                }
                //turn on listeners...
                if(i > 0) {
                    EcomOrderFormWithShippingAddress.turnOnListeners();
                    jQuery(EcomOrderFormWithShippingAddress.useShippingDetailsSelector).change();
                }
                  
            }
            //why this????
            jQuery(EcomOrderFormWithShippingAddress.shippingGeoCodingFieldSelector).removeAttr("required");
            //update one more time ... 
        },

        /**
         * copy Billing to Shipping
         *
         */
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
                    if(EcomOrderFormWithShippingAddress.closed) {
                        jQuery(shippingFieldSelector).val("");
                    }
                    else if( ! shippingFieldValue && billingFieldValue) {
                        jQuery(shippingFieldSelector).val(billingFieldValue).change();
                    }
                }
            }
            else {
                jQuery(EcomOrderFormWithShippingAddress.shippingFieldSelectors).each(
                    function(i, el) {
                        if(jQuery(el).hasClass("required")) {
                            jQuery(el)
                                .removeAttr("required")
                                .removeAttr("aria-required")
                                .removeAttr("data-has-required");
                        }
                        else {
                            //do nothing...
                        }
                    }
                );
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
        },

        /**
         *
         * get a list of fields that is potentially shared.
         */
        getListOfSharedFields: function(){

            jQuery(this.formSelector+' input, '+this.formSelector+", select"+this.formSelector+" textarea").each(
                function(i, el){
                    var name = jQuery(el).attr("name");
                    if(typeof name !== 'undefined') {
                        var billingFieldSelector = EcomOrderFormWithShippingAddress.billingFieldSelector(name);
                        if(jQuery(billingFieldSelector).length > 0) {
                            EcomOrderFormWithShippingAddress.fieldArray.push(name);
                        }
                    }
                    //your code here
            });
        },

        turnOnListeners: function(){

            //if the billing updates, the shipping updates
            jQuery(EcomOrderFormWithShippingAddress.billingFieldSelectors).change(
                function() {
                    //important ... 
                    EcomOrderFormWithShippingAddress.updateFields();
                }
            );

            //on focus of the shipping fields, look for update...
            jQuery(EcomOrderFormWithShippingAddress.shippingFieldSelectors).focus(
                function() {
                    EcomOrderFormWithShippingAddress.updateFields();
                }
            );

            //turn-on shipping details toggle
            jQuery(EcomOrderFormWithShippingAddress.useShippingDetailsSelector).change(
                function(){
                    if(jQuery(EcomOrderFormWithShippingAddress.useShippingDetailsSelector).is(":checked") === true) {


                        //slidedown
                        jQuery(EcomOrderFormWithShippingAddress.shippingSectionSelector).slideDown();

                        //focus on first field
                        var firstShippingField = EcomOrderFormWithShippingAddress.fieldArray[0];
                        jQuery(EcomOrderFormWithShippingAddress.shippingFieldSelector(firstShippingField)).focus();

                        //set required fields ...
                        jQuery(EcomOrderFormWithShippingAddress.shippingFieldSelectors).each(
                            function(i, el) {
                                if(jQuery(el).hasClass("required")) {
                                    jQuery(el)
                                        .attr("required", "required")
                                        .attr("aria-required", true)
                                        .attr("aria-required", true)
                                        .attr("data-has-required", "yes");
                                }
                                else {

                                }
                            }
                        );

                        //copy fields ...
                        EcomOrderFormWithShippingAddress.updateFields();

                        //save setting
                        EcomOrderFormWithShippingAddress.closed = false;
                    }
                    else {

                        //slide up
                        jQuery(EcomOrderFormWithShippingAddress.shippingSectionSelector).slideUp();

                        //make not required
                        jQuery(EcomOrderFormWithShippingAddress.shippingFieldSelectors).each(
                            function(i, el) {
                                if(jQuery(el).hasClass("required")) {
                                    jQuery(el)
                                        .removeAttr("required")
                                        .removeAttr("aria-required")
                                        .removeAttr("data-has-required");
                                }
                                else {
                                    //do nothing...
                                }
                            }
                        );

                        //save answer
                        EcomOrderFormWithShippingAddress.closed = true;
                    }
                    EcomOrderFormWithShippingAddress.updateFields();
                }
            );
        }
    }
}
