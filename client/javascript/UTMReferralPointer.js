// one-bucket storage key
const storageKey = `${location.host}.utm_data`
const metaKeys = new Set([
  '_capturedAt',
  '_landingUrl',
  '_referrer',
  '_uniqueID'
])
const expirationTime = 7776000000 // ~90 days

const parseQuery = () => {
  const u = new URL(location.href)
  // capture all query params as-is
  return Object.fromEntries(u.searchParams.entries())
}

// add this helper
// replace isExpired with millis-based check
// expiry (millis)
const isExpired = () => {
  try {
    const { _capturedAt } = loadFromStorage(true)
    if (!_capturedAt) return false
    const t = new Date(_capturedAt).getTime()
    return Number.isFinite(t) && Date.now() - t > expirationTime
  } catch {
    return false
  }
}

const hasOnlyMeta = obj => Object.keys(obj).every(k => metaKeys.has(k))
const hasMoreThanMeta = obj => Object.keys(obj).some(k => !metaKeys.has(k))

const loadFromStorage = (includeMeta = false) => {
  try {
    const raw = localStorage.getItem(storageKey)
    if (!raw) return {}
    const obj = JSON.parse(raw)
    if (includeMeta) return obj
    // strip meta keys by default
    return Object.fromEntries(
      Object.entries(obj).filter(([k]) => !metaKeys.has(k))
    )
  } catch {
    return {}
  }
}
const makeUUID = () =>
  globalThis.crypto && typeof globalThis.crypto.randomUUID === 'function'
    ? globalThis.crypto.randomUUID()
    : String(Math.floor(Math.random() * Number.MAX_SAFE_INTEGER))

const hasAnyParamsStored = () => {
  if (isExpired()) {
    clearStorage()
    return false
  }
  const obj = loadFromStorage(true)
  return typeof obj._uniqueID !== 'undefined' || hasMoreThanMeta(obj)
}

const saveFirstTouch = params => {
  if (hasAnyParamsStored()) return

  const hasParams = Object.keys(params).length > 0

  let uniqueID = 'none'
  if (hasParams) uniqueID = makeUUID()
  const dataToSave = {
    ...params,
    _capturedAt: new Date().toISOString(),
    _landingUrl: location.pathname,
    _referrer: document.referrer || '',
    _uniqueID: uniqueID
  }

  saveToStorage(dataToSave)
}
const saveToStorage = data => {
  try {
    const existing = loadFromStorage(true)
    // do not overwrite existing first-touch keys; only fill blanks
    const merged = { ...data, ...existing }
    localStorage.setItem(storageKey, JSON.stringify(merged))
  } catch {
    /* ignore quota / JSON issues */
  }
}

const clearStorage = () => {
  try {
    localStorage.removeItem(storageKey)
  } catch {
    /* ignore */
  }
}

// helper: schedule without blocking rendering
const runWhenIdle = cb =>
  'requestIdleCallback' in window
    ? requestIdleCallback(cb, { timeout: 2000 })
    : setTimeout(cb, 0)

// non-blocking send: prefer sendBeacon, else fetch keepalive
const sendToServer = data => {
  if (!window.LinkToSendReferral) return

  const url = window.LinkToSendReferral

  // --- Fallback: async fetch with keepalive + success check ---
  const controller = new AbortController()
  const timeout = setTimeout(() => controller.abort(), 15000)

  fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    keepalive: true,
    signal: controller.signal,
    body: JSON.stringify(data)
  })
    .then(r => r.text())
    .then(t => {
      // Optional: handle your own success logic here
      if (Number.parseInt(t, 10) > 0) clearStorage()
    })
    .catch(() => {
      console.warn('Failed to send referral data')
    })
    .finally(() => clearTimeout(timeout))
}

// boot: do the light work on DOM ready, defer network to idle
document.addEventListener('DOMContentLoaded', () => {
  if (isExpired()) clearStorage()

  const params = parseQuery()
  saveFirstTouch(params)

  if (window.LinkToSendReferral && hasAnyParamsStored()) {
    const all = loadFromStorage(true)
    if (Object.keys(all).length && !hasOnlyMeta(all)) {
      runWhenIdle(() => sendToServer(all))
    }
  }
})
