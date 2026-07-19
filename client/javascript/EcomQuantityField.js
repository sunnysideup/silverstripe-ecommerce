import EcomCart from './EcomCart'

/**
 * Keeps the cart quantity in sync over AJAX.
 *
 * Attaches — through delegation — to every `.ecomquantityfield` block:
 *  - `button.removeOneLink`    decrease by one
 *  - `button.addOneLink`       increase by one
 *  - `input.ajaxQuantityField` typed / pasted values
 *
 * Nothing ever navigates the page. Updates are sent as an AJAX POST to the
 * quantity form's action:
 *  - the whole form is serialised into the body, so the SecurityID (CSRF
 *    token) travels the way Silverstripe expects;
 *  - the quantity is included in the body AND the query string, so both
 *    `$request->postVar('quantity')` and `$request->getVar('quantity')`
 *    style controllers find it;
 *  - `X-Requested-With: XMLHttpRequest` is set, so `Director::is_ajax()` /
 *    `$request->isAjax()` is true and the ShoppingCart controller responds
 *    with its JSON changes instead of a redirect.
 *
 * The JSON response is handed to `EcomCart.processChanges()` — the same
 * routine `EcomCart.getChanges()` uses — so the cart re-renders exactly as
 * it does for every other cart update.
 */
const EcomQuantityField = {
  /** Hide the +/- buttons once JS has taken over. */
  hidePlusAndMinues: true,

  /** Name of the request parameter carrying the quantity. */
  quantityParameterName: 'quantity',

  /** How long to wait after the last edit before sending an update. */
  typingDelay: 1000,

  delegateRootSelector: 'body',
  mainSelector: '.ecomquantityfield',
  quantityFieldSelector: 'input.ajaxQuantityField',
  removeSelector: 'form button.removeOneLink',
  addSelector: 'form button.addOneLink',
  completedClass: 'ajaxCompleted',
  URLSegmentHiddenFieldSelectorAppendix: '_SetQuantityLink',

  /** Pending debounce timers, keyed by field name. */
  timers: Object.create(null),

  /** Last value actually sent, keyed by field name. */
  lastValue: Object.create(null),

  /** In-flight request controllers, keyed by field name. */
  aborters: Object.create(null),

  set_delegateRootSelector (selector) {
    this.delegateRootSelector = selector
  },

  unset_delegateRootSelector () {
    this.delegateRootSelector = 'body'
  },

  // ----------------------------------------------------------------- setup

  init () {
    const root = this.getRoot()
    if (!root) {
      return
    }

    EcomCart.reinitCallbacks.push(() => this.reinit())

    /**
     * Delegated listener. `focus` / `blur` do not bubble, so their bubbling
     * counterparts are used instead.
     */
    const on = (eventName, childSelector, handler) => {
      root.addEventListener(eventName, e => {
        const target =
          e.target instanceof Element ? e.target.closest(childSelector) : null
        if (target && target.closest(this.mainSelector)) {
          handler(target, e)
        }
      })
    }

    on('click', this.removeSelector, (button, e) => {
      e.preventDefault()
      this.step(button, -1)
    })

    on('click', this.addSelector, (button, e) => {
      e.preventDefault()
      this.step(button, 1)
    })

    // Nothing here is allowed to reload the page — not the +/- buttons, and
    // not hitting Enter in the quantity field.
    on('submit', 'form', (form, e) => {
      e.preventDefault()
      const field = this.getQuantityField(form)
      if (field && form.contains(field)) {
        this.update(field)
      }
    })

    on('focusin', this.quantityFieldSelector, field => {
      this.lastValue[field.name] = field.value
    })

    // `input` rather than `keydown`: it also covers pasting with the mouse,
    // browser autofill and the native number spinners.
    on('input', this.quantityFieldSelector, field => {
      this.clearTimer(field)
      this.timers[field.name] = window.setTimeout(
        () => this.update(field),
        this.typingDelay
      )
    })

    on('change', this.quantityFieldSelector, field => {
      this.update(field)
    })

    on('focusout', this.quantityFieldSelector, field => {
      this.update(field)
    })

    this.listenForJQueryChanges(root)
    this.reinit()
  },

  /**
   * jQuery's `.trigger('change')` does not emit a native DOM event, so a
   * vanilla listener would never see changes fired by other parts of the
   * e-commerce module. Bridge those across when jQuery is present.
   *
   * `originalEvent` is undefined only for jQuery-triggered events, which
   * stops native changes being handled twice.
   */
  listenForJQueryChanges (root) {
    const jq = window.jQuery
    if (!jq) {
      return
    }

    jq(root).on(
      'change',
      `${this.mainSelector} ${this.quantityFieldSelector}`,
      e => {
        if (!e.originalEvent) {
          this.update(e.target)
        }
      }
    )
  },

  /**
   * Prepare any `.ecomquantityfield` block that has not been handled yet.
   * Safe to call repeatedly — after the cart is re-rendered, for example.
   */
  reinit () {
    const root = this.getRoot()
    if (!root) {
      return
    }

    root.querySelectorAll(this.mainSelector).forEach(wrapper => {
      wrapper.classList.add(this.completedClass)

      if (this.hidePlusAndMinues) {
        wrapper
          .querySelectorAll(`${this.removeSelector}, ${this.addSelector}`)
          .forEach(button => {
            button.style.display = 'none'
          })
      }

      wrapper.querySelectorAll(this.quantityFieldSelector).forEach(field => {
        field.removeAttribute('disabled')
        this.toggleRemoveButton(field, parseFloat(field.value) || 0)
      })
    })
  },

  // --------------------------------------------------------------- actions

  /**
   * Increase / decrease the quantity field belonging to a +/- button.
   */
  step (button, amount) {
    const field = this.getQuantityField(button)
    if (!field) {
      return
    }

    field.value = (parseFloat(field.value) || 0) + amount
    this.update(field)
  },

  /**
   * Normalise the field, then send it if the value actually moved.
   */
  update (field) {
    this.clearTimer(field)

    const quantity = this.normaliseValue(field)
    this.toggleRemoveButton(field, quantity)

    if (this.lastValue[field.name] === String(quantity)) {
      return
    }
    this.lastValue[field.name] = String(quantity)

    this.send(field, quantity)
  },

  /**
   * POST the update. A newer edit for the same field aborts any request
   * still in flight, so responses can never arrive out of order.
   *
   * This mirrors the bookkeeping of `EcomCart.getChanges()` exactly:
   * loading classes via `addLoadingSelector()`, the `openAjaxCalls`
   * counter, the `onBeforeUpdate` hook and the extra params — then hands
   * the JSON response to `EcomCart.setChanges()`, which applies the
   * changes to the page, runs the reinit callbacks and removes the
   * loading classes, just as it does for every other cart update.
   */
  async send (field, quantity) {
    const built = this.buildUrl(field, quantity)
    if (!built) {
      return
    }

    this.aborters[field.name]?.abort()
    const aborter = new AbortController()
    this.aborters[field.name] = aborter

    // Serialising the form keeps the SecurityID in the POST body, which is
    // where Silverstripe's SecurityToken checks look first.
    const body = new URLSearchParams(
      field.form ? new FormData(field.form) : undefined
    )
    body.set(this.quantityParameterName, quantity)

    // Same extra params getChanges() sends (as GET vars), in the same
    // order. They go into both the query string and the body so the
    // controller finds them whether it reads getVar, postVar or requestVar.
    const url = new URL(built)
    const extras = {}
    if (EcomCart.ajaxButtonsOn) {
      extras.ajaxButtonsOn = 'true'
    }
    if (EcomCart.openAjaxCalls > 1) {
      extras.manyrequests = '1'
    }
    const loadingIndex = this.showLoading(field)
    if (loadingIndex !== null) {
      extras.loadingindex = String(loadingIndex)
    }
    for (const [key, value] of Object.entries(extras)) {
      body.set(key, value)
      url.searchParams.set(key, value)
    }

    if (typeof EcomCart.onBeforeUpdate === 'function') {
      // .call(url, …) replicates the original's (odd but relied-upon)
      // signature, where the url arrives as `this`.
      EcomCart.onBeforeUpdate.call(url.toString(), body, EcomCart.setChanges)
    }

    EcomCart.openAjaxCalls++
    field.setAttribute('aria-busy', 'true')

    try {
      const response = await fetch(url.toString(), {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json'
        },
        signal: aborter.signal
      })

      if (!response.ok) {
        throw new Error(`Cart update failed: HTTP ${response.status}`)
      }

      EcomCart.setChanges(await response.json(), 'success')
    } catch (error) {
      // setChanges() never ran, so undo its share of the bookkeeping.
      EcomCart.openAjaxCalls--

      if (error.name === 'AbortError') {
        return // superseded by a newer edit; its response cleans up
      }

      this.hideLoading(loadingIndex)
      // Forget the "sent" value so the next change / retry sends again.
      delete this.lastValue[field.name]
      field.dispatchEvent(
        new CustomEvent('ecomquantityfield:error', {
          bubbles: true,
          detail: { error, quantity }
        })
      )
      console.error(error)
    } finally {
      if (this.aborters[field.name] === aborter) {
        this.aborters[field.name] = null
        field.setAttribute('aria-busy', 'false')
      }
    }
  },

  /**
   * Show EcomCart's loading state: `.loading` on the field's wrapper and
   * `ecomCartIsUpdating` on <body>. Returns the loading index, which
   * `setChanges()` uses to clean up.
   */
  showLoading (field) {
    if (typeof EcomCart.addLoadingSelector === 'function') {
      return EcomCart.addLoadingSelector(field)
    }
    return null
  },

  /**
   * Manual clean-up for the error path only — on success,
   * `EcomCart.setChanges()` removes the loading classes itself.
   */
  hideLoading (loadingIndex) {
    const jq = window.jQuery
    if (!jq) {
      return
    }
    jq('body').removeClass(EcomCart.classToShowPageIsUpdating)
    const el = EcomCart.loadingSelectors?.[loadingIndex]
    if (el) {
      jq(el).removeClass(EcomCart.classToShowLoading)
    }
  },

  // --------------------------------------------------------------- helpers

  getRoot () {
    return document.querySelector(this.delegateRootSelector)
  },

  /**
   * Resolve the set-quantity URL against the document base (which honours
   * any <base href>) and append the quantity to the query string as well,
   * so `getVar('quantity')`-style controllers are covered too.
   */
  buildUrl (field, quantity) {
    const segment = this.getSetQuantityURLSegment(field)
    if (!segment) {
      return ''
    }

    const url = new URL(
      segment.replace(/&amp;/g, '&'),
      document.baseURI || window.location.href
    )
    url.searchParams.set(this.quantityParameterName, quantity)

    return url.toString()
  },

  /**
   * Prefer the hidden field holding the set-quantity link, then the form's
   * own action, then the data attribute.
   */
  getSetQuantityURLSegment (field) {
    if (field.form && field.form.getAttribute('action')) {
      return field.form.getAttribute('action')
    }
    return field.getAttribute('data-quantity-link') || ''
  },

  /**
   * Strip anything that is not a number and clamp to min-value / max-value.
   * Writes the corrected value back into the field and returns it.
   */
  normaliseValue (field) {
    const min = parseFloat(field.getAttribute('min-value')) || 1
    const max = parseFloat(field.getAttribute('max-value')) || 0

    let value = parseFloat(String(field.value).replace(/[^0-9.]+/g, ''))
    if (isNaN(value) || value < min) {
      value = min
    }
    if (max && value > max) {
      value = max
    }

    field.value = value

    return value
  },

  /**
   * The "-" button is pointless at a quantity of one, so hide it.
   */
  toggleRemoveButton (field, quantity) {
    const wrapper = field.closest(this.mainSelector)
    if (!wrapper) {
      return
    }

    wrapper.querySelectorAll(this.removeSelector).forEach(button => {
      button.style.visibility = quantity < 2 ? 'hidden' : 'visible'
    })
  },

  /**
   * The +/- buttons and the input each sit in their own <form>, so look the
   * field up in the shared `.ecomquantityfield` wrapper rather than among
   * siblings.
   */
  getQuantityField (el) {
    const wrapper = el.closest(this.mainSelector)

    return wrapper ? wrapper.querySelector(this.quantityFieldSelector) : null
  },

  /**
   * Field names come from the CMS and may contain characters that are not
   * valid in a CSS selector.
   */
  escape (value) {
    return typeof CSS !== 'undefined' && CSS.escape
      ? CSS.escape(value)
      : String(value).replace(/["\\]/g, '\\$&')
  },

  clearTimer (field) {
    window.clearTimeout(this.timers[field.name])
    this.timers[field.name] = null
  },

  debug () {
    document
      .querySelectorAll(
        [
          this.addSelector,
          this.removeSelector,
          this.quantityFieldSelector
        ].join(', ')
      )
      .forEach(el => {
        el.style.border = '3px solid red'
      })
  }
}

if (document.getElementsByClassName('ecomquantityfield').length) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () =>
      EcomQuantityField.init()
    )
  } else {
    EcomQuantityField.init()
  }
}

export default EcomQuantityField
