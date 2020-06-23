/**
    * @description:
    * This class provides extra functionality for the
    * printing and emailing orders.
    * @author nicolaas @ sunny side up . co . nz
    **/

;
if(
    (document.getElementById("OrderPrintAndMail") !== null && typeof document.getElementById("OrderPrintAndMail") !== "undefined")
) {
    (function($){
        $(document).ready(
            function() {
                EcomPrintAndMail.init();
            }
        );
    })(jQuery);

    var EcomPrintAndMail = {

        selectors: "#OrderPrintAndMail a",

        init: function() {
            jQuery(EcomPrintAndMail.selectors).on(
                "click",
                function(e) {
                    e.preventDefault();
                    var id = jQuery(this).parent("li").attr("id");
                    var url = jQuery(this).attr("href");
                    window.open(url, id, 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=1,resizable=1,width=600,height=400,left = 50,top = 50');
                }
            )
        }
    }
}
