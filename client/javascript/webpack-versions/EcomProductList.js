import EcomUrlUtils from './EcomUrlUtils.js'

const EcomProductList = {
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

  // #################################
  // INIT AND RESET FUNCTIONS
  // #################################

  /**
   * initialises all the ajax functionality
   */
  init: function () {
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
    // allow ajax product list back and forth:
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
    myUrl = EcomUrlUtils.mergeUrlParamsForAjax(myUrl, dataResetFor)
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
    let url = EcomUrlUtils.joinUrlWithSlash(
      window.jQuery('base').attr('href'),
      EcomCart.shoppingCartURLSegment
    )
    if (method) {
      url = EcomUrlUtils.joinUrlWithSlash(url, method)
    }
    if (variable) {
      url = EcomUrlUtils.joinUrlWithSlash(url, variable)
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

jQuery(() => {
  EcomProductList.init()
})
