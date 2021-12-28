/**
 *@author Nicolaas [at] sunnysideup.co.nz
 *
 * @todoset up a readonly system
 *
 **/
if (
  document.getElementsByClassName("div.password.field.text") !== null &&
  typeof document.getElementsByClassName("div.password.field.text") !==
    "undefined"
) {
  (function ($) {
    $(document).ready(function () {
      EcomPasswordField.init();
    });
  })(window.jQuery);

  var EcomPasswordField = {
    chars: "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz",

    passwordFieldInputSelectors: "div.password.field.text",

    errorMessageSelector: "div.password.field.text span.message",

    choosePasswordLinkSelector: ".passwordToggleLink",

    stringLength: 14,

    //toggles password selection and enters random password so that users still end up with a password
    //even if they do not choose one.
    init: function () {
      var yesLabel = window.jQuery(
        EcomPasswordField.choosePasswordLinkSelector
      ).text();
      if (
        !window.jQuery(EcomPasswordField.choosePasswordLinkSelector).attr("datayes")
      ) {
        window.jQuery(EcomPasswordField.choosePasswordLinkSelector).attr(
          "datayes",
          yesLabel
        );
      }

      if (window.jQuery(EcomPasswordField.passwordFieldInputSelectors).length) {
        window.jQuery(document).on(
          "click",
          EcomPasswordField.choosePasswordLinkSelector,
          function () {
            window.jQuery(EcomPasswordField.passwordFieldInputSelectors).slideToggle(
              function () {
                if (
                  window.jQuery(EcomPasswordField.passwordFieldInputSelectors).is(
                    ":visible"
                  )
                ) {
                  var newPassword = "";
                  var newLabel = window.jQuery(
                    EcomPasswordField.choosePasswordLinkSelector
                  ).attr("datano");
                } else {
                  var newPassword = EcomPasswordField.passwordGenerator();
                  var newLabel = window.jQuery(
                    EcomPasswordField.choosePasswordLinkSelector
                  ).attr("datayes");
                }
                window.jQuery(EcomPasswordField.choosePasswordLinkSelector).text(
                  newLabel
                );
                window.jQuery(EcomPasswordField.passwordFieldInputSelectors).each(
                  function (i, el) {
                    window.jQuery(el).find("input").val(newPassword);
                  }
                );
              }
            );
            return false;
          }
        );
        window.jQuery(EcomPasswordField.choosePasswordLinkSelector).click();
      }

      window.jQuery("form").on("click", ".btn-toolbar input", function () {
        var notAllHaveSomething = false;
        //reset to avoid auto-fills
        window.jQuery(EcomPasswordField.passwordFieldInputSelectors).each(function (
          i,
          el
        ) {
          if (
            window.jQuery(el).find("input").val() == "" ||
            window.jQuery(el).is(":hidden")
          ) {
            notAllHaveSomething = true;
          }
        });
        if (notAllHaveSomething) {
          window.jQuery(EcomPasswordField.passwordFieldInputSelectors).each(function (
            i,
            el
          ) {
            window.jQuery(el).find("input").val("");
          });
        }
      });
      //show passwords straight away IF there is an error
      if (window.jQuery(EcomPasswordField.errorMessageSelector).length) {
        window.jQuery(EcomPasswordField.choosePasswordLinkSelector).click();
      }
    },

    //generates random password
    passwordGenerator: function () {
      return "";
      var randomstring = "";
      for (var i = 0; i < this.stringLength; i++) {
        var rnum = Math.floor(Math.random() * this.chars.length);
        randomstring += this.chars.substring(rnum, rnum + 1);
      }
      return randomstring;
    },
  };
}
