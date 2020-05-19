<% if $FeedbackLink %>
    <h4>
        Do have any feedback about the order process?
    </h4>
    <a href="$FeedbackLink">

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: /images/ (case sensitive)
  * NEW: /client/images/ (COMPLEX)
  * EXP: Check new location, also see: https://docs.silverstripe.org/en/4/developer_guides/templates/requirements/#direct-resource-urls
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        <img src="/ecommerce/client/images/feedback-icon.png">
        Leave Feedback Now
    </a>
<% end_if %>
