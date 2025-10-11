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

// Flatten data to URLSearchParams (arrays -> repeated keys)
const toParams = obj => {
  const p = new URLSearchParams()
  for (const [k, v] of Object.entries(obj || {})) {
    if (v == null) continue
    if (Array.isArray(v)) v.forEach(x => p.append(k, String(x)))
    else if (typeof v === 'object')
      p.append(k, JSON.stringify(v)) // no deep expansion
    else p.append(k, String(v))
  }
  return p
}

// non-blocking send: prefer sendBeacon, else fetch keepalive
async function sendToServer (data) {
  if (!window.LinkToSendReferral) return
  const url = window.LinkToSendReferral
  const params = toParams(data)

  // Try to survive page close
  if (document.visibilityState === 'hidden' && navigator.sendBeacon) {
    const body = new Blob([params.toString()], {
      type: 'application/x-www-form-urlencoded;charset=UTF-8'
    })
    if (navigator.sendBeacon(url, body)) clearStorage?.()
    return
  }

  // Normal path with timeout + abort
  const controller = new AbortController()
  const timeout = setTimeout(() => controller.abort(), 15000)
  try {
    const res = await fetch(url, {
      method: 'POST',
      body: params, // fetch sets Content-Type to x-www-form-urlencoded
      signal: controller.signal
    })
    const text = await res.text()
    if (Number.parseInt(text, 10) > 0) {
      clearStorage()
    } else {
      console.warn('Server did not accept referral data:', text)
    }
  } catch (err) {
    console.warn('Failed to send referral data:', err?.name || err)
  } finally {
    clearTimeout(timeout)
  }
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
