<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <title><% _t('PRINT_ALL_PACKING_SLIPS', 'Print Packing Slips for Selected Orders') %></title>
</head>
<body>
    <% loop $Orders %>
        <div style="page-break-after: always;" id="Wrapper">
            <h1 class="title">
                <% if PackingSlipTitle %>
                    $PackingSlipTitle
                <% else %>
                    <% _t("Order.PACKING_SLIP", "Packing Slip") %>
                <% end_if %>
            </h1>
            <div id="AddressesHolder">
                <div id="Sender" class="section">
                    <h3><% _t("Order.SENDER", "Sender:") %></h3>
                    <% with EcomConfig %>
                        <div id="ShopInfo">
                            <% if EmailLogo %><img src="$EmailLogo.getAbsoluteURL" alt="Logo - $EmailLogo.Title" /><% end_if %>
                            <% if ShopPhysicalAddress %><div id="ShopPhysicalAddress">$ShopPhysicalAddress</div><% end_if %>
                        </div>
                    <% end_with %>
                </div>
                <div id="Recipient" class="section">
                    <h3><% _t("Order.DELIVER_TO", "Deliver to:") %></h3>
                    <% include Order_AddressShipping %>
                </div>
                <div class="clear"></div>
            </div>
            <div id="ItemsHolder" class="section">
                <h3><% _t("Order.ITEMS", "Items:") %></h3>
                <% include Order_Content_Items_Only_No_Prices %>
            </div>
            <div id="PackingSlipNote">$PackingSlipNote</div>
        </div>
        <hr class="multi-print-separator"/>
    <% end_loop %>
    <script type="text/javascript">if (window ==window.top) {window.setTimeout(function(){window.print();}, 1000);}</script>
</body>
</html>
