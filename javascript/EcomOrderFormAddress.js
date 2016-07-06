/**
 * @author Nicolaas [at] sunnysideup.co.nz
 *
 * This helps with the OrderForm Address
 *
 *
 * TO DO: set up a readonly system
 *
 **/
;
if(
     (document.getElementById("OrderFormAddress_OrderFormAddress") !== null && typeof document.getElementById("OrderFormAddress_OrderFormAddress") !== "undefined")
) {
    (function($) {
        $(document).ready(
            function() {
                EcomOrderFormAddress.init();
            }
        );
    })(jQuery);



    var EcomOrderFormAddress = {

        formSelector: "#OrderFormAddress_OrderFormAddress",

        postalCodeLink: ".postalCodeLink",

        postalCodeLinkTarget: "_postalcode",

        addJSValidation: false,

        init: function() {
            this.postalCodeLinkSetup();
            jQuery(this.formSelector).attr('autocomplete', 'off');
            if(this.addJSValidation) {
                jQuery(this.formSelector).attr("novalidate", "novalidate");
                jQuery(document).on(
                    "submit",
                    EcomOrderFormAddress.formSelector,
                    function(){
                        var isFormValid = true;
                        jQuery(EcomOrderFormAddress.formSelector + " input[required='required'], " + EcomOrderFormAddress.formSelector + " input[required='required'], ").each(
                            function(i, el){ // Note the :text
                                if (jQuery.trim(jQuery(el).val()).length == 0){
                                    jQuery(el).parents("div.field").addClass("holder-bad");
                                    if(jQuery(el).is(":visible")) {

                                    }
                                    isFormValid = false;
                                }
                                else {
                                    jQuery(el).parents("div.field").removeClass("holder-bad");
                                }
                        });
                        return isFormValid;
                });
            }

        },

        setReadOnly: function(fieldName) {
            jQuery("name=['"+fieldName+"']").attr("disabled", true);
            jQuery("name=['"+fieldName+"']").attr("readonly", true);
        },

        undoReadOnly: function(fieldName) {
            jQuery("name=['"+fieldName+"']").attr("disabled", false);
            jQuery("name=['"+fieldName+"']").attr("readonly", false);
        },


        postalCodeLinkSetup: function() {
            jQuery(EcomOrderFormAddress.postalCodeLink).attr("target", EcomOrderFormAddress.postalCodeLinkTarget);
        }
    }
}
