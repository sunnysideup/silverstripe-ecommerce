/**
 * @description: update Cart using AJAX (JSON data source)
 * as well as making any "add to cart" and "remove from cart" links
 * work with AJAX (if setup correctly)
 * @author nicolaas @ sunny side up . co . nz
 *
 * in short, the way this works is that a bunch of items on the page
 * are set up to interact with the shopping cart:
 * - country selector
 * - region selector
 * - add to cart buttons
 * - remove from cart buttons
 *
 * If any of these form fields / buttons are clicked / changed
 * data is requested from the server.
 *
 * When the data returns, it is processed and its 'instruction'
 * are applied. Instructions can be:
 *
 * type = "id" | "class"
 *     parameter = innerHTML => update innerHTML
 *     parameter = hide => show/hide, using "hideForNow" class
 *      parameter = anything else =>  update attribute
 * WITH name:
 *  - update attribute (e.g. update value for quantity field using the field's name)
 * WITH dropdownArray
 *  - update dropdown
 * WITH rows:
 *  - add / delete TO BE IMPLEMENTED
 *
 *
 * It is recommended that you adjust the IDs / class names / names / dropdown identifiers
 * in your html rather than trying to change what is being returned (although this is possible too).
 *
 * To see what is returns can be done as follows:
 * 1. log in with administrator credentials
 * 2. browse to:
 * @http://www.yoursite.com/shoppingcart/test/
 *
 * NOTE: for your own ajax needs, you can also JUST access the cart, like this:
 * http://www.yourseite.com/shoppingcart/showcart/
 *
 **/

window.joinUrlWithSlash = function (...strings) {
  const hasQuery = strings.some(str => str.includes('?'))
  return strings
    .map(str => str.replace(/\/+$/, ''))
    .join('/')
    .replace(/([^:]\/)\/+/g, '$1')
    .replace(hasQuery ? /\/+(\?|$)/ : /\/+$/, '$1')
}

const EcomCart = {
  /**
   * Applies fetched data to DOM elements matching a selector.
   * @param {string} selector - CSS selector for target elements.
   * @param {object} values - Data to apply (e.g., class, HTML content, callback function).
   */
  applyData: (selector, values) => {
    document.querySelectorAll(selector).forEach(el => {
      if (values.class && !el.classList.contains(values.class)) {
        el.classList.add(values.class)
      }
      if (values.removeClass && el.classList.contains(values.removeClass)) {
        el.classList.remove(values.removeClass)
      }
      if (values.html && el.innerHTML !== values.html) {
        el.innerHTML = values.html
      }
      if (values.prepend && !el.innerHTML.includes(values.prepend)) {
        el.innerHTML = values.prepend + el.innerHTML
      }
      if (values.append && !el.innerHTML.includes(values.append)) {
        el.innerHTML = el.innerHTML + values.append
      }
      if (values.callback) {
        values.callback(el)
      }
    })
  },

  /**
   * Set to TRUE to see debug info.
   * @var Boolean
   */
  debug: false,
  set_debug: function (b) {
    this.debug = b
  },

  /**
   * selector to identify input field for selecting country.
   */
  shoppingCartURLSegment: 'shoppingcart',
  set_shoppingCartURLSegment: function (s) {
    this.shoppingCartURLSegment = s
  },

  /**
   * this is a collection of dom elements that hold the item causing the change
   * we retain this here so that we can add a loading class to it and,
   * on return, we can remove it.
   * Because it is an array, each clicked element can be individually given
   * the loading class and also removed when its particular request returns.
   */
  loadingSelectors: [],

  /**
   * tells us the number of ajax calls that are currently awaiting
   * processing
   * @var Int
   */
  openAjaxCalls: 0,

  /**
   * are there any items in the cart
   * @var Boolean
   */
  cartHasItems: false,

  /**
   * This is the data that we start with (which may be contained in the original HTML)
   * @var Array
   */
  initialData: [],
  set_initialData: function (a) {
    this.initialData = a
  },

  /**
   *  array of callbacks to call after update
   *
   * @type Array
   */
  reinitCallbacks: [],

  // #################################
  // COUNTRY + REGION SELECTION
  // #################################

  /**
   * selector to identify the area in which the country + region selection takes place
   * @todo: can we make this more specific?
   */
  countryAndRegionRootSelector: 'body',
  set_countryAndRegionRootSelector: function (s) {
    this.countryAndRegionRootSelector = s
  },

  /**
   * selector to identify input field for selecting country.
   */
  ajaxCountryFieldSelector: 'select.ajaxCountryField',
  set_ajaxCountryFieldSelector: function (s) {
    this.ajaxCountryFieldSelector = s
  },

  /**
   * selector to identify input field for selecting region.
   */
  ajaxRegionFieldSelector: 'select.ajaxRegionField',
  set_ajaxRegionFieldSelector: function (s) {
    this.ajaxRegionFieldSelector = s
  },

  // #################################
  // UPDATING THE CART - CLASSES USED
  // #################################

  /**
   * class used to show cart data is being updated.
   */
  classToShowLoading: 'loading',
  set_classToShowLoading: function (s) {
    this.classToShowLoading = s
  },

  /**
   * class used to 'lock' the page while cart updates are being processed.
   */
  classToShowPageIsUpdating: 'ecomCartIsUpdating',
  set_classToShowPageIsUpdating: function (s) {
    this.classToShowPageIsUpdating = s
  },

  /**
   * the class used to show add/remove buyable buttons
   */
  showClass: 'show',
  set_showClass: function (s) {
    this.showClass = s
  },

  /**
   * the class used to hide add/remove buyable buttons
   */
  hideClass: 'hide',
  set_hideClass: function (s) {
    this.hideClass = s
  },

  /**
   * a method called before the update
   * params for onBeforeUpdate:
   * url, params, EcomCart.setChanges
   * EcomCart.set_onBeforeUpdate(function(url, params, setChanges) {alert("before");});
   */
  onBeforeUpdate: null,
  set_onBeforeUpdate: function (f) {
    this.onBeforeUpdate = f
  },

  /**
   * a method called after the update
   * params for onAfterUpdate:
   * changes, status
   * EcomCart.set_onAfterUpdate(function(change, status) {alert("after");});
   */
  onAfterUpdate: null,
  set_onAfterUpdate: function (f) {
    this.onAfterUpdate = f
  },

  /**
   * @var Array
   * Synonyms are used in the update to also update
   * They take the form of:
   * Selector (e.g. MyCart) => Other Selectors
   * It updates the Other Selectors at the same time as it updates the Selector
   * e.g. Order_DB_302_Total => ".TotalAmounts"
   * As most of the core selctors are dynamic, they should be set at runtime.
   */
  synonyms: [],
  set_synonyms: function (a) {
    this.synonyms = a
  },
  add_synonym: function (key, value) {
    this.synonyms[key] = value
  },
  remove_synonym: function (key) {
    this.synonyms.splice(key, 1)
  },

  // #################################
  // ITEMS (OR LACK OF) IN THE CART
  // #################################

  /**
   * selector of the dom elements shown when there are no items in cart.
   */
  selectorShowOnZeroItems: '.showOnZeroItems',
  set_selectorShowOnZeroItems: function (s) {
    this.selectorShowOnZeroItems = s
  },

  /**
   * selector of the dom elements that is hidden on zero items.
   */
  selectorHideOnZeroItems: '.hideOnZeroItems',
  set_selectorHideOnZeroItems: function (s) {
    this.selectorHideOnZeroItems = s
  },

  /**
   * selector for the item rows.
   */
  selectorItemRows: 'tr.orderitem',
  set_selectorItemRows: function (s) {
    this.selectorItemRows = s
  },

  /**
   * the selector used to identify "remove from cart" links within the cart.
   */
  removeCartSelector: '.ajaxRemoveFromCart',
  set_removeCartSelector: function (s) {
    this.removeCartSelector = s
  },

  // #################################
  // AJAX CART LINKS OUTSIDE THE CART
  // #################################

  /**
   * turn on / off the ajax buttons outside of the cart
   * (e.g. add this product to cart, delete from cart)
   * @var Boolean
   */
  ajaxButtonsOn: true,
  set_ajaxButtonsOn: function (b) {
    this.ajaxButtonsOn = b
  },

  /**
   * Can the Product List be updated using AJAX?
   *
   * @var Boolean
   */
  ajaxifyProductList: false,
  set_ajaxifyProductList: function (b) {
    this.ajaxifyProductList = b
  },

  /**
   * Is the product list from a cached source?
   *
   * This is important to know, because in this case
   * we have to disable the SecurityID by adding
   * cached=1 to all URLs
   *
   * @var Boolean
   */
  productListIsFromCachedSource: true,

  /**
   * NOTE: set to empty string to bypass confirmation step
   */
  confirmDeleteText:
    'Are you sure you would like to remove this item from your cart?',
  set_confirmDeleteText: function (s) {
    this.confirmDeleteText = s
  },

  /**
   * the area in which the ajax links can be found.
   */
  ajaxLinksAreaSelector: 'body',
  set_ajaxLinksAreaSelector: function (v) {
    this.ajaxLinksAreaSelector = v
  },

  /**
   * the selector used to identify links that add buyables to the cart
   */
  addLinkSelector: '.ajaxBuyableAdd',
  set_addLinkSelector: function (s) {
    this.addLinkSelector = s
  },

  /**
   * the selector used to identify links that add buyables to the cart
   */
  excludedPagesSelector: '.no-ajax-buttons',
  set_excludedPagesSelector: function (s) {
    this.excludedPagesSelector = s
  },

  /**
   * the selector used to identify links that remove buyables from the cart
   * (outside the cart itself)
   */
  removeLinkSelector: '.ajaxBuyableRemove',
  set_removeLinkSelector: function (s) {
    this.removeLinkSelector = s
  },

  /**
   * the selector used to identify any buyable holder within a cart
   */
  orderItemHolderSelector: '.orderItemHolder',
  set_orderItemHolderSelector: function (s) {
    this.removeLinkSelector = s
  },

  /**
   * the selector used to identify the cart related menu items (e.g. cart / checkout)
   */
  cartMenuLinksSelector: '.cartlink',
  set_cartMenuLinksSelector: function (s) {
    this.cartMenuLinksSelector = s
  },

  // #################################
  // AJAX PRODUCT LINKS
  // #################################

  /**
   * the selector used to identify links
   * that change the product list. These can be ajaxified so that the list
   * is using AJAX rather than reload the whole page.
   * @var String
   */
  ajaxifiedListAdjusterSelectors: '.ajaxifyMyProductGroupLinks',
  set_ajaxifiedListAdjusterSelectors: function (s) {
    this.ajaxifiedListAdjusterSelectors = s
  },

  /**
   * selector of element that will be replaced by the new
   * product list
   * @var String
   */
  ajaxifiedListHolderSelector: '#ProductGroup',
  set_ajaxifiedListHolderSelector: function (s) {
    this.ajaxifiedListsSelector = s
  },

  /**
   * Hidden page title, used when products are updated using the
   * @var String
   */
  hiddenPageTitleID: '#HiddenPageTitleID',
  set_hiddenPageTitleID: function (s) {
    this.hiddenPageTitleID = s
  },

  /**
   * Hidden page title, used when products are updated using the
   * @var function
   */
  ajaxifiedProductsCallBack: function () {},
  set_ajaxifiedProductsCallBack: function (f) {
    this.ajaxifiedProductsCallBack = f
  },

  // #################################
  // DIALOGUE POP-UP BOX
  // #################################

  /**
   * the selector used to identify any links that open a pop-up dialogue
   * the syntax is as follows:
   * <a href="#colorboxDialogCart" class="colorboxDialog" rel="">show cart</a>
   * <div id="colorboxDialogCart">content for pop-up</div> (this line is optional)
   */
  colorboxDialogSelector: '.colorboxDialog',
  set_colorboxDialogSelector: function (s) {
    this.colorboxDialogSelector = s
  },

  /**
   * The options set for the colorbox dialogue, see: https://github.com/jackmoore/colorbox
   * @var Int
   */
  colorboxDialogOptions: {
    height: '95%',
    width: '95%',
    maxHeight: '95%',
    maxWidth: '95%',
    loadingClass: 'loading',
    iframe: true,
    onOpen: function (event) {
      EcomCart.reinit(true)
    }
  },
  set_colorboxDialogOptions: function (o) {
    this.colorboxDialogOptions = o
  },

  // #################################
  // INIT AND RESET FUNCTIONS
  // #################################

  /**
   * initialises all the ajax functionality
   */
  init: function () {
    if (typeof window.EcomCartOptions !== 'undefined') {
      for (var key in window.EcomCartOptions) {
        if (window.EcomCartOptions.hasOwnProperty(key)) {
          this[key] = window.EcomCartOptions[key]
        }
      }
    }
    // make sure that country and region changes are applied to Shopping Cart
    EcomCart.countryAndRegionUpdates()
    // setup an area where the user can change their country / region
    EcomCart.changeCountryFieldSwap()
    // ajaxify product list
    EcomCart.addAjaxificationOfProductList()
    EcomCart.addSelectForSmallerSpaces()
    // cart buttons
    if (EcomCart.ajaxButtonsOn) {
      // make sure that "add to cart" links are updated with AJAX
      EcomCart.addAddLinks(EcomCart.ajaxLinksAreaSelector)
      // make sure that "remove from cart" links are updated with AJAX
      EcomCart.addRemoveLinks(EcomCart.ajaxLinksAreaSelector)
      // make sure that "delete from cart" links are updated with AJAX - looking at the actual cart itself.
      EcomCart.addCartRemove(EcomCart.ajaxLinksAreaSelector)
    }
    // EcomCart.updateForZeroVSOneOrMoreRows(); is only required after changes are made
    // because HTML loads the right stuff by default.
    // EcomCart.updateForZeroVSOneOrMoreRows();
    EcomCart.initColorboxDialog()
    EcomCart.setChanges(EcomCart.initialData, '')
    // allow ajax product list back and forth:
  },

  /**
   * runs everytime the cart is updated
   * @param Boolean changes applied? have changes been applied in the meantime.
   */
  reinit: function (changesApplied) {
    // hide or show "zero items" information
    if (changesApplied) {
      EcomCart.updateForZeroVSOneOrMoreRows()
    }
    for (var i = 0; i < EcomCart.reinitCallbacks.length; i++) {
      EcomCart.reinitCallbacks[i]()
    }
  },

  // #################################
  // COUNTRY AND REGION CHANGES
  // #################################

  /**
   * sets the functions for updating country and region
   */
  countryAndRegionUpdates: function () {
    window
      .jQuery(EcomCart.countryAndRegionRootSelector)
      .on('change', EcomCart.ajaxCountryFieldSelector, function () {
        var url = EcomCart.createUrl('setcountry', this.value)
        EcomCart.getChanges(url, null, this)
      })
    window
      .jQuery(EcomCart.countryAndRegionRootSelector)
      .on('change', EcomCart.ajaxRegionFieldSelector, function () {
        var url = EcomCart.createUrl('setregion', this.value)
        EcomCart.getChanges(url, null, this)
      })
  },

  /**
   * gets the options from the main country field and presents them as options for the user
   * to select a new country.
   */
  changeCountryFieldSwap: function () {
    window
      .jQuery(EcomCart.countryAndRegionRootSelector)
      .on(
        'change',
        EcomCart.selectorChangeCountryFieldHolder + ' select',
        function () {
          var val = window
            .jQuery(EcomCart.selectorChangeCountryFieldHolder + ' select')
            .val()
          window.jQuery(EcomCart.ajaxCountryFieldSelector).val(val)
          var url = EcomCart.createUrl('setcountry', val)
          EcomCart.getChanges(url, null, this)
          window.jQuery(EcomCart.selectorChangeCountryLink).click()
        }
      )
  },

  /**
   * ajaxify the product list
   *
   */
  addAjaxificationOfProductList: function () {
    if (EcomCart.ajaxifyProductList) {
      document.body.addEventListener(
        'click',
        function (event) {
          const target = event.target.closest(
            EcomCart.ajaxifiedListAdjusterSelectors + ' a'
          )

          if (target) {
            event.preventDefault()

            const currentEl = target // already a native DOM element
            const url = currentEl.getAttribute('href')
            const dataResetFor = currentEl.getAttribute('data-reset-for')

            EcomCart.ajaxLoadProductList(url, dataResetFor, () => {
              // Step 1: Find the closest ancestor matching the selector
              const holder = currentEl.closest(
                EcomCart.ajaxifiedListAdjusterSelectors
              )
              // console.log('Holder:', holder) // Debug: Check if holder is found

              if (holder) {
                // Step 2: Remove 'current' class from links
                const currentLink = holder.querySelector('a.current')
                if (currentLink) {
                  currentLink.classList.remove('current')
                } else {
                  // console.log('No link with class "current" found')
                }

                // Step 3: Set the select value
                const select = holder.querySelector('select')
                // console.log('Select element set to:', select, url) // Debug: Check if select element exists

                if (select) {
                  // Check if the select has an option with the given value
                  const optionExists = Array.from(select.options).some(
                    option => option.value === url
                  )
                  if (optionExists) {
                    select.value = url
                    // console.log('Select value set to:', select.value) // Verify the value is set
                  }
                } else {
                  // console.log('No select element found')
                }
              }

              // Step 4: Add 'current' class to `currentEl`
              currentEl.classList.add('current')
              // console.log('currentEl classList:', currentEl.classList) // Verify 'current' class is added
            })
          }
        },
        true
      )
      document.body.addEventListener('change', function (event) {
        const target = event.target.closest(
          EcomCart.ajaxifiedListAdjusterSelectors + ' select'
        )

        if (target) {
          event.preventDefault()

          const currentEl = target // already a native DOM element
          const url = currentEl.value
          const selectedOption = currentEl.options[currentEl.selectedIndex]
          const dataResetFor = selectedOption.getAttribute('data-reset-for')
          EcomCart.ajaxLoadProductList(url, dataResetFor, () => {
            const holder = currentEl.closest(
              EcomCart.ajaxifiedListAdjusterSelectors
            )
            holder.querySelectorAll('a.current').forEach(link => {
              link.classList.remove('current')
            })
            holder.querySelector(`a[href='${url}']`).classList.add('current')
          })
        }
      })

      // fix for back button
    }

    // always do the popstate
    window.onpopstate = function (e) {
      if (EcomCart.hasAjaxProductLoad) {
        const newUrl = window.location.href
        window.location.href = newUrl
      }
    }
    // window.addEventListener('beforeunload', () => {
    //   if (EcomCart.hasAjaxProductLoad) {
    //     const url = new URL(window.location.href)
    //     url.searchParams.set('nocache', Date.now()) // Add or update `reload` parameter

    //     // Update history state with modified URL
    //     window.history.replaceState(null, '', url.toString())
    //   }
    // })
  },

  hasAjaxProductLoad: false,

  ajaxLoadProductList: function (myUrl, dataResetFor, myCallBack) {
    EcomCart.hasAjaxProductLoad = true
    // console.log('AJAX Load Product List:', myUrl) // Debug: Check if URL is correct
    myUrl = EcomCart.mergeUrlParamsForAjax(myUrl, dataResetFor)
    window.jQuery.ajax({
      beforeSend: function () {
        window
          .jQuery(EcomCart.ajaxifiedListHolderSelector)
          .addClass(EcomCart.classToShowLoading)
      },
      // cache: false,
      complete: function () {
        window
          .jQuery(EcomCart.ajaxifiedListHolderSelector)
          .removeClass(EcomCart.classToShowLoading)
      },
      dataType: 'html',
      error: function (jqXHR, textStatus, errorThrown) {
        alert(
          'An error occurred (' +
            textStatus +
            ' ' +
            errorThrown +
            ')! I will try reloading the page now.'
        )
        window.location.href = myUrl
      },
      success: function (data, textStatus, jqXHR) {
        window.jQuery(EcomCart.ajaxifiedListHolderSelector).html(data)

        // create history
        var pageTitle = window.jQuery(EcomCart.hiddenPageTitleID).text()
        // create history
        var pageTitle = window.jQuery(EcomCart.hiddenPageTitleID).text()
        window.history.pushState(
          {
            pageTitle: pageTitle
          },
          pageTitle,
          EcomCart.removeAjaxParam(myUrl)
        )

        document.title = pageTitle
        // update changes
        // set changes also does the reinit
        EcomCart.openAjaxCalls++
        EcomCart.setChanges(EcomCart.initialData, '')
        if (typeof EcomProducts !== 'undefined') {
          EcomProducts.reinit()
        }
        if (typeof EcomCart.ajaxifiedProductsCallBack === 'function') {
          EcomCart.ajaxifiedProductsCallBack()
        }
        // scroll to the top of the product list.
        window.jQuery('html, body').animate(
          {
            scrollTop:
              window.jQuery(EcomCart.ajaxifiedListHolderSelector).offset().top -
              160
          },
          500
        )
        myCallBack()
        //fire an event to inform that data on the page has changed
        const event = new Event('paginationchange')
        window.dispatchEvent(event)
      },
      url: myUrl
    })
  },

  addSelectForSmallerSpaces: function () {
    const filterSortLinks = document.querySelectorAll(
      EcomCart.ajaxifiedListAdjusterSelectors
    )
    filterSortLinks.forEach(group => {
      const list = group.querySelector('ul')
      if (!list) {
        // console.error('No list found in group', group)
        return
      }
      const maxLength = group.getAttribute('data-max-ul-count') ?? 3
      const listItems = list.getElementsByTagName('li')
      if (listItems.length < 2) {
        group.classList.add('ajaxified-hide')
        return
      }
      const dropdownContainer = group.querySelector('.dropdown-container')
      if (!dropdownContainer) {
        return
      }
      const dropdown = dropdownContainer.querySelector('.dropdown')

      // Add list items to the select dropdown
      Array.from(listItems).forEach(item => {
        const link = item.querySelector('a')
        const url = new URL(link.href)
        const pathAndQuery = url.pathname + url.search
        const dataResetFor = link.getAttribute('data-reset-for')
        if (link) {
          const option = document.createElement('option')
          option.textContent = link.textContent
          option.value = pathAndQuery
          if (dataResetFor) {
            option.setAttribute('data-reset-for', dataResetFor)
          }
          if (link.classList.contains('current')) {
            option.selected = true
          }
          dropdown.appendChild(option)
        }
      })

      // Check if list has more than maxLength items
      const shouldShowDropdown = listItems.length > maxLength
      group.classList.add('ajaxified-has-both')
      list.classList.toggle('ajaxified-show', !shouldShowDropdown)
      list.classList.toggle('ajaxified-hide', shouldShowDropdown)
      dropdownContainer.classList.toggle('ajaxified-show', shouldShowDropdown)
      dropdownContainer.classList.toggle('ajaxified-hide', !shouldShowDropdown)
    })
  },

  mergeUrlParamsForAjax: function (newUrl, dataResetFor) {
    const base = new URL(newUrl, window.location.origin)
    const oldParams = new URL(window.location.href).searchParams

    // Combine all keys from both old and new params, excluding dataResetFor
    const allKeys = new Set([
      ...Array.from(oldParams.keys()),
      ...Array.from(base.searchParams.keys())
    ])
    // Track if there’s exactly one change and if that change is 'start'
    let changeCount = 0
    let onlyStartChanged = true
    allKeys.forEach(key => {
      const oldValue = oldParams.get(key)
      const newValue = base.searchParams.get(key)
      if (oldValue !== newValue && key !== 'ajax') {
        changeCount++
        if (key !== 'start') {
          onlyStartChanged = false
        }
      }
    })

    // Remove 'start' if there's more than one change, if the only change isn't 'start',
    // or if 'start' is zero
    if (changeCount > 1 || onlyStartChanged === false) {
      base.searchParams.delete('start')
      oldParams.delete('start')
    }

    // Merge old params that aren't in base and aren't dataResetFor
    oldParams.forEach((value, key) => {
      if (!base.searchParams.has(key) && key !== dataResetFor) {
        base.searchParams.set(key, value)
      }
    })
    base.searchParams.set('ajax', 1)
    if (base.searchParams.get('start') === '0') {
      base.searchParams.delete('start')
    }

    return base.pathname + base.search // Return path and query only
  },

  removeAjaxParam: function (url) {
    const urlObj = new URL(url, window.location.origin)
    urlObj.searchParams.delete('ajax')
    return urlObj.toString()
  },

  // #################################
  // SETUP PAGE
  // #################################

  /**
   * adds the "add to cart" ajax functionality to links.
   * @param String withinSelector: area where these links can be found, the more specific the better (faster)
   */
  addAddLinks: function (withinSelector) {
    window
      .jQuery(withinSelector)
      .not(EcomCart.excludedPagesSelector)
      .on('click', EcomCart.addLinkSelector, function () {
        var url = window.jQuery(this).attr('href')
        if (EcomCart.productListIsFromCachedSource) {
          url += '&cached=1'
        }
        EcomCart.getChanges(url, null, this)
        return false
      })
  },

  /**
   * add ajax functionality to "remove from cart" links
   * outside the cart
   * @param String withinSelector: area where these links can be found, the more specific the better (faster)
   */
  addRemoveLinks: function (withinSelector) {
    window
      .jQuery(withinSelector)
      .not(EcomCart.excludedPagesSelector)
      .on('click', EcomCart.removeLinkSelector, function () {
        if (EcomCart.unconfirmedDelete || confirm(EcomCart.confirmDeleteText)) {
          var url = window.jQuery(this).attr('href')
          if (EcomCart.productListIsFromCachedSource) {
            url += '&cached=1'
          }
          EcomCart.getChanges(url, null, this)
        }
        return false
      })
  },

  /**
   * adds the "remove from cart" ajax functionality to links
   * IN THE CART!
   * @param String withinSelector: area where these links can be found, the more specific the better (faster)
   */
  addCartRemove: function (withinSelector) {
    window
      .jQuery(withinSelector)
      .on('click', EcomCart.removeCartSelector, function (event) {
        if (
          !EcomCart.confirmDeleteText ||
          confirm(EcomCart.confirmDeleteText)
        ) {
          var url = window.jQuery(this).attr('href')
          var el = window.jQuery(this).parents(EcomCart.orderItemHolderSelector)
          window.jQuery(el).slideUp('slow', function () {
            window.jQuery(el).remove()
          })
          EcomCart.getChanges(url, null, this)
        }
        return false
      })
  },

  // #################################
  // UPDATE PAGE
  // #################################

  /**
   * get JSON data from server
   * @param String url: URL for getting data (ajax request)
   * @param Array params: parameters to add to ajax request
   * @param Object loadingElement: the element that is being clicked or should be shown as "loading"
   */
  getChanges: function (url, params, loadingElement) {
    if (params === null) {
      params = {}
    }
    if (EcomCart.ajaxButtonsOn) {
      params.ajaxButtonsOn = true
    }
    if (EcomCart.openAjaxCalls > 1) {
      params.manyrequests = 1
    }
    var loadingIndex = this.addLoadingSelector(loadingElement)
    params.loadingindex = loadingIndex
    if (EcomCart.onBeforeUpdate) {
      if (typeof EcomCart.onBeforeUpdate === 'function') {
        EcomCart.onBeforeUpdate.call(url, params, EcomCart.setChanges)
      }
    }
    EcomCart.openAjaxCalls++
    window.jQuery.getJSON(url, params, EcomCart.setChanges)
  },

  /**
   * when, for example, you click on an "add to cart" button
   * this method adds the loading class to the clicked button
   * and retains the element so that the loading class can be removed
   * when the data is returned.
   * @param element (e.g. window.jQuery("#MyClickableButton") )
   * @return integer
   */
  addLoadingSelector: function (loadingElement) {
    loadingElement = window.jQuery(loadingElement).parent().parent()
    window.jQuery(loadingElement).addClass(EcomCart.classToShowLoading)
    window.jQuery('body').addClass(EcomCart.classToShowPageIsUpdating)
    EcomCart.loadingSelectors[EcomCart.loadingSelectors.length] = loadingElement
    return EcomCart.loadingSelectors.length - 1
  },

  /**
   *
   *
   *
   * @return String
   */
  createUrl: function (method, variable) {
    let url = window.joinUrlWithSlash(
      window.jQuery('base').attr('href'),
      EcomCart.shoppingCartURLSegment
    )
    if (method) {
      url = window.joinUrlWithSlash(url, method)
    }
    if (variable) {
      url = window.joinUrlWithSlash(url, variable)
    }
    return url
  },

  /**
   * apply changes to the page using the JSON data from the server.
   * @param JSON OBJECT changes: a JSON object of changes
   * @param String status: status of updates
   */
  setChanges: function (changes, status) {
    EcomCart.set_initialData(changes)
    EcomCart.openAjaxCalls--
    // change to switch
    // add loadingElement to data return
    // clean up documentation at the top of the document
    // if (EcomCart.debug) {
    //   console.debug('------------- SET CHANGES -----------')
    // }
    if (changes.reload) {
      window.location = window.location
      return
    }
    if (EcomCart.openAjaxCalls <= 0) {
      Object.entries(changes).forEach(([selector, values]) => {
        EcomCart.applyData(selector, values)
      })
      if (EcomCart.onAfterUpdate) {
        if (typeof EcomCart.onAfterUpdate === 'function') {
          EcomCart.onAfterUpdate.call(changes, status)
        }
      }

      EcomCart.reinit(changes.length > 0)
      window.jQuery('body').removeClass(EcomCart.classToShowPageIsUpdating)
      for (var i = 0; i < EcomCart.loadingSelectors.length; i++) {
        window
          .jQuery(EcomCart.loadingSelectors[i])
          .removeClass(EcomCart.classToShowLoading)
      }
    }
  },

  /**
   * changes to the cart based on zero OR one or more rows
   */
  updateForZeroVSOneOrMoreRows: function () {
    if (EcomCart.cartHasItems) {
      window.jQuery(EcomCart.selectorShowOnZeroItems).hide()
      window.jQuery(EcomCart.selectorHideOnZeroItems).each(function (i, el) {
        if (!window.jQuery(el).hasClass('hideForNow')) {
          window.jQuery(el).show()
        }
      })
    } else {
      window.jQuery(EcomCart.selectorShowOnZeroItems).show()
      window.jQuery(EcomCart.selectorHideOnZeroItems).hide()
    }
  },

  // ##########################################
  // HELPER FUNCTIONS
  // ##########################################

  /**
   * cleaning up strings
   * @param String str
   * @return string
   */
  escapeHTML: function (str) {
    return str
  },

  /**
   * check if a particular variable is set
   * @param Mixed
   * @return bool
   */
  variableIsSet: function (variable) {
    if (typeof variable === 'undefined' || variable == 'undefined') {
      return false
    }
    return true
  },

  /**
   * check if a particular variable is set AND has a value
   * @param Mixed
   * @return bool
   */
  variableSetWithValue: function (variable) {
    if (EcomCart.variableIsSet(variable)) {
      if (variable) {
        return true
      }
    }
    return false
  },

  // #################################
  // Simple Dialogue
  // #################################

  /**
   * Setup dialogue links
   */
  initColorboxDialog: function () {
    window
      .jQuery(document)
      .on('click', EcomCart.colorboxDialogSelector, function (e) {
        EcomCart.colorboxDialogOptions.href = window.jQuery(this).attr('href')
        EcomCart.colorboxDialogOptions.open = true
        window.jQuery.colorbox(EcomCart.colorboxDialogOptions)
        return false
      })
  }
}

window.EcomCart = EcomCart

jQuery(() => {
  EcomCart.init()
})
