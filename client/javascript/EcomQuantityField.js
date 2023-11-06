/**
 * @description: update cart using AJAX
 * This JS attaches to the Quantity Field.
 *
 * @TODO: turn into a function.
 *
 */
;(function ($) {
  $(document).ready(function () {
    EcomQuantityField.init()
  })
})(window.jQuery)

if (typeof require === 'undefined') {
  var MyEcomCart = EcomCart
}

var EcomQuantityField = {
  joinWithSlash: (...strings) =>
    strings.map(str => (str.endsWith('/') ? str : `${str}/`)).join(''),

  //todo: make more specific! some selector that holds true for all cart holders.
  hidePlusAndMinues: true,

  delegateRootSelector: 'body',
  set_delegateRootSelector: function (s) {
    this.delegateRootSelector = s
  },
  unset_delegateRootSelector: function () {
    this.delegateRootSelector = 'body'
  },

  mainSelector: '.ecomquantityfield',

  quantityFieldSelector: 'input.ajaxQuantityField',

  removeSelector: ' a.removeOneLink',

  addSelector: ' a.addOneLink',

  completedClass: 'ajaxCompleted',

  URLSegmentHiddenFieldSelectorAppendix: '_SetQuantityLink',

  updateFX: [],

  lastValue: [],

  EcomCart: {},

  init: function () {
    if (typeof EcomCart === 'undefined' && typeof require !== 'undefined') {
      // var EcomCart = require("./EcomCartWebPack");
      this.EcomCart = window.EcomCart
    } else {
      this.EcomCart = EcomCart
    }
    this.EcomCart.reinitCallbacks.push(EcomQuantityField.reinit)
    //make sure it only runs if needed...
    if (window.jQuery(EcomQuantityField.delegateRootSelector).length > 0) {
      window
        .jQuery(EcomQuantityField.delegateRootSelector)
        .on(
          'click',
          EcomQuantityField.mainSelector +
            ' ' +
            EcomQuantityField.removeSelector,
          function (e) {
            EcomQuantityField.updateFX[window.jQuery(this).attr('name')] = null
            e.preventDefault()
            var inputField = window
              .jQuery(this)
              .siblings(EcomQuantityField.quantityFieldSelector)
            window
              .jQuery(inputField)
              .val(parseFloat(window.jQuery(inputField).val()) - 1)
              .change()
            return false
          }
        )
      window
        .jQuery(EcomQuantityField.delegateRootSelector)
        .on(
          'click',
          EcomQuantityField.mainSelector + ' ' + EcomQuantityField.addSelector,
          function (e) {
            e.preventDefault()
            EcomQuantityField.updateFX[window.jQuery(this).attr('name')] = null
            var inputField = window
              .jQuery(this)
              .siblings(EcomQuantityField.quantityFieldSelector)
            window
              .jQuery(inputField)
              .val(parseFloat(window.jQuery(inputField).val()) + 1)
              .change()
            return false
          }
        )
      window
        .jQuery(EcomQuantityField.delegateRootSelector)
        .on(
          'focus',
          EcomQuantityField.mainSelector +
            ' ' +
            EcomQuantityField.quantityFieldSelector,
          function () {
            EcomQuantityField.lastValue[window.jQuery(this).attr('name')] =
              window.jQuery(this).val()
          }
        )
      window
        .jQuery(EcomQuantityField.delegateRootSelector)
        .on(
          'keydown',
          EcomQuantityField.mainSelector +
            ' ' +
            EcomQuantityField.quantityFieldSelector,
          function () {
            var el = this
            EcomQuantityField.updateFX[window.jQuery(this).attr('name')] =
              window.setTimeout(function () {
                if (
                  EcomQuantityField.lastValue[window.jQuery(el).attr('name')] !=
                  window.jQuery(el).val()
                ) {
                  window.jQuery(el).change()
                }
              }, 1000)
          }
        )
      window
        .jQuery(EcomQuantityField.delegateRootSelector)
        .on(
          'change',
          EcomQuantityField.mainSelector +
            ' ' +
            EcomQuantityField.quantityFieldSelector,
          function () {
            EcomQuantityField.updateFX[window.jQuery(this).attr('name')] = null
            var URLSegment = EcomQuantityField.getSetQuantityURLSegment(this)
            if (URLSegment.length > 0) {
              this.value = this.value.replace(/[^0-9.]+/g, '')
              if (this.value == 0 || !this.value) {
                this.value = 1
              }
              if (this.value < 2) {
                window
                  .jQuery(this)
                  .siblings(EcomQuantityField.removeSelector)
                  .css('visibility', 'hidden')
              } else {
                window
                  .jQuery(this)
                  .siblings(EcomQuantityField.removeSelector)
                  .css('visibility', 'visible')
              }

              if (
                EcomQuantityField.lastValue[window.jQuery(this).attr('name')] !=
                window.jQuery(this).val()
              ) {
                EcomQuantityField.lastValue[window.jQuery(this).attr('name')] =
                  window.jQuery(this).val()
                if (URLSegment.indexOf('?') == -1) {
                  URLSegment = URLSegment + '?'
                } else {
                  URLSegment = URLSegment + '&'
                }
                var url = EcomQuantityField.joinWithSlash(
                  window.jQuery('base').attr('href'),
                  URLSegment
                )
                // add quantity
                url += 'quantity=' + this.value

                // no double-encoded ampersands
                url = url.replace('&amp;', '&')

                if (typeof EcomQuantityField.EcomCart !== 'undefined') {
                  EcomQuantityField.EcomCart.getChanges(url, null, this)
                } else if (typeof EcomCart !== 'undefined') {
                  EcomCart.getChanges(url, null, this)
                } else {
                  alert('Sorry, changes could not be saved.')
                  window.location = url
                }
              }
            }
          }
        )
      window
        .jQuery(EcomQuantityField.delegateRootSelector)
        .on(
          'blur',
          EcomQuantityField.mainSelector +
            ' ' +
            EcomQuantityField.quantityFieldSelector,
          function () {
            EcomQuantityField.updateFX[window.jQuery(this).attr('name')] = null
          }
        )

      /////// IMPORTANT /////
      EcomQuantityField.reinit()
    }
  },

  //todo: auto-re-attach
  reinit: function () {
    window
      .jQuery(EcomQuantityField.delegateRootSelector)
      .find(EcomQuantityField.mainSelector)
      .each(function (i, el) {
        if (!window.jQuery(el).hasClass(EcomQuantityField.completedClass)) {
          if (EcomQuantityField.hidePlusAndMinues) {
            window.jQuery(el).find(EcomQuantityField.removeSelector).hide()
            window.jQuery(el).find(EcomQuantityField.addSelector).hide()
          }
          window.jQuery(el).addClass(EcomQuantityField.completedClass)
          window
            .jQuery(el)
            .find(EcomQuantityField.quantityFieldSelector)
            .removeAttr('disabled')
        }
      })
  },

  getSetQuantityURLSegment: function (inputField) {
    var name =
      window.jQuery(inputField).attr('name') +
      EcomQuantityField.URLSegmentHiddenFieldSelectorAppendix
    if (window.jQuery('[name=' + name + ']').length > 0) {
      return window.jQuery('[name=' + name + ']').val()
    }
    //backup!
    return window.jQuery(inputField).attr('data-quantity-link')
  },

  debug: function () {
    window.jQuery(EcomQuantityField.addSelector).css('border', '3px solid red')
    window
      .jQuery(EcomQuantityField.removeSelector)
      .css('border', '3px solid red')
    window
      .jQuery(EcomQuantityField.quantityFieldSelector)
      .css('border', '3px solid red')
  }
}
