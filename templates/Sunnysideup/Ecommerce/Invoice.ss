<% include Sunnysideup\Ecommerce\Includes\HeaderAndFooter\BasicPageHeader Title='Invoice' %>


<div style="page-break-after: always;" >
    <% include Sunnysideup\Ecommerce\Includes\Order_ShopInfo_Invoice %>
    <% with Order %>
        <% include Sunnysideup\Ecommerce\Includes\Order %>
        <% include Sunnysideup\Ecommerce\Includes\Order_FeedbackFormLink %>
    <% end_with %>
</div>
<script type="text/javascript">if (window ==window.top) {window.setTimeout(function(){window.print();}, 1000);}</script>


<% include Sunnysideup\Ecommerce\Includes\HeaderAndFooter\BasicPageFooter %>
