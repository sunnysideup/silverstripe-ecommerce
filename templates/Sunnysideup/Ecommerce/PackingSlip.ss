<% include Sunnysideup\Ecommerce\Includes\HeaderAndFooter\BasicPageHeader Title='Packing Slip' %>


<div style="page-break-after: always;" id="Wrapper">
    <h1 class="title"><% if PackingSlipTitle %>$PackingSlipTitle<% else %><% _t("Order.PACKING_SLIP", "Packing Slip") %><% end_if %></h1>
    <div id="AddressesHolder">
        <div id="Sender" class="section">
            <h3><% _t("Order.SENDER", "Sender:") %></h3>
            <% include Sunnysideup\Ecommerce\Includes\Order_ShopInfo_PackingSlip %>
        </div>
    <% with Order %>
        <div id="Recipient" class="section">
            <h3><% _t("Order.DELIVER_TO", "Deliver to:") %></h3>
            <% include Sunnysideup\Ecommerce\Includes\Order_AddressShipping %>
        </div>
        <div class="clear"></div>
    </div>
    <div id="ItemsHolder" class="section">
        <h3><% _t("Order.ITEMS", "Items:") %></h3>
        <% include Sunnysideup\Ecommerce\Includes\Order_Content_Items_Only_No_Prices %>
    </div>
    <% include Sunnysideup\Ecommerce\Includes\Order_FeedbackFormLink %>
    <% end_with %>
</div>
<div id="PackingSlipNote">$PackingSlipNote</div>
<script type="text/javascript">if (window ==window.top) {window.setTimeout(function(){window.print();}, 1000);}</script>


<% include Sunnysideup\Ecommerce\Includes\HeaderAndFooter\BasicPageFooter %>
