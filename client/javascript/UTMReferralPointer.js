const getVarsToExpect = [
  // Standard UTM
  'utm_source',
  'utm_medium',
  'utm_campaign',
  'utm_term',
  'utm_content',

  // Google Ads / Analytics
  'gclid',
  'gclsrc',
  'gad',
  'gbraid',
  'wbraid',
  'dclid',

  // Facebook / Meta
  'fbclid',
  'fb_clickid',
  'fbc',
  'fbp',

  // Microsoft / Bing
  'msclkid',

  // TikTok
  'ttclid',

  // Twitter / X
  'twclid',

  // LinkedIn
  'li_fat_id',

  // Pinterest
  'epik',

  // Snapchat
  'sccid',

  // Generic / Affiliate / Referral
  'ref',
  'rf',
  'referrer',
  'referral',
  'referral_code',
  'affid',
  'affsource',
  'aff_sub',
  'aff_sub2',
  'aff_sub3',
  'aff_sub4',
  'aff_sub5',
  'subid',
  'sub_id',
  'partner',
  'partnerid',
  'cid',
  'campaignid',
  'adid',
  'creative',
  'clickid',

  // Channel grouping
  'organic',
  'direct',
  'social',
  'email',
  'push',
  'other'
]

// one-bucket storage key
const storageKey = `${window.location.host}.utm_data`

const hasUTMParameters = () => {
  try {
    const raw = localStorage.getItem(storageKey)
    if (!raw) return false
    const obj = JSON.parse(raw)
    return Object.keys(obj).some(k => getVarsToExpect.includes(k) && obj[k])
  } catch {
    return false
  }
}

const metaKeys = ['_capturedAt', '_landingUrl', '_referrer']

// write-once save incl. meta
const saveFirstTouch = data => {
  if (!hasUTMParameters() && Object.keys(data).length) {
    const meta = {
      _capturedAt: new Date().toISOString(),
      _landingUrl: window.location.href,
      _referrer: document.referrer || ''
    }
    saveToStorage({ ...data, ...meta })
  }
}

const getUTMParameters = () => {
  const params = new URLSearchParams(window.location.search)
  const found = {}
  getVarsToExpect.forEach(k => {
    if (params.has(k)) {
      const v = params.get(k)
      if (v != null && v !== '') found[k] = v
    }
  })
  return found
}

const loadFromStorage = (includeMeta = false) => {
  try {
    const raw = localStorage.getItem(storageKey)
    if (!raw) return {}
    const obj = JSON.parse(raw)
    if (includeMeta) return obj

    // default: only UTM/trackers
    const filtered = {}
    getVarsToExpect.forEach(k => {
      if (obj?.[k]) filtered[k] = obj[k]
    })
    return filtered
  } catch {
    return {}
  }
}

const saveToStorage = data => {
  try {
    const existing = loadFromStorage()
    const merged = { ...existing, ...data }
    localStorage.setItem(storageKey, JSON.stringify(merged))
  } catch {
    /* ignore */
  }
}

const clearStorage = () => {
  try {
    localStorage.removeItem(storageKey)
  } catch {
    /* ignore */
  }
}

const sendToServer = utmData => {
  if (!window.LinkToSendReferral) return
  const controller = new AbortController()
  const timeout = setTimeout(() => controller.abort(), 15000)
  const query = new URLSearchParams(utmData).toString()
  const url = `${window.LinkToSendReferral}?${query}`
  fetch(url, { signal: controller.signal })
    .then(r => r.text())
    .then(t => {
      if (Number.parseInt(t, 10) > 0) clearStorage()
    })
    .catch(() => {})
    .finally(() => clearTimeout(timeout))
}

// ---- main
;(() => {
  if (!hasUTMParameters()) {
    const fromUrl = getUTMParameters()
    saveFirstTouch(fromUrl)
  }
  if (hasUTMParameters() && window.LinkToSendReferral) {
    const utmAndMeta = loadFromStorage(true) // include meta for server
    if (Object.keys(utmAndMeta).length) sendToServer(utmAndMeta)
  }
})()
