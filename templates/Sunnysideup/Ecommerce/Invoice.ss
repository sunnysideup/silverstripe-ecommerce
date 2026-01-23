<% include Sunnysideup\Ecommerce\Includes\HeaderAndFooter\BasicPageHeader Title='Invoice' %>


<div >
    <% include Sunnysideup\Ecommerce\Includes\Order_ShopInfo_Invoice %>
    <% with Order %>
        <% include Sunnysideup\Ecommerce\Includes\Order %>
        <% include Sunnysideup\Ecommerce\Includes\Order_FeedbackFormLink %>
    <% end_with %>
</div>
<script>if (window ==window.top) {window.setTimeout(function(){window.print();}, 1000);}</script>


<% include Sunnysideup\Ecommerce\Includes\HeaderAndFooter\BasicPageFooter %>
