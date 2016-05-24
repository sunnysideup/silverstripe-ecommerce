/**
 *@author Nicolaas [at] sunnysideup.co.nz
 *
 * TO DO: set up a readonly system
 *
 **/
;
if(
     (document.getElementById("OrderFormAddress_OrderFormAddress_PasswordCheck1_Holder") !== null && typeof document.getElementById("OrderFormAddress_OrderFormAddress_PasswordCheck1_Holder") !== "undefined")
) {

    (function($) {
        $(document).ready(
            function() {
                EcomPasswordField.init();
            }
        );
    })(jQuery);



    var EcomPasswordField = {

        chars : "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz",

        passwordFieldInputSelectors: "#OrderFormAddress_OrderFormAddress_PasswordCheck1_Holder, #OrderFormAddress_OrderFormAddress_PasswordCheck2_Holder",

        errorMessageSelector: "#OrderFormAddress_OrderFormAddress_PasswordCheck1_Holder span.message, #OrderFormAddress_OrderFormAddress_PasswordCheck2_Holder span.message",

        choosePasswordLinkSelector: ".choosePassword, .updatePasswordLink",

        stringLength : 14,

        //toggles password selection and enters random password so that users still end up with a password
        //even if they do not choose one.
        init: function() {
            var yesLabel = jQuery(EcomPasswordField.choosePasswordLinkSelector).text();
            if(!jQuery(EcomPasswordField.choosePasswordLinkSelector).attr("datayes")) {
                jQuery(EcomPasswordField.choosePasswordLinkSelector).attr("datayes", yesLabel);
            }

            if(jQuery(EcomPasswordField.passwordFieldInputSelectors).length) {
                jQuery(document).on(
                    "click",
                    EcomPasswordField.choosePasswordLinkSelector,
                    function() {
                        jQuery(EcomPasswordField.passwordFieldInputSelectors).slideToggle(
                            function(){
                                if(jQuery(EcomPasswordField.passwordFieldInputSelectors).is(':visible')) {
                                    var newPassword = '';
                                    var newLabel = jQuery(EcomPasswordField.choosePasswordLinkSelector).attr("datano");
                                }
                                else{
                                    var newPassword = EcomPasswordField.passwordGenerator();
                                    var newLabel = jQuery(EcomPasswordField.choosePasswordLinkSelector).attr("datayes");
                                }
                                jQuery(EcomPasswordField.choosePasswordLinkSelector).text(newLabel);
                                jQuery(EcomPasswordField.passwordFieldInputSelectors).each(
                                    function(i, el) {
                                        jQuery(el).find("input").val(newPassword);
                                    }
                                );
                            }
                        );
                        return false;
                    }
                );
                jQuery(EcomPasswordField.choosePasswordLinkSelector).click();
            }

            jQuery("form").on(
                "click",
                ".Actions input",
                function() {
                    var notAllHaveSomething = false
                    //reset to avoid auto-fills
                    jQuery(EcomPasswordField.passwordFieldInputSelectors).each(
                        function(i, el) {
                            if(jQuery(el).find("input").val() == "" || jQuery(el).is(":hidden")) {
                                notAllHaveSomething = true;
                            }
                        }
                    );
                    if(notAllHaveSomething) {
                        jQuery(EcomPasswordField.passwordFieldInputSelectors).each(
                            function(i, el) {
                                jQuery(el).find("input").val("");
                            }
                        );
                    }
                }
            );
            //show passwords straight away IF there is an error
            if(jQuery(EcomPasswordField.errorMessageSelector).length){
                jQuery(EcomPasswordField.choosePasswordLinkSelector).click();
            }
        },

        //generates random password
        passwordGenerator: function() {
            return '';
            var randomstring = '';
            for (var i=0; i < this.stringLength; i++) {
                var rnum = Math.floor(Math.random() * this.chars.length);
                randomstring += this.chars.substring(rnum,rnum+1);
            }
            return randomstring;
        }


    }
}
