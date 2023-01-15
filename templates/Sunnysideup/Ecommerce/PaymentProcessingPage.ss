<% include Sunnysideup\Ecommerce\Includes\HeaderAndFooter\BasicPageHeader Title='Processing' %>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>

<div id="Outer">
    <div id="Inner">
        <div id="PaymentLogoImage">$Logo.RAW</div>
        <div id="PaymentLoadingImage">
            <img src="$resourceURL('sunnysideup/ecommerce:client/images/loading.gif')" alt="Loading image" />
        </div>
        <div id="PaymentFormHolder">$Form.RAW</div>
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
<% include Sunnysideup\Ecommerce\Includes\HeaderAndFooter\BasicPageFooter %>
