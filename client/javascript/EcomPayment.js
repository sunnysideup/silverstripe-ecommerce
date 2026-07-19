/**
 * Ecommerce payment method selection.
 *
 * - No jQuery.
 * - Shows only the field group belonging to the selected payment method.
 * - Fields opt in to validation via `data-require-when-active` in the markup.
 *   They carry NO `required` attribute server-side; the script adds it only
 *   while their payment method is selected, and removes it again when it is not.
 */
;(function () {
  'use strict'

  const CONFIG = {
    radioContainers: [
      '#OrderForm_OrderForm_PaymentMethod',
      '#OrderFormPayment_PaymentForm_PaymentMethod'
    ],
    fieldGroupSelector: 'div.paymentfields',
    disabledClass: 'methodDisabled',
    classPrefix: 'methodFields_',
    activeClass: 'active',
    // Marker attribute. Put it on the field itself, or on any ancestor inside
    // the payment group (e.g. the _Holder div) to cover every field within.
    requireAttr: 'data-require-when-active'
  }

  const FIELD_SELECTOR = 'input, select, textarea'

  /** Method value encoded in the group's `methodFields_*` class. */
  function methodOfGroup (group) {
    const cls = Array.from(group.classList).find(c =>
      c.startsWith(CONFIG.classPrefix)
    )
    return cls ? cls.slice(CONFIG.classPrefix.length) : null
  }

  /** Fields inside `group` that are marked as required-when-active. */
  function requirableFields (group) {
    const marked = new Set()

    group.querySelectorAll('[' + CONFIG.requireAttr + ']').forEach(node => {
      // Skip explicit opt-outs, e.g. data-require-when-active="0".
      if (node.getAttribute(CONFIG.requireAttr) === '0') return

      if (node.matches(FIELD_SELECTOR)) {
        marked.add(node)
      } else {
        node.querySelectorAll(FIELD_SELECTOR).forEach(f => marked.add(f))
      }
    })

    return Array.from(marked)
  }

  function applyState (group, isActive) {
    group.classList.toggle(CONFIG.activeClass, isActive)

    requirableFields(group).forEach(field => {
      if (isActive) {
        field.required = true
        field.setAttribute('aria-required', 'true')
      } else {
        field.required = false
        field.removeAttribute('aria-required')
        if (typeof field.setCustomValidity === 'function') {
          field.setCustomValidity('')
        }
      }
    })
  }

  function init () {
    const container = CONFIG.radioContainers
      .map(sel => document.querySelector(sel))
      .find(Boolean)
    if (!container) return

    const form = container.closest('form')
    if (!form) return

    const radios = Array.from(container.querySelectorAll('input[type="radio"]'))
    if (!radios.length) return

    // Groups belonging to a payment method, minus the ones marked unavailable.
    const groups = Array.from(
      form.querySelectorAll(CONFIG.fieldGroupSelector)
    ).filter(el => !el.classList.contains(CONFIG.disabledClass))

    function select (value) {
      groups.forEach(group => applyState(group, methodOfGroup(group) === value))
    }

    radios.forEach(radio => {
      radio.addEventListener('change', () => {
        if (radio.checked) select(radio.value)
      })
    })

    // Clicking anywhere in a group selects that method.
    groups.forEach(group => {
      group.addEventListener('click', event => {
        const method = methodOfGroup(group)
        if (!method) return
        const radio = radios.find(r => r.value === method)
        if (!radio || radio.checked) return
        event.stopPropagation()
        radio.checked = true
        radio.dispatchEvent(new Event('change', { bubbles: true }))
      })
    })

    // The radio list itself stays hidden; the groups act as the UI.
    container.style.display = 'none'

    const checked = radios.find(r => r.checked) || radios[0]
    checked.checked = true
    checked.dispatchEvent(new Event('change', { bubbles: true }))
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true })
  } else {
    init()
  }
})()
