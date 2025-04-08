/**
 * @description:
 * This class provides extra functionality for the
 * Product and ProductGroup Page.
 * @author nicolaas @ sunny side up . co . nz
 **/
;(function ($) {
  $(document).ready(function () {
    EcomProducts.init()
    EcomProducts.reinit()
  })
})(window.jQuery)

EcomProducts = {
  EcomCart: null,

  //see: http://www.jacklmoore.com/colorbox/
  colorboxDialogOptions_addVariations: {
    height: '95%',
    width: '95%',
    maxHeight: '95%',
    maxWidth: '95%',
    loadingClass: 'loading',
    iframe: false,
    model: true,
    onComplete: function (event) {
      if (typeof EcomCart === 'undefined') {
        // var EcomCart = require("./EcomCart");
        EcomProducts.EcomCart = EcomCart.EcomCart
      } else {
        EcomProducts.EcomCart = EcomCart
      }
      EcomProducts.EcomCart.reinit()
    }
  },

  //see: http://www.jacklmoore.com/colorbox/
  colorboxDialogOptions_viewImages: {},

  selectVariationSelector: 'a.selectVariation',

  imagePopupSelector: '.colorboxImagePopup',

  openCloseSectionLinkSelector: 'a.openCloseSectionLink',

  openCloseSectionSelector: 'div.openCloseSection',

  openClass: 'open',

  closeClass: 'close',

  init: function () {
    //pop-up for selections
    window
      .jQuery(document)
      .on('click', EcomProducts.selectVariationSelector, function (e) {
        EcomProducts.colorboxDialogOptions_addVariations.href = window
          .jQuery(this)
          .attr('href')
        EcomProducts.colorboxDialogOptions_addVariations.open = true
        window.jQuery.colorbox(EcomProducts.colorboxDialogOptions_addVariations)
        return false
      })
    //pop-up for images
    window
      .jQuery(document)
      .on('click', EcomProducts.imagePopupSelector, function (e) {
        EcomProducts.imagePopupSelector.href = window.jQuery(this).attr('href')
        EcomProducts.imagePopupSelector.open = true
        window.jQuery.colorbox(EcomProducts.imagePopupSelector)
        return false
      })
    //filter sort display tabs
    window
      .jQuery(document)
      .on('click', EcomProducts.openCloseSectionLinkSelector, function (event) {
        event.preventDefault()
        var id = EcomProducts.findID(this)
        //close the others that are open if the current one is about to open ...
        if (window.jQuery(this).hasClass(EcomProducts.closeClass)) {
          window
            .jQuery(EcomProducts.openCloseSectionLinkSelector)
            .each(function (i, el) {
              if (window.jQuery(el).hasClass(EcomProducts.openClass)) {
                window.jQuery(el).click()
              }
            })
        }
        window
          .jQuery(this)
          .toggleClass(EcomProducts.closeClass)
          .toggleClass(EcomProducts.openClass)
        window
          .jQuery(id)
          .slideToggle()
          .toggleClass(EcomProducts.closeClass)
          .toggleClass(EcomProducts.openClass)
      })
  },

  reinit: function () {
    var thereIsOnlyOne = false
    if (window.jQuery(EcomProducts.openCloseSectionLinkSelector).length == 1) {
      thereIsOnlyOne = true
    }
    window.jQuery('.close.openCloseSection').css('display', 'none')
    if (thereIsOnlyOne) {
      window.jQuery(EcomProducts.openCloseSectionLinkSelector).click()
    }
  },

  findID: function (el) {
    var id = window.jQuery(el).attr('href')
    var idLength = id.length
    var hashPosition = id.indexOf('#')
    return id.substr(id.indexOf('#'), idLength - hashPosition)
  }
}

window.jQuery(EcomProducts.openCloseSectionSelector).css('display', 'none')
