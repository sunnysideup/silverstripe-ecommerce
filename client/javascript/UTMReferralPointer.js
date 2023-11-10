function getUTMParameters () {
  // console.log('getting params from url')
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
  // console.log('getting data from local storage')
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
  // console.log('clearing local storage')
  ;[('utm_source', 'utm_medium', 'utm_campaign')].forEach(param => {
    localStorage.removeItem(param)
  })
}

function sendUTMDataToServer (utmData) {
  // console.log('sending to server')
  const baseTag = document.querySelector('base')
  let baseHref = baseTag ? baseTag.getAttribute('href') : null

  if (baseHref) {
    // Remove trailing slash if it exists
    baseHref = baseHref.endsWith('/') ? baseHref.slice(0, -1) : baseHref
    const queryParams = new URLSearchParams(utmData).toString()
    const url = `${baseHref}/shoppingcart/addreferral?${queryParams}`

    fetch(url)
      .then(response => response.text())
      .then(data => {
        // console.log(data)
        if (parseInt(data) > 0) {
          clearLocalStorage()
        }
      })
      .catch(error => console.error('Error:', error))
  }
}

if (window.location.search && window.location.search.indexOf('utm_') > -1) {
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
}
