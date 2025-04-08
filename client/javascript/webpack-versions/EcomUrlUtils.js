EcomUrlUtils = {
  joinUrlWithSlash: function (...strings) {
    const hasQuery = strings.some(str => str.includes('?'))
    return strings
      .map(str => str.replace(/\/+$/, ''))
      .join('/')
      .replace(/([^:]\/)\/+/g, '$1')
      .replace(hasQuery ? /\/+(\?|$)/ : /\/+$/, '$1')
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
  }
}
export default EcomUrlUtils
