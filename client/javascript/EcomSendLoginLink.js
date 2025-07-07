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
          const securityToken = field.dataset.securityToken
          const email = field.value
          const params = new URLSearchParams()
          params.append('email', email)
          params.append('backurl', window.location.href)
          params.append('securitytoken', securityToken)

          fetch(loginLink, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
          }).then(response => {
            console.log(response)
            if (response.ok) {
              field.parentElement.classList.add('login-link-sent')
              field.title = response.text()
            }
          })
        })
      })
    }
  }

  jQuery(() => {
    EcomSendLoginLink.init()
  })
}
