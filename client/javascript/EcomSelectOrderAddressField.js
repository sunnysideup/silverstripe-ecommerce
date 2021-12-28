/**
 * @TODO: turn into a function....
 *
 *
 *
 *
 *
 *
 *
 *
 *
 */

    ;(function($) {
        $(document).ready(
            function() {
                if(typeof EcomSelectOrderAddressFieldOptions !== 'undefined') {
                    for(var i = 0; i < EcomSelectOrderAddressFieldOptions.length; i++) {
                        EcomSelectOrderAddressField.set_data(EcomSelectOrderAddressFieldOptions[i].id, EcomSelectOrderAddressFieldOptions[i].address)
                    }
                    EcomSelectOrderAddressField.init();
                }
            }
        );
    })(window.jQuery);



    var EcomSelectOrderAddressField = {

        /**
         * selector for the "select address field"
         * @var String
         */
        fieldSelector: ".selectorderaddress",

        /**
         * selector for the "select address field" input element
         * @var String
         */
        inputSelector: "input[class='radio']",

        /**
         * selector for the related address field holder
         * @var String
         */
        addressSelector: ".orderAddressHolder",

        /**
         * selector for the link that removes an 'obsolete' address
         * @var String
         */
        removeLinkSelector: ".noLongerInUse",

        /**
         * class used to show that something is being loaded
         * @var String
         */
        loadingClass: "loading",


        /**
         * message shown before an address is removed
         * @var String
         */
        areYouSureMessage: "Are you sure you want to remove this address?",

        /**
         * array of data connected to each "selectable" address
         * @var Array
         */
        data: [],
            set_data: function(i, object) {EcomSelectOrderAddressField.data[i] = object; },

        init: function() {
            if(window.jQuery(EcomSelectOrderAddressField.fieldSelector).length > 0) {
                EcomSelectOrderAddressField.setupAddressChanges();
                EcomSelectOrderAddressField.setupNoLongerInUseLinks();
            }

        },

        setupAddressChanges: function(){
            window.jQuery(EcomSelectOrderAddressField.fieldSelector).each(
                function(i, el){
                    //window.jQuery(el).next(EcomSelectOrderAddressField.addressSelector).hide();
                    window.jQuery(el).find(EcomSelectOrderAddressField.inputSelector).each(
                        function(i, el) {
                            window.jQuery(el).change(
                                function(e) {
                                    //window.jQuery(this).parents(EcomSelectOrderAddressField.fieldSelector).next(EcomSelectOrderAddressField.addressSelector).show();
                                    var id = window.jQuery(this).val();
                                    window.jQuery(this).closest("ul").children("li").removeClass("selected");
                                    window.jQuery(this).closest("li").addClass("selected");
                                    var data = EcomSelectOrderAddressField.data[id];
                                    if(data) {
                                        window.jQuery.each(
                                            data,
                                            function(i, n){
                                                window.jQuery("input[name='"+i+"'], select[name='"+i+"'], textarea[name='"+i+"']").val(n);
                                            }
                                        );
                                    }
                                }
                            );
                        }
                    );
                    //must do the after setting change event.
                    window.jQuery(this).find("input:first").click();
                }
            );
        },

        setupNoLongerInUseLinks: function(){
            window.jQuery(EcomSelectOrderAddressField.removeLinkSelector).click(
                function(e) {
                    e.preventDefault();
                    window.jQuery(this).addClass(EcomSelectOrderAddressField.loadingClass);
                    var id = window.jQuery(this).attr("rel");
                    var url = window.jQuery(this).attr("href");
                    window.jQuery.get(
                        url,
                        function(data){
                            window.jQuery(".val"+id).addClass("removed");
                            window.jQuery(".val"+id+" input").remove();
                            window.jQuery(".val"+id+" label").html(data);
                            window.jQuery(this).removeClass(EcomSelectOrderAddressField.loadingClass);
                        }
                    );
                }
            );
        }



    }

