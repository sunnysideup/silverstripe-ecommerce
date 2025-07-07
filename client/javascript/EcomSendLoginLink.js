/**
 *@author Nicolaas [at] sunnysideup.co.nz
 * sends a login link to the user
 * so that they can log in without a password
 * (if they have shopped with us before)
 **/

if (document.getElementById('OrderFormAddress_OrderFormAddress_Email')) {
  const EcomSendLoginLink = {
    init () {
      document.querySelectorAll('[data-login-link]').forEach(field => {
        field.addEventListener('change', event => {
          const loginLink = field.dataset.loginLink
          const email = field.value

          fetch(loginLink, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              email: email,
              backurl: window.location.href
            })
          })
        })
      })
    }
  }

  jQuery(() => {
    EcomSendLoginLink.init()
  })
}
