<% include BasicPageHeader Title='Processing' %>
<script type="text/javascript" src="/framework/thirdparty/jquery/jquery.js"></script>

<div id="Outer">
    <div id="Inner">
        <div id="PaymentLogoImage">$Logo</div>

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: /images/ (case sensitive)
  * NEW: /client/images/ (COMPLEX)
  * EXP: Check new location, also see: https://docs.silverstripe.org/en/4/developer_guides/templates/requirements/#direct-resource-urls
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        <div id="PaymentLoadingImage"><img src="ecommerce/client/images/loading.gif" alt="Loading image"></div>
        <div id="PaymentFormHolder">$Form</div>
    </div>
</div>
<style type="text/css">
    html, body {
        width: 100%;
        height: 100%;
        margin: 0 auto;
        background-color: #ffffff;
    }
    body {
        text-align: center;
    }
    #Outer {
        text-align: center;
        position: relative;
        top: 50%;
        left: 50%;
    }
    #Inner {
        position: absolute;
        margin-top: -200px;
        margin-left: -200px;
        height: 400px;
        width: 400px;
        overflow: auto;
    }
    #Inner img {
        display: block;
        margin: 10px 0;
        border: 0;
    }
</style>
<% include BasicPageFooter %>
