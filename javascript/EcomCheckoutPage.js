/**
 *@author Nicolaas [at] sunnysideup.co.nz
 * adds JS functionality to the checkout page
 * makes all the previous and next buttons and forms ajax
 *
 **/
if(
    (document.getElementById("Checkout") !== null && typeof document.getElementById("Checkout") !== "undefined")
) {
    (function($) {
        $(document).ready(
            function() {
                EcomCheckoutPage.init();
            }
        );
    })(jQuery);


    var EcomCheckoutPage = {

        nextPreviousOverviewListSelector: "ol.steps a",

        nextPreviousButtonsSelector: ".checkoutStepPrevNextHolder a",

        outerHolderSelector: "#Checkout",

        formsSelectors: "#OrderFormAddress_OrderFormAddress",

        loadingClass: "loading",

        init: function(){
            EcomCheckoutPage.makeAllPreviousAndNextAjaxified();
            EcomCheckoutPage.makeAllFormsAjaxified();
        },

        makeAllPreviousAndNextAjaxified: function(){
            jQuery("body").on(
                "click",
                EcomCheckoutPage.nextPreviousOverviewListSelector+", "+EcomCheckoutPage.nextPreviousButtonsSelector,
                function(event){
                    event.preventDefault();
                    var href = jQuery(this).attr("href");
                    jQuery(EcomCheckoutPage.outerHolderSelector).fadeOut(
                        function() {
                            jQuery(EcomCheckoutPage.outerHolderSelector).addClass(EcomCheckoutPage.loadingClass);
                            var jqxhr = jQuery.ajax(
                                {
                                    url: href,
                                    settings: {
                                        cache: false
                                    }
                                }
                            )
                            .done(
                                function( data, textStatus, jqXHR ) {
                                    EcomCheckoutPage.attachCSSAndJSHeader(jqXHR);
                                    jQuery(EcomCheckoutPage.outerHolderSelector).html(data);
                                }
                            )
                            .fail(
                                function() {
                                    window.location = href;
                                }
                            )
                            .always(
                                function() {
                                    jQuery(EcomCheckoutPage.outerHolderSelector).fadeIn(
                                        function() {
                                            jQuery(EcomCheckoutPage.outerHolderSelector).removeClass(EcomCheckoutPage.loadingClass);
                                        }
                                    );
                                }
                            );
                        }
                    );
                    return false;
                }
            );
        },

        makeAllFormsAjaxified: function(){
        var options = {
            target:        EcomCheckoutPage.outerHolderSelector,   // target element(s) to be updated with server response
            beforeSubmit:  EcomCheckoutPage.showRequestFromForm,  // pre-submit callback
            success:       EcomCheckoutPage.showResponseFromForm,  // post-submit callback
            error:         EcomCheckoutPage.handleFormError  // post-submit callback

            // other available options:
            //url:       url         // override for form's 'action' attribute
            //type:      type        // 'get' or 'post', override for form's 'method' attribute
            //dataType:  null        // 'xml', 'script', or 'json' (expected server response type)
            //clearForm: true        // clear all form fields after successful submit
            //resetForm: true        // reset the form after successful submit

            // $.ajax options can be used here too, for example:
            //timeout:   3000
        };
         // bind form using 'ajaxForm'
        jQuery(EcomCheckoutPage.formsSelectors).ajaxForm(options);
         },


        // pre-submit callback
        showRequestFromForm: function(formData, jqForm, options) {
            // formData is an array; here we use $.param to convert it to a string to display it
            // but the form plugin does this for you automatically when it submits the data
            var queryString = $.param(formData);

            // jqForm is a jQuery object encapsulating the form element.  To access the
            // DOM element for the form do this:
            // var formElement = jqForm[0];

            alert('About to submit: \n\n' + queryString);

            // here we could return false to prevent the form from being submitted;
            // returning anything other than false will allow the form submit to continue
            return true;
        },

        // post-submit callback
        showResponseFromForm: function(responseText, statusText, xhr, $form)  {
            // for normal html responses, the first argument to the success callback
            // is the XMLHttpRequest object's responseText property

            // if the ajaxForm method was passed an Options Object with the dataType
            // property set to 'xml' then the first argument to the success callback
            // is the XMLHttpRequest object's responseXML property

            // if the ajaxForm method was passed an Options Object with the dataType
            // property set to 'json' then the first argument to the success callback
            // is the json data object returned by the server

            alert('status: ' + statusText + '\n\nresponseText: \n' + responseText +
                    '\n\nThe output div should have already been updated with the responseText.');
            EcomCheckoutPage.attachCSSAndJSHeader(xhr);
        },

        attachCSSAndJSHeader: function(jqXHRResponse) {
            var headers = EcomCheckoutPage.parseResponseHeaders(jqXHRResponse.getAllResponseHeaders());
            console.debug(headers);
            var CSSArray = headers["X-Include-CSS"].split(",");
            console.debug(headers["X-Include-CSS"]);
            console.debug(CSSArray);
            if(CSSArray.length > 0) {
                jQuery.each(
                    CSSArray,
                    function( index, value ) {
                        jQuery('<link>')
                            .appendTo('head')
                            .attr({type : 'text/css', rel : 'stylesheet'})
                            .attr('href', value);
                    }
                );
            }
            var JSArray = headers["X-Include-JS"].split(",");
            if(JSArray.length > 0) {
                jQuery.each(
                    JSArray,
                    function( index, value ) {
                        jQuery.getScript( value);
                    }
                );
            }
            EcomQuantityField.reinit();
        },

        handleFormError: function(){
            alert("Error Occured, please try again");
        },

        /**
         * XmlHttpRequest's getAllResponseHeaders() method returns a string of response
         * headers according to the format described here:
         * http://www.w3.org/TR/XMLHttpRequest/#the-getallresponseheaders-method
         * This method parses that string into a user-friendly key/value pair object.
         */
        parseResponseHeaders: function(headerStr) {
            var headers = {};
            if (!headerStr) {
                return headers;
            }
            var headerPairs = headerStr.split('\u000d\u000a');
            for (var i = 0; i < headerPairs.length; i++) {
                var headerPair = headerPairs[i];
                // Can't use split() here because it does the wrong thing
                // if the header value has the string ": " in it.
                var index = headerPair.indexOf('\u003a\u0020');
                if (index > 0) {
                    var key = headerPair.substring(0, index);
                    var val = headerPair.substring(index + 2);
                    headers[key] = val;
                }
            }
            return headers;
        }

    }
}
