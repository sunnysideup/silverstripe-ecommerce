import jQuery from 'jquery'
/**
 *@author nicolaas[at]sunnysideup.co.nz
 * This adds functionality to the shipping address section of the checkout form
 *
 **/
if (document.getElementById('OrderFormAddress_OrderFormAddress')) {
  const EcomOrderFormWithShippingAddress = {
    /**
     * array of field names
     * @var array
     */
    copy_billing_to_shipping: false,
    /**
     * array of field names
     * @var array
     */
    fieldArray: [],

    /**
     * array of selectors to select shipping fields
     * @var string
     */
    shippingFieldSelectors: '',

    /**
     * array of selectors to select billing fields
     * @var string
     */
    billingFieldSelectors: '',

    /**
     * selector for form
     * @var string
     */
    formSelector: '#OrderFormAddress_OrderFormAddress',

    /**
     * selector for shipping form section
     * @var string
     */
    shippingSectionSelector: '.shippingFieldsHeader, .shippingFields',

    /**
     * selector for the checkbox that shows wheter or not
     * the shipping address is different
     *
     * @var string
     */
    useShippingDetailsSelector: "input[name='UseShippingAddress']",

    /**
     * selector for the checkbox that shows wheter or not
     * the shipping address is different
     *
     * @var string
     */
    useShippingDetailsAlternativeSelector:
      "input[name='UseDifferentShippingAddress']",

    /**
     * where do we save the alternative header title
     * for the billing address when the billing address has been selected
     * @var string
     */
    billingHeaderAttributeTitleAlternative: 'data-title-with-shipping-address',

    /**
     * Geocoding field ...
     *
     * @var string
     */
    shippingGeoCodingFieldSelector:
      "input[name='ShippingEcommerceGeocodingField']",

    /**
     * country to
     * @var string
     */
    countryToUpdateCartClass: 'ajaxCountryField',

    /**
     * country to
     * @var string
     */
    regionToUpdateCartClass: 'ajaxRegionField',

    /**
     * is the shipping field closed...
     *
     */
    closed: false,

    //hides shipping fields
    //toggle shipping fields when "use separate shipping address" is ticked
    //update shipping fields, when billing fields are changed.
    init: function () {
      if (
        window.jQuery(
          EcomOrderFormWithShippingAddress.useShippingDetailsSelector
        ).length > 0
      ) {
        this.getListOfSharedFields()

        var i
        for (
          i = 0;
          i < EcomOrderFormWithShippingAddress.fieldArray.length;
          ++i
        ) {
          if (i > 0) {
            EcomOrderFormWithShippingAddress.shippingFieldSelectors += ', '
            EcomOrderFormWithShippingAddress.billingFieldSelectors += ', '
          }
          EcomOrderFormWithShippingAddress.shippingFieldSelectors +=
            EcomOrderFormWithShippingAddress.shippingFieldSelector(
              EcomOrderFormWithShippingAddress.fieldArray[i]
            )
          EcomOrderFormWithShippingAddress.billingFieldSelectors +=
            EcomOrderFormWithShippingAddress.billingFieldSelector(
              EcomOrderFormWithShippingAddress.fieldArray[i]
            )
        }
        //turn on listeners...
        if (i > 0) {
          EcomOrderFormWithShippingAddress.turnOnListeners()
          if (
            window.jQuery(
              EcomOrderFormWithShippingAddress.useShippingDetailsAlternativeSelector
            ).length > 0
          ) {
            window
              .jQuery(
                EcomOrderFormWithShippingAddress.useShippingDetailsAlternativeSelector
              )
              .change()
          } else {
            window
              .jQuery(
                EcomOrderFormWithShippingAddress.useShippingDetailsSelector
              )
              .change()
          }
        }
      }
      //why this????
      window
        .jQuery(EcomOrderFormWithShippingAddress.shippingGeoCodingFieldSelector)
        .removeAttr('required')
      //update one more time ...
    },

    /**
     * copy Billing to Shipping
     *
     */
    updateFields: function () {
      var hasShippingAddress = this.hasShippingAddress()
      //copy the billing address details to the shipping address details
      var billingFieldSelector = ''
      var shippingFieldSelector = ''
      var billingField = null
      var shippingField = null
      var billingFieldValue = ''
      var shippingFieldValue = ''
      for (
        var i = 0;
        i < EcomOrderFormWithShippingAddress.fieldArray.length;
        ++i
      ) {
        billingFieldSelector =
          EcomOrderFormWithShippingAddress.billingFieldSelector(
            EcomOrderFormWithShippingAddress.fieldArray[i]
          )
        shippingFieldSelector =
          EcomOrderFormWithShippingAddress.shippingFieldSelector(
            EcomOrderFormWithShippingAddress.fieldArray[i]
          )
        billingField = window.jQuery(billingFieldSelector)
        shippingField = window.jQuery(shippingFieldSelector)
        if (hasShippingAddress === true) {
          if (
            billingField.hasClass(
              EcomOrderFormWithShippingAddress.countryToUpdateCartClass
            )
          ) {
            billingField.removeClass(
              EcomOrderFormWithShippingAddress.countryToUpdateCartClass
            )
            shippingField.addClass(
              EcomOrderFormWithShippingAddress.countryToUpdateCartClass
            )
          }
          if (
            billingField.hasClass(
              EcomOrderFormWithShippingAddress.regionToUpdateCartClass
            )
          ) {
            billingField.removeClass(
              EcomOrderFormWithShippingAddress.regionToUpdateCartClass
            )
            shippingField.addClass(
              EcomOrderFormWithShippingAddress.regionToUpdateCartClass
            )
          }
          billingFieldValue = billingField.val()
          shippingFieldValue = shippingField.val()
          if (
            EcomOrderFormWithShippingAddress.closed &&
            !shippingField.hasClass('dropdown')
          ) {
            shippingField.val('')
          } else if (!shippingFieldValue && billingFieldValue) {
            if (EcomOrderFormWithShippingAddress.copy_billing_to_shipping) {
              shippingField.val(billingFieldValue).change()
            }
          }
        } else {
          if (
            shippingField.hasClass(
              EcomOrderFormWithShippingAddress.countryToUpdateCartClass
            )
          ) {
            shippingField.removeClass(
              EcomOrderFormWithShippingAddress.countryToUpdateCartClass
            )
            billingField.addClass(
              EcomOrderFormWithShippingAddress.countryToUpdateCartClass
            )
          }
          if (
            shippingField.hasClass(
              EcomOrderFormWithShippingAddress.regionToUpdateCartClass
            )
          ) {
            shippingField.removeClass(
              EcomOrderFormWithShippingAddress.regionToUpdateCartClass
            )
            billingField.addClass(
              EcomOrderFormWithShippingAddress.regionToUpdateCartClass
            )
          }
          if (shippingField.hasClass('required')) {
            shippingField
              .removeAttr('required')
              .removeAttr('aria-required')
              .removeAttr('data-has-required')
          } else {
            //do nothing...
          }
        }
      }
      this.swapBillingHeader()
    },

    /**
     * return the billing field selector
     * @param string
     * @return string
     */
    billingFieldSelector: function (name) {
      name = name.replace('Shipping', '')
      return (
        ' ' +
        EcomOrderFormWithShippingAddress.formSelector +
        " input[name='" +
        name +
        "'], " +
        EcomOrderFormWithShippingAddress.formSelector +
        " select[name='" +
        name +
        "'], " +
        EcomOrderFormWithShippingAddress.formSelector +
        " textarea[name='" +
        name +
        "']"
      )
    },

    isShippingField: function (name) {
      return name.indexOf('Shipping') > -1
    },

    /**
     * return the shipping field selector
     * @param string
     * @return string
     */
    shippingFieldSelector: function (name) {
      name = 'Shipping' + name.replace('Billing', '')
      return (
        ' ' +
        EcomOrderFormWithShippingAddress.formSelector +
        " input[name='" +
        name +
        "'], " +
        EcomOrderFormWithShippingAddress.formSelector +
        " select[name='" +
        name +
        "'], " +
        EcomOrderFormWithShippingAddress.formSelector +
        " textarea[name='" +
        name +
        "']"
      )
    },

    //this function exists, because FF was auto-completing
    //Shipping City as the username part of a password / username combination (password being the next field)
    removeEmailFromShippingCityHack: function () {
      var pattern =
        /^([a-zA-Z0-9_.-])+@([a-zA-Z0-9_.-])+\.([a-zA-Z])+([a-zA-Z])+/
      var shippingCitySelectorValue = window
        .jQuery(EcomOrderFormWithShippingAddress.shippingCitySelector)
        .val()
      if (pattern.test(shippingCitySelectorValue)) {
        window
          .jQuery(EcomOrderFormWithShippingAddress.shippingCitySelector)
          .val(
            window.jQuery(EcomOrderFormWithShippingAddress.citySelector).val()
          )
          .change()
      } else {
        //do nothing
      }
    },

    /**
     *
     * get a list of fields that is potentially shared.
     */
    getListOfSharedFields: function () {
      window
        .jQuery(
          this.formSelector +
            ' input, ' +
            this.formSelector +
            ' select, ' +
            this.formSelector +
            ' textarea'
        )
        .each(function (i, el) {
          el = window.jQuery(el)
          var name = el.attr('name')
          if (typeof name !== 'undefined') {
            var type = el.prop('nodeName')
            if (typeof type !== 'undefined' && type.toLowerCase() === 'input') {
              type = el.attr('type')
            }
            if (typeof type !== 'undefined') {
              if (type !== 'submit' && type !== 'hidden') {
                var billingFieldSelector =
                  EcomOrderFormWithShippingAddress.billingFieldSelector(name)
                var shippingFieldSelector =
                  EcomOrderFormWithShippingAddress.shippingFieldSelector(name)
                if (
                  window.jQuery(billingFieldSelector).length > 0 &&
                  !EcomOrderFormWithShippingAddress.isShippingField(name)
                ) {
                  if (
                    window.jQuery.inArray(
                      name,
                      EcomOrderFormWithShippingAddress.fieldArray
                    )
                  ) {
                    EcomOrderFormWithShippingAddress.fieldArray.push(name)
                  }
                }
              }
            }
          }
        })
    },

    turnOnListeners: function () {
      //if the billing updates, the shipping updates
      window
        .jQuery(EcomOrderFormWithShippingAddress.billingFieldSelectors)
        .change(function () {
          //important ...
          EcomOrderFormWithShippingAddress.updateFields()
        })

      //on focus of the shipping fields, look for update...
      window
        .jQuery(EcomOrderFormWithShippingAddress.shippingFieldSelectors)
        .focus(function () {
          EcomOrderFormWithShippingAddress.updateFields()
        })

      //turn-on shipping details toggle
      window
        .jQuery(EcomOrderFormWithShippingAddress.useShippingDetailsSelector)
        .change(function () {
          if (EcomOrderFormWithShippingAddress.hasShippingAddress()) {
            //slidedown
            window
              .jQuery(EcomOrderFormWithShippingAddress.shippingSectionSelector)
              .slideDown()

            //focus on first field
            var firstShippingField =
              EcomOrderFormWithShippingAddress.fieldArray[0]
            window
              .jQuery(
                EcomOrderFormWithShippingAddress.shippingFieldSelector(
                  firstShippingField
                )
              )
              .focus()

            //set required fields ...
            window
              .jQuery(EcomOrderFormWithShippingAddress.shippingFieldSelectors)
              .each(function (i, el) {
                if (window.jQuery(el).hasClass('required')) {
                  window
                    .jQuery(el)
                    .attr('required', 'required')
                    .attr('aria-required', true)
                    .attr('aria-required', true)
                    .attr('data-has-required', 'yes')
                } else {
                }
              })

            //save setting
            EcomOrderFormWithShippingAddress.closed = false
          } else {
            //slide up
            window
              .jQuery(EcomOrderFormWithShippingAddress.shippingSectionSelector)
              .slideUp()

            //make not required
            window
              .jQuery(EcomOrderFormWithShippingAddress.shippingFieldSelectors)
              .each(function (i, el) {
                if (window.jQuery(el).hasClass('required')) {
                  window
                    .jQuery(el)
                    .removeAttr('required')
                    .removeAttr('aria-required')
                    .removeAttr('data-has-required')
                } else {
                  //do nothing...
                }
              })

            //save answer
            EcomOrderFormWithShippingAddress.closed = true
          }
          EcomOrderFormWithShippingAddress.makeSureOnlyTheRightCountriesCanBeSelected()
          //copy fields ...
          EcomOrderFormWithShippingAddress.updateFields()
        })
      if (
        window.jQuery(
          EcomOrderFormWithShippingAddress.useShippingDetailsAlternativeSelector
        ).length > 0
      ) {
        window
          .jQuery(
            EcomOrderFormWithShippingAddress.useShippingDetailsAlternativeSelector
          )
          .change(function (event) {
            if (
              window
                .jQuery(
                  EcomOrderFormWithShippingAddress.useShippingDetailsAlternativeSelector
                )
                .is(':checked') === true
            ) {
              window
                .jQuery(
                  EcomOrderFormWithShippingAddress.useShippingDetailsSelector
                )
                .attr('checked', 'checked')
                .val('1')
            } else {
              window
                .jQuery(
                  EcomOrderFormWithShippingAddress.useShippingDetailsSelector
                )
                .removeAttr('checked', 'checked')
                .val('0')
            }
            window
              .jQuery(
                EcomOrderFormWithShippingAddress.useShippingDetailsSelector
              )
              .change()
          })
      }
    },

    swapBillingHeader: function () {
      var billingHeader = window.jQuery(this.formSelector + '_BillingDetails')
      var hasShippingAddress = this.hasShippingAddress()
      if (hasShippingAddress) {
        var newHeaderAttr = this.billingHeaderAttributeTitleAlternative
      } else {
        var newHeaderAttr =
          this.billingHeaderAttributeTitleAlternative + '_default'
      }
      var newHeader = billingHeader.attr(newHeaderAttr)
      billingHeader.text(newHeader)
    },

    hasShippingAddress: function () {
      var isHidden = window.jQuery(
        EcomOrderFormWithShippingAddress.useShippingDetailsSelector +
          '[type="hidden"]'
      )
      if (isHidden) {
        var isOne =
          window
            .jQuery(EcomOrderFormWithShippingAddress.useShippingDetailsSelector)
            .val() == '1'
        return isOne
      } else {
        var isChecked = window
          .jQuery(EcomOrderFormWithShippingAddress.useShippingDetailsSelector)
          .is(':checked')
        return isChecked
      }
    },

    /**
     * Swapping out the available countries
     * for the Billing Address depending on whether the Billing address
     * is also the Shipping address or that the shipping address is separate
     */
    makeSureOnlyTheRightCountriesCanBeSelected: function () {
      if (
        typeof CountryPrice_SetCountriesForDelivery_New !== 'undefined' &&
        typeof CountryPrice_SetCountriesForDelivery_Original !== 'undefined'
      ) {
        if (this.hasShippingAddress()) {
          var options = CountryPrice_SetCountriesForDelivery_Original
        } else {
          var options = CountryPrice_SetCountriesForDelivery_New
        }
        var el = window.jQuery("select[name='Country']")
        EcomOrderFormWithShippingAddress.swappingOptions(el, options)
      }
    },

    /**
     * update a select with new options
     * @var window.jQuery Object
     * @var array
     */
    swappingOptions: function (el, newOptions) {
      var oldValue = window.jQuery(el).val()
      window.jQuery(el).empty()
      $.each(newOptions, function (key, value) {
        el.append($('<option></option>').attr('value', key).text(value))
      })
      window.jQuery(el).val(oldValue)
    }
  }

  jQuery(() => {
    EcomOrderFormWithShippingAddress.init()
  })
}
