/**
 * @author Nicolaas [at] sunnysideup.co.nz
 *
 * This helps with the OrderForm Address
 *
 *
 * @todoset up a readonly system
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
    })(window.jQuery);



    var EcomOrderFormAddress = {

        formSelector: "#OrderFormAddress_OrderFormAddress",

        postalCodeLink: ".postalCodeLink",

        postalCodeLinkTarget: "_postalcode",

        addJSValidation: false,

        init: function() {
            this.postalCodeLinkSetup();
            window.jQuery(this.formSelector).attr('autocomplete', 'off');
            if(this.addJSValidation) {
                window.jQuery(this.formSelector).attr("novalidate", "novalidate");
                window.jQuery(document).on(
                    "submit",
                    EcomOrderFormAddress.formSelector,
                    function(){
                        var isFormValid = true;
                        window.jQuery(EcomOrderFormAddress.formSelector + " input[required='required'], " + EcomOrderFormAddress.formSelector + " input[required='required'], ").each(
                            function(i, el){ // Note the :text
                                if (window.jQuery.trim(window.jQuery(el).val()).length == 0){
                                    window.jQuery(el).parents("div.field").addClass("holder-bad");
                                    if(window.jQuery(el).is(":visible")) {

                                    }
                                    isFormValid = false;
                                }
                                else {
                                    window.jQuery(el).parents("div.field").removeClass("holder-bad");
                                }
                        });
                        return isFormValid;
                });
            }

        },

        setReadOnly: function(fieldName) {
            window.jQuery("name=['"+fieldName+"']").attr("disabled", true);
            window.jQuery("name=['"+fieldName+"']").attr("readonly", true);
        },

        undoReadOnly: function(fieldName) {
            window.jQuery("name=['"+fieldName+"']").attr("disabled", false);
            window.jQuery("name=['"+fieldName+"']").attr("readonly", false);
        },


        postalCodeLinkSetup: function() {
            window.jQuery(EcomOrderFormAddress.postalCodeLink).attr("target", EcomOrderFormAddress.postalCodeLinkTarget);
        }
    }
}
