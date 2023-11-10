function getUTMParameters () {
  const params = new URLSearchParams(window.location.search)
  const utmParams = {}
  ;['utm_source', 'utm_medium', 'utm_campaign'].forEach(param => {
    if (params.has(param)) {
      utmParams[param] = params.get(param)
    }
  })
  return utmParams
}

function saveToLocalStorage (data) {
  for (const key in data) {
    localStorage.setItem(key, data[key])
  }
}

function getUTMDataFromLocalStorage () {
  const utmData = {}
  ;['utm_source', 'utm_medium', 'utm_campaign'].forEach(param => {
    const value = localStorage.getItem(param)
    if (value) {
      utmData[param] = value
    }
  })
  return utmData
}

function clearLocalStorage () {
  ;[
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_term',
    'utm_content'
  ].forEach(param => {
    localStorage.removeItem(param)
  })
}

function sendUTMDataToServer (utmData) {
  const baseTag = document.querySelector('base')
  const baseHref = baseTag ? baseTag.getAttribute('href') : null
  if (baseHref) {
    fetch(baseHref + '/shoppingcart/addreferral', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(utmData)
    })
      .then(response => response.text())
      .then(data => {
        if (parseInt(data) > 0) {
          clearLocalStorage()
        }
      })
      .catch(error => console.error('Error:', error))
  }
}

// Main execution
let utmParams = getUTMParameters()
if (Object.keys(utmParams).length > 0) {
  saveToLocalStorage(utmParams)
} else {
  utmParams = getUTMDataFromLocalStorage()
}

if (Object.keys(utmParams).length > 0) {
  sendUTMDataToServer(utmParams)
}
