/**
    * @description:
    * This class provides extra functionality for the
    * Product and ProductGroup Page.
    * @author nicolaas @ sunny side up . co . nz
    **/
(function($){
    $(document).ready(
        function() {
            EcomProducts.init();
            EcomProducts.reinit();
        }
    );
})(jQuery);

EcomProducts = {

    EcomCart: null,

    //see: http://www.jacklmoore.com/colorbox/
    colorboxDialogOptions_addVariations: {
        height: "95%",
        width: "95%",
        maxHeight: "95%",
        maxWidth: "95%",
        loadingClass: "loading",
        iframe: false,
        model: true,
        onComplete: function (event) {
            if(typeof EcomCart === "undefined" ) {
                var EcomCart = require("./EcomCartWebPack");
                EcomProducts.EcomCart = EcomCart.EcomCart;
            } else {
                EcomProducts.EcomCart = EcomCart;
            }
            EcomProducts.EcomCart.reinit();
        }
    },

    //see: http://www.jacklmoore.com/colorbox/
    colorboxDialogOptions_viewImages: {},

    selectVariationSelector: 'a.selectVariation',

    imagePopupSelector: '.colorboxImagePopup',

    openCloseSectionLinkSelector: "a.openCloseSectionLink",

    openCloseSectionSelector: "div.openCloseSection",

    openClass: "open",

    closeClass: "close",

    init: function(){
        //pop-up for selections
        jQuery(document).on(
            "click",
            EcomProducts.selectVariationSelector,
            function (e) {
                EcomProducts.colorboxDialogOptions_addVariations.href = jQuery(this).attr('href');
                EcomProducts.colorboxDialogOptions_addVariations.open = true;
                jQuery.colorbox(EcomProducts.colorboxDialogOptions_addVariations);
                return false;
            }
        );
        //pop-up for images
        jQuery(document).on(
            "click",
            EcomProducts.imagePopupSelector,
            function (e) {
                EcomProducts.imagePopupSelector.href = jQuery(this).attr('href');
                EcomProducts.imagePopupSelector.open = true;
                jQuery.colorbox(EcomProducts.imagePopupSelector);
                return false;
            }
        );
        //filter sort display tabs
        jQuery(document).on(
            "click",
            EcomProducts.openCloseSectionLinkSelector,
            function(event) {
                event.preventDefault();
                var id = EcomProducts.findID(this);
                //close the others that are open if the current one is about to open ...
                if(jQuery(this).hasClass(EcomProducts.closeClass)) {
                    jQuery(EcomProducts.openCloseSectionLinkSelector).each(
                        function(i, el) {
                            if(jQuery(el).hasClass(EcomProducts.openClass)) {
                                jQuery(el).click();
                            }
                        }
                    )
                }
                jQuery(this).toggleClass(EcomProducts.closeClass).toggleClass(EcomProducts.openClass);
                jQuery(id).slideToggle().toggleClass(EcomProducts.closeClass).toggleClass(EcomProducts.openClass);
            }
        );

    },


    reinit: function(){
        var thereIsOnlyOne = false;
        if(jQuery(EcomProducts.openCloseSectionLinkSelector).length == 1) {
            thereIsOnlyOne = true;
        }
        jQuery(".close.openCloseSection").css("display", "none");
        if(thereIsOnlyOne) {
            jQuery(EcomProducts.openCloseSectionLinkSelector).click();
        }
    },

    findID: function(el) {
        var id = jQuery(el).attr("href");
        var idLength = id.length;
        var hashPosition = id.indexOf("#");
        return id.substr(id.indexOf("#"), idLength - hashPosition);
    }


}



jQuery(EcomProducts.openCloseSectionSelector).css("display", "none");
