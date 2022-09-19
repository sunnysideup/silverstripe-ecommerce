<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <title>$Subject</title>
</head>
<body>
<div id="EmailContent" style="margin: 20px;">
    <table border="0" cellspacing="0" width="100%">
        <tr>
            <td class="holderTd">
                <table>
                    <thead>
                        <tr>
                            <th>
                                <div class="logo">
                                    $SiteConfig.Title
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <table id="Content">
                                    <thead>

                                        <tr class="shopAddress">
                                            <th>
                                                <% if Order %>
                                                    <% with Order %>
                                                        <% with EcomConfig %>
                                                            <h1 class="title">
                                                                $InvoiceTitle<% if $Subject %> - $Subject <% end_if %>
                                                            </h1>
                                                        <% end_with %>
                                                    <% end_with %>
                                                <% end_if %>
                                            </th>
                                        </tr>

                                        <tr class="message">
                                            <td class="left">
                                                <% if Message %>
                                                    <h3 class="checkout-message">
                                                        <span>$Message.RAW</span>
                                                    </h3>
                                                <% end_if %>
                                                <% if $OrderStepMessage %>
                                                    <h3 class="checkout-message">
                                                        $Subject
                                                    </h3>
                                                    <div class="order-step-message"><% if $Order.RetrieveLink %><a href="$Order.RetrieveLink">$Order.Title - Click to view and update Online</a><% else %>$Order.Title<% end_if %></d>
                                                    <div class="order-step-message">$OrderStepMessage.RAW</span>
                                                <% end_if %>
                                            </td>
                                        </tr>

                                    </thead>
                                    <tbody>

                                        <tr>
                                            <td>
                            <% if Order %>
                                <% with Order %>
                                                <div id="OrderInformation">
                                                    <p class="checkout-message">
                                                        <span><% if RetrieveLink %><a href="$RetrieveLink">$Title - Click to view and update Online</a><% else %>$Title<% end_if %></span>
                                                    </p>
                                                    <% include Sunnysideup\Ecommerce\Includes\Order_OrderStatusLogs %>
                                                    <% include Sunnysideup\Ecommerce\Includes\Order_PickUpOrDeliveryNote %>
                                                    <% include Sunnysideup\Ecommerce\Includes\Order_Addresses %>
                                                    <h2>Order Summary</h2>
                                                    <% include Sunnysideup\Ecommerce\Includes\Order_Content %>
                                                    <% include Sunnysideup\Ecommerce\Includes\Order_Payments %>
                                                    <% include Sunnysideup\Ecommerce\Includes\Order_OutstandingTotal %>
                                                    <% include Sunnysideup\Ecommerce\Includes\Order_CustomerNote %>
                                                </div>
                                <% end_with %>
                            <% else %>
                                                <p class="warning message">There was an error in retrieving this order. Please contact the store.</p>
                            <% end_if %>
                                            </td>
                                        </tr>

                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                    <% if Order %>
                        <% with Order %>
                            <tfoot>
                                <% with EcomConfig %>
                                    <tr id="ShopInfo">
                                        <td>
                                            <% if ReceiptEmail %>
                                                <div id="ShopEmaillAddress">
                                                    If you have any questions regarding your order, please contact us<br>
                                                    <a href="mailto:$ReceiptEmail">$ReceiptEmail</a>
                                                </div>
                                            <% end_if %>
                                            <% if ShopPhysicalAddress %>
                                                <div id="ShopPhysicalAddress">$ShopPhysicalAddress</div>
                                            <% end_if %>
                                        </td>
                                    </tr>
                                <% end_with %>
                                <tr>
                                    <td>
                                        <div class="footer">
                                            <h5 class="footer__heading">Thank you for shopping with us!</h5>
                                            <% include Sunnysideup\Ecommerce\Includes\Order_FeedbackFormLink %>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        <% end_with %>
                    <% end_if %>

                </table>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
