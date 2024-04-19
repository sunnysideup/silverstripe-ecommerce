<% include Sunnysideup\Ecommerce\Includes\HeaderAndFooter\BasicPageHeader Title='Processing' %>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>

<div id="Outer">
    <div id="Inner">
        <div id="PaymentLogoImage">$Logo.RAW</div>
        <div id="PaymentLoadingImage">
            <img src="$resourceURL('sunnysideup/ecommerce:client/images/loading.gif')" alt="Loading image" />
        </div>
        <div id="PaymentFormContent">$Content</div>
        <div id="PaymentFormHolder">$Form.RAW</div>
    </div>
</div>
<style type="text/css">
    html, body {
        margin: 0 auto;
        background-color: #ffffff;
    }
    body, * {
        text-align: center;
    }
    #Outer {
        padding-top: 10vh;
        margin: 0 auto;
        max-width: 600px;
    }
    #Inner img {
        display: block;
        margin: 30px auto;
        border: 0;
    }
</style>
<% include Sunnysideup\Ecommerce\Includes\HeaderAndFooter\BasicPageFooter %>
