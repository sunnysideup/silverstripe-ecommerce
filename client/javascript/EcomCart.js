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

// const originalSetTimeout = window.setTimeout
// window.setTimeout = function (...args) {
//   // console.log('setTimeout called with args:', args)
//   // console.log('setTimeout called from:')
//   console.trace()
//   return originalSetTimeout.apply(this, args)
// }

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
   * The group filter sidebar (groupfilter links) is NOT part of the
   * ajaxified product list holder, so its link hrefs are never refreshed
   * when a product list is loaded via ajax - only the product grid is
   * swapped. This means every groupfilter link's href always reflects the
   * groupfilter selection as it was at the last full page load.
   *
   * We capture that initial selection once (in `init`), so that later
   * clicks can work out what a single, possibly-stale link is actually
   * meant to toggle, and apply that toggle against the live/current
   * selection instead - allowing more than one group filter to be
   * selected at once without one click replacing the previous one.
   *
   * @var array
   */
  initialGroupFilterTokens: [],

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
    // get variables!
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
    // capture the groupfilter selection baked into the page at full load time
    // (see initialGroupFilterTokens for why this is needed)
    EcomCart.initialGroupFilterTokens = EcomCart.getGroupFilterTokens(
      window.location.href
    )
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
          window.jQuery(EcomCart.selectorChangeCountryLink).trigger('click')
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
            const dataResetFor = currentEl.getAttribute('data-reset-for')
            const rawUrl = currentEl.getAttribute('href')
            const url = EcomCart.applyGroupFilterToggle(rawUrl, dataResetFor)

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
                    option => option.value === rawUrl
                  )
                  if (optionExists) {
                    select.value = rawUrl
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
          const rawUrl = currentEl.value
          const selectedOption = currentEl.options[currentEl.selectedIndex]
          const dataResetFor = selectedOption.getAttribute('data-reset-for')
          // unlike the plain filter links (which can be combined), the
          // select dropdown only ever allows a single group to be picked
          // at a time - managing several selections through one <select>
          // is not practical
          const url = EcomCart.applyGroupFilterSingleSelection(
            rawUrl,
            dataResetFor
          )
          EcomCart.ajaxLoadProductList(url, dataResetFor, () => {
            const holder = currentEl.closest(
              EcomCart.ajaxifiedListAdjusterSelectors
            )
            holder.querySelectorAll('a.current').forEach(link => {
              link.classList.remove('current')
            })
            const matchingLink = holder.querySelector(`a[href='${rawUrl}']`)
            if (matchingLink) {
              matchingLink.classList.add('current')
            }
          })
        }
      })

      // group filter form (checkboxes on desktop, select on small screens).
      // This is a form rather than links so that robots do not follow it.
      document.body.addEventListener('change', function (event) {
        const form = event.target.closest('form[data-group-filter-form]')
        if (!form) {
          return
        }
        let tokens = []
        if (event.target.tagName === 'SELECT') {
          if (event.target.value) {
            tokens = [event.target.value]
          }
        } else {
          form
            .querySelectorAll('input.group-filter-checkbox:checked')
            .forEach(function (cb) {
              tokens.push(cb.value)
            })
        }
        const url =
          window.location.pathname +
          (tokens.length
            ? '?groupfilter=' + encodeURIComponent(tokens.join(','))
            : '')
        // the refreshed filter markup replaces this form, no callback needed
        EcomCart.ajaxLoadProductList(
          url,
          tokens.length ? null : 'groupfilter',
          function () {}
        )
      })
      document.body.addEventListener('submit', function (event) {
        if (event.target.closest('form[data-group-filter-form]')) {
          event.preventDefault()
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

  /**
   * disables / enables the group filter form inputs, so they can not be
   * clicked while a product list is being reloaded.
   */
  setGroupFilterFormsDisabled: function (disabled) {
    document
      .querySelectorAll('form[data-group-filter-form]')
      .forEach(function (form) {
        form.classList.toggle('is-loading', disabled)
        form.querySelectorAll('input, select').forEach(function (el) {
          el.disabled = disabled
        })
      })
  },

  ajaxLoadProductList: function (myUrl, dataResetFor, myCallBack) {
    EcomCart.hasAjaxProductLoad = true
    // console.log('AJAX Load Product List:', myUrl) // Debug: Check if URL is correct
    myUrl = EcomCart.mergeUrlParamsForAjax(myUrl, dataResetFor)
    window.jQuery.ajax({
      beforeSend: function () {
        EcomCart.setGroupFilterFormsDisabled(true)
        window
          .jQuery(EcomCart.ajaxifiedListHolderSelector)
          .addClass(EcomCart.classToShowLoading)
      },
      // cache: false,
      complete: function () {
        EcomCart.setGroupFilterFormsDisabled(false)
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
        EcomCart.applyAjaxProductListResponse(data, myUrl)

        const holderEl = document.querySelector(
          EcomCart.ajaxifiedListHolderSelector
        )
        if (holderEl) {
          holderEl.dispatchEvent(
            new CustomEvent('SSU:DOMContentUpdated', {
              bubbles: true,
              detail: { updatedElement: holderEl }
            })
          )
        }

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

  /**
   * builds (or rebuilds) the "select" dropdown version of a filter/sort
   * list, used on smaller screens when there are too many options to show
   * as a plain list of links.
   *
   * @param {ParentNode} root optional - scope the search for filter/sort
   *                          groups to within this element only (e.g. a
   *                          container that was just refreshed via ajax).
   *                          Defaults to the whole document.
   */
  addSelectForSmallerSpaces: function (root) {
    const searchRoot = root || document
    const filterSortLinks = searchRoot.querySelectorAll(
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
      if (!dropdown) {
        return
      }

      // clear out any options from a previous run - this function may be
      // called again (e.g. after an ajax refresh of this group's markup),
      // and the select element itself is not replaced, so without this
      // options would be duplicated every time
      dropdown.innerHTML = ''

      // Add list items to the select dropdown
      Array.from(listItems).forEach(item => {
        const link = item.querySelector('a')
        if (!link) {
          return
        }
        const url = new URL(link.href)
        const pathAndQuery = url.pathname + url.search
        const dataResetFor = link.getAttribute('data-reset-for')
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

  /**
   * The ajax product list response may contain two named partials (see
   * AjaxProductList.ss):
   * - #ProductGroupGroupFiltersHolder: a freshly rendered copy of the
   *   group filter sidebar, with links/hrefs reflecting the live,
   *   possibly multi-group, selection
   * - #ProductGroupProductListPartial: the actual product cards
   *
   * When both are present, each is used to update its matching live
   * element so that, after an ajax product list reload, the group filter
   * links are no longer stale (fixing more than one group filter being
   * selectable at once). If the response doesn't contain these partials
   * (e.g. a different ajaxified list elsewhere on the site), it falls
   * back to the previous behaviour of setting the whole response as the
   * product list holder's content.
   *
   * @param {String} data   the raw html returned by the ajax product list request
   * @param {String} newUrl the (merged/toggled) url that was requested
   */
  applyAjaxProductListResponse: function (data, newUrl) {
    let filtersPartial = null
    let productListPartial = null

    if (typeof window.DOMParser !== 'undefined') {
      const parsedDoc = new window.DOMParser().parseFromString(
        data,
        'text/html'
      )
      filtersPartial = parsedDoc.getElementById('ProductGroupGroupFiltersHolder')
      productListPartial = parsedDoc.getElementById(
        'ProductGroupProductListPartial'
      )
    }

    if (productListPartial) {
      window
        .jQuery(EcomCart.ajaxifiedListHolderSelector)
        .html(productListPartial.innerHTML)
    } else {
      window.jQuery(EcomCart.ajaxifiedListHolderSelector).html(data)
    }

    if (filtersPartial) {
      const liveFiltersHolder = document.getElementById(
        'ProductGroupGroupFiltersHolder'
      )
      if (liveFiltersHolder) {
        liveFiltersHolder.innerHTML = filtersPartial.innerHTML
        // the newly inserted markup has its own (empty) select dropdown
        // for smaller screens - rebuild it (and re-apply the
        // show-list/show-dropdown classes) for this holder only, so it
        // doesn't end up shown empty, and so it doesn't duplicate options
        // in any other, unrelated, filter/sort dropdown on the page
        EcomCart.addSelectForSmallerSpaces(liveFiltersHolder)
        // the selected groupfilter(s) have just changed, so the baseline
        // snapshot used by applyGroupFilterToggle needs to move forward too
        // (use the url that was just requested, not window.location, since
        // the history pushState for it hasn't happened yet at this point)
        if (newUrl) {
          EcomCart.initialGroupFilterTokens = EcomCart.getGroupFilterTokens(
            newUrl
          )
        }
      }
    }
  },

  /**
   * Returns the array of groupfilter tokens (comma separated group
   * segments, e.g. "my-group.123") present in the given URL, or an empty
   * array if there is no groupfilter param.
   *
   * @param {String} url
   * @return {Array}
   */
  getGroupFilterTokens: function (url) {
    try {
      const urlObj = new URL(url, window.location.origin)
      const raw = urlObj.searchParams.get('groupfilter')
      if (!raw) {
        return []
      }

      return raw
        .split(',')
        .map(token => token.trim())
        .filter(Boolean)
    } catch (e) {
      return []
    }
  },

  /**
   * Returns the tokens that are present in only one of the two given
   * arrays (i.e. the symmetric difference).
   *
   * @param {Array} a
   * @param {Array} b
   * @return {Array}
   */
  symmetricDifferenceOfTokens: function (a, b) {
    const result = []
    a.forEach(function (token) {
      if (b.indexOf(token) === -1) {
        result.push(token)
      }
    })
    b.forEach(function (token) {
      if (a.indexOf(token) === -1 && result.indexOf(token) === -1) {
        result.push(token)
      }
    })

    return result
  },

  /**
   * The groupfilter sidebar links are not refreshed when the product list
   * is loaded via ajax (only the product grid is) - see
   * `initialGroupFilterTokens` above for the full explanation. This means
   * a link's href always reflects the groupfilter selection as it was at
   * the last full page load, rather than the live/current selection,
   * which would otherwise cause one group filter selection to replace
   * another instead of being added to it.
   *
   * This method works out, from a (possibly stale) link's own href, which
   * single group it is meant to toggle on or off - by comparing it
   * against the selection that was present at the initial page load -
   * and then applies that one toggle against the CURRENT, live selection
   * (taken from the address bar), keeping any other groups already
   * selected.
   *
   * Links that have nothing to do with groupfilter (sort/filter/display
   * links, or the groupfilter "reset to all" link) are returned
   * unchanged.
   *
   * @param {String} url          the href of the link/option that was activated
   * @param {String} dataResetFor the type being reset by this link, if any
   * @return {String} the url to actually load, with the groupfilter
   *                   selection merged in
   */
  applyGroupFilterToggle: function (url, dataResetFor) {
    if ('groupfilter' === dataResetFor) {
      // "All" / reset link - always clears the whole selection
      return url
    }

    const hrefTokens = EcomCart.getGroupFilterTokens(url)
    const initialTokens = EcomCart.initialGroupFilterTokens || []

    if (0 === hrefTokens.length && 0 === initialTokens.length) {
      // this link has nothing to do with groupfilter (e.g. a sort,
      // filter, or display link)
      return url
    }

    const changedTokens = EcomCart.symmetricDifferenceOfTokens(
      hrefTokens,
      initialTokens
    )
    if (1 !== changedTokens.length) {
      // can't work out, with confidence, which single group this link is
      // meant to toggle - fall back to the href exactly as rendered
      return url
    }

    const ownToken = changedTokens[0]
    const currentTokens = EcomCart.getGroupFilterTokens(window.location.href)
    const position = currentTokens.indexOf(ownToken)
    let newTokens
    if (-1 === position) {
      newTokens = currentTokens.concat([ownToken])
    } else {
      newTokens = currentTokens.slice()
      newTokens.splice(position, 1)
    }

    const urlObj = new URL(url, window.location.origin)
    if (newTokens.length) {
      urlObj.searchParams.set('groupfilter', newTokens.join(','))
    } else {
      urlObj.searchParams.delete('groupfilter')
    }

    return urlObj.pathname + urlObj.search
  },

  /**
   * Unlike the plain filter links (which can be combined - see
   * applyGroupFilterToggle above), the small-screen "select" dropdown
   * version of the group filter list only ever allows a single group to
   * be selected at a time: managing several selections through a single
   * <select> is not practical from a usability point of view.
   *
   * The dropdown's options are built from the same (possibly already
   * combined) hrefs as the plain links - e.g. an option may have a value
   * of "groupfilter=a,b" if "a" was already selected when "b"'s option
   * was built (see combineGroupFilterValue on the server). The group this
   * particular option represents is always the LAST token in that list,
   * since the server always appends a newly added group at the end.
   *
   * This reduces the option's value down to just that single group,
   * discarding any others, so that picking an option from the dropdown
   * always replaces rather than adds to the current groupfilter
   * selection.
   *
   * @param {String} url          the href/value of the option that was selected
   * @param {String} dataResetFor the type being reset by this option, if any
   * @return {String} the url to actually load, with only a single group selected
   */
  applyGroupFilterSingleSelection: function (url, dataResetFor) {
    if ('groupfilter' === dataResetFor) {
      // "All" / reset option - always clears the whole selection
      return url
    }

    const hrefTokens = EcomCart.getGroupFilterTokens(url)
    if (0 === hrefTokens.length) {
      // this option has nothing to do with groupfilter (e.g. a sort,
      // filter, or display option)
      return url
    }

    const ownToken = hrefTokens[hrefTokens.length - 1]

    const urlObj = new URL(url, window.location.origin)
    urlObj.searchParams.set('groupfilter', ownToken)

    return urlObj.pathname + urlObj.search
  },

  // #################################
  // SETUP PAGE
  // #################################

  resolveLink (element) {
    var url = element.dataset.link || window.jQuery(element).attr('href')
    return url
  },

  resolveAddRemoveLink (element) {
    var url = EcomCart.resolveLink(element)
    if (EcomCart.productListIsFromCachedSource) {
      url += (url.includes('?') ? '&' : '?') + 'cached=1'
    }
    return url
  },
  /**
   * Helper function to handle AJAX form submission for adding/removing items
   * via fetch (POST), mirroring the logic of getChanges().
   */
  postCartUpdate: async function (form, button) {
    if (!form) return

    // Resolve URL from the form's action attribute
    const urlStr = form.getAttribute('action')
    const url = new URL(urlStr, document.baseURI || window.location.href)

    // Serialise the form (includes SecurityID and other hidden fields)
    const body = new URLSearchParams(new FormData(form))
    const extras = {}

    if (EcomCart.ajaxButtonsOn) {
      extras.ajaxButtonsOn = 'true'
    }
    if (EcomCart.openAjaxCalls > 1) {
      extras.manyrequests = '1'
    }

    const loadingIndex = EcomCart.addLoadingSelector(button)
    if (loadingIndex !== null) {
      extras.loadingindex = String(loadingIndex)
    }

    for (const [key, value] of Object.entries(extras)) {
      body.set(key, value)
      url.searchParams.set(key, value)
    }

    if (typeof EcomCart.onBeforeUpdate === 'function') {
      EcomCart.onBeforeUpdate.call(url.toString(), body, EcomCart.setChanges)
    }

    EcomCart.openAjaxCalls++
    button.setAttribute('aria-busy', 'true')

    try {
      const response = await fetch(url.toString(), {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json'
        }
      })

      if (!response.ok) {
        throw new Error(`Cart update failed: HTTP ${response.status}`)
      }

      const changes = await response.json()
      EcomCart.setChanges(changes, 'success')
    } catch (error) {
      EcomCart.openAjaxCalls--

      // Clean up loading states on error
      if (EcomCart.loadingSelectors[loadingIndex]) {
        EcomCart.loadingSelectors[loadingIndex].classList.remove(
          EcomCart.classToShowLoading
        )
      }
      document.body.classList.remove(EcomCart.classToShowPageIsUpdating)
      console.error(error)
    } finally {
      button.setAttribute('aria-busy', 'false')
    }
  },

  /**
   * adds the "add to cart" ajax functionality to links/buttons.
   * @param String withinSelector: area where these links can be found
   */
  addAddLinks: function (withinSelector) {
    const containers = document.querySelectorAll(withinSelector)
    containers.forEach(container => {
      container.addEventListener('click', function (event) {
        const button = event.target.closest(EcomCart.addLinkSelector)
        const isExcluded = event.target.closest(EcomCart.excludedPagesSelector)

        if (button && !isExcluded) {
          event.preventDefault()
          const form = button.closest('form')

          if (form) {
            EcomCart.postCartUpdate(form, button)
          } else {
            // Fallback for legacy <a> links without a form
            const url = EcomCart.resolveAddRemoveLink(button)
            EcomCart.getChanges(url, null, button)
          }
        }
      })
    })
  },

  /**
   * add ajax functionality to "remove from cart" links/buttons outside the cart
   * @param String withinSelector: area where these links can be found
   */
  addRemoveLinks: function (withinSelector) {
    const containers = document.querySelectorAll(withinSelector)
    containers.forEach(container => {
      container.addEventListener('click', function (event) {
        const button = event.target.closest(EcomCart.removeLinkSelector)
        const isExcluded = event.target.closest(EcomCart.excludedPagesSelector)

        if (button && !isExcluded) {
          event.preventDefault()
          if (
            EcomCart.unconfirmedDelete ||
            confirm(EcomCart.confirmDeleteText)
          ) {
            const form = button.closest('form')

            if (form) {
              EcomCart.postCartUpdate(form, button)
            } else {
              // Fallback for legacy <a> links without a form
              const url = EcomCart.resolveAddRemoveLink(button)
              EcomCart.getChanges(url, null, button)
            }
          }
        }
      })
    })
  },

  /**
   * adds the "remove from cart" ajax functionality to links/buttons IN THE CART!
   * @param String withinSelector: area where these links can be found
   */
  addCartRemove: function (withinSelector) {
    const containers = document.querySelectorAll(withinSelector)
    containers.forEach(container => {
      container.addEventListener('click', function (event) {
        const button = event.target.closest(EcomCart.removeCartSelector)

        if (button) {
          event.preventDefault()
          if (
            !EcomCart.confirmDeleteText ||
            confirm(EcomCart.confirmDeleteText)
          ) {
            const orderItemHolder = button.closest(
              EcomCart.orderItemHolderSelector
            )

            if (orderItemHolder) {
              // Vanilla JS equivalent of jQuery slideUp() & remove()
              orderItemHolder.style.transition = 'all 0.4s ease-out'
              orderItemHolder.style.overflow = 'hidden'
              orderItemHolder.style.height = orderItemHolder.offsetHeight + 'px'

              // Force repaint before transitioning to 0
              orderItemHolder.offsetHeight

              orderItemHolder.style.height = '0px'
              orderItemHolder.style.paddingTop = '0px'
              orderItemHolder.style.paddingBottom = '0px'
              orderItemHolder.style.marginTop = '0px'
              orderItemHolder.style.marginBottom = '0px'
              orderItemHolder.style.opacity = '0'
              orderItemHolder.style.border = 'none'

              setTimeout(() => {
                orderItemHolder.remove()
              }, 400) // Wait for transition to finish
            }

            const form = button.closest('form')
            if (form) {
              EcomCart.postCartUpdate(form, button)
            } else {
              // Fallback for legacy <a> links without a form
              const url = EcomCart.resolveLink(button)
              EcomCart.getChanges(url, null, button)
            }
          }
        }
      })
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

export default EcomCart
