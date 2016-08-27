/**
  * @description: update cart using AJAX
  * This JS attaches to the Quantity Field.
  *
  * @TODO: turn into a function.
  *
  */
;
(function($){
    $(document).ready(
        function () {
            EcomQuantityField.init();
        }
    );
})(jQuery);

if(typeof require === 'undefined') {
    var MyEcomCart = EcomCart;
}

var EcomQuantityField = {

    //todo: make more specific! some selector that holds true for all cart holders.
    hidePlusAndMinues: true,

    delegateRootSelector: 'body',
        set_delegateRootSelector: function(s) {this.delegateRootSelector = s;},
        unset_delegateRootSelector: function() {this.delegateRootSelector = 'body';},

    mainSelector: ".ecomquantityfield",

    quantityFieldSelector: "input.ajaxQuantityField",

    removeSelector: " a.removeOneLink",

    addSelector: " a.addOneLink",

    completedClass: "ajaxCompleted",

    URLSegmentHiddenFieldSelectorAppendix: "_SetQuantityLink",

    updateFX: [],

    lastValue: [],

    EcomCart: {},

    init: function (){
        if(typeof EcomCart === "undefined" && typeof require !== 'undefined') {
            var EcomCart = require("./EcomCartWebPack");
            this.EcomCart = EcomCart.EcomCart;
        }
        else {
            this.EcomCart = MyEcomCart;
        }
        this.EcomCart.reinitCallbacks.push(EcomQuantityField.reinit);
        //make sure it only runs if needed...
        if(jQuery(EcomQuantityField.delegateRootSelector).length > 0) {
            jQuery(EcomQuantityField.delegateRootSelector).on(
                "click",
                EcomQuantityField.mainSelector + " " + EcomQuantityField.removeSelector,
                function(e) {
                    EcomQuantityField.updateFX[jQuery(this).attr("name")] = null;
                    e.preventDefault();
                    var inputField = jQuery(this).siblings(EcomQuantityField.quantityFieldSelector);
                    jQuery(inputField).val(parseFloat(jQuery(inputField).val())-1).change();
                    return false;
                }
            );
            jQuery(EcomQuantityField.delegateRootSelector).on(
                "click",
                EcomQuantityField.mainSelector + " " + EcomQuantityField.addSelector,
                function(e) {
                    e.preventDefault();
                    EcomQuantityField.updateFX[jQuery(this).attr("name")] = null;
                    var inputField = jQuery(this).siblings(EcomQuantityField.quantityFieldSelector);
                    jQuery(inputField).val(parseFloat(jQuery(inputField).val())+1).change();
                    return false;
                }
            );
            jQuery(EcomQuantityField.delegateRootSelector).on(
                "focus",
                EcomQuantityField.mainSelector + " " + EcomQuantityField.quantityFieldSelector,
                function(){
                    EcomQuantityField.lastValue[jQuery(this).attr("name")] = jQuery(this).val();
                }
            );
            jQuery(EcomQuantityField.delegateRootSelector).on(
                "keydown",
                EcomQuantityField.mainSelector + " " + EcomQuantityField.quantityFieldSelector,
                function(){
                    var el = this;
                    EcomQuantityField.updateFX[jQuery(this).attr("name")] = window.setTimeout(
                        function(){
                            if(EcomQuantityField.lastValue[jQuery(el).attr("name")] != jQuery(el).val()) {
                                jQuery(el).change();
                            }
                        },
                        1000
                    );
                }
            );
            jQuery(EcomQuantityField.delegateRootSelector).on(
                "change",
                EcomQuantityField.mainSelector + " " + EcomQuantityField.quantityFieldSelector,
                function() {
                    EcomQuantityField.updateFX[jQuery(this).attr("name")] = null;
                    var URLSegment = EcomQuantityField.getSetQuantityURLSegment(this);
                    if(URLSegment.length > 0) {
                        this.value = this.value.replace(/[^0-9.]+/g, '');
                        if(this.value == 0 || !this.value) {
                            this.value = 1;
                        }
                        if(this.value < 2) {
                            jQuery(this).siblings(EcomQuantityField.removeSelector).css("visibility", "hidden");
                        }
                        else {
                            jQuery(this).siblings(EcomQuantityField.removeSelector).css("visibility", "visible");
                        }
                        if(EcomQuantityField.lastValue[jQuery(this).attr("name")] != jQuery(this).val()) {
                            EcomQuantityField.lastValue[jQuery(this).attr("name")] = jQuery(this).val();
                            if(URLSegment.indexOf("?") == -1) {
                                URLSegment = URLSegment + "?";
                            }
                            else {
                                URLSegment = URLSegment + "&";
                            }
                            var url = jQuery('base').attr('href') + URLSegment + 'quantity=' + this.value;
                            url = url.replace("&amp;", "&");
                            if(typeof EcomQuantityField.EcomCart !== 'undefined') {
                                EcomQuantityField.EcomCart.getChanges(url, null, this);
                            } else if(typeof EcomCart !== "undefined") {
                                EcomCart.getChanges(url, null, this);
                            } else {
                                alert("Sorry, changes could not be saved.");
                                window.location = url;
                            }
                        }
                    }
                }
            );
            jQuery(EcomQuantityField.delegateRootSelector).on(
                "blur",
                EcomQuantityField.mainSelector + " " + EcomQuantityField.quantityFieldSelector,
                function() {
                    EcomQuantityField.updateFX[jQuery(this).attr("name")] = null;
                }
            );

            /////// IMPORTANT /////
            EcomQuantityField.reinit();
        }
    },

    //todo: auto-re-attach
    reinit: function () {
        jQuery(EcomQuantityField.delegateRootSelector).find(EcomQuantityField.mainSelector).each(
            function(i, el) {
                if(!jQuery(el).hasClass(EcomQuantityField.completedClass)) {
                    if(EcomQuantityField.hidePlusAndMinues) {
                        jQuery(el).find(EcomQuantityField.removeSelector).hide();
                        jQuery(el).find(EcomQuantityField.addSelector).hide();
                    }
                    jQuery(el).addClass(EcomQuantityField.completedClass);
                    jQuery(el).find(EcomQuantityField.quantityFieldSelector).removeAttr("disabled");
                }
            }
        );
    },

    getSetQuantityURLSegment: function (inputField) {
        var name = jQuery(inputField).attr('name')+EcomQuantityField.URLSegmentHiddenFieldSelectorAppendix ;
        if(jQuery('[name=' + name + ']').length > 0) {
            return jQuery('[name=' + name + ']').val();
        };
        //backup!
        return jQuery(inputField).attr("data-quantity-link");
    },

    debug: function() {
        jQuery(EcomQuantityField.addSelector).css("border", "3px solid red");
        jQuery(EcomQuantityField.removeSelector).css("border", "3px solid red");
        jQuery(EcomQuantityField.quantityFieldSelector).css("border", "3px solid red");
    }
}
