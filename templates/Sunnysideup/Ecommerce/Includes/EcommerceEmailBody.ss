<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>$Subject</title>
</head>
<body>
<div id="EmailContent" style="margin: 20px">
    <table id="Content">
        <thead>

            <tr class="shopAddress">
                <th>
                    <% include Sunnysideup\Ecommerce\Includes\Order_ShopInfo %>
                </th>
            </tr>

            <tr class="message">
                <td class="left">
                    <h1 class="title">$Subject</h1>
                    <% if OrderStepMessage %><div class="orderStepMessage">$OrderStepMessage.Raw</div><% end_if %>
                </td>
            </tr>

        </thead>
        <tbody>

            <tr>
                <td>
<% if Order %>
    <% with Order %>
                    <div id="OrderInformation">
                    <h2 class="orderHeading"><% if RetrieveLink %><a href="$RetrieveLink"><% end_if %>$Title - click to update order<% if RetrieveLink %></a><% end_if %></h2>
                        <% include Sunnysideup\Ecommerce\Includes\Order_OrderStatusLogs %>
                        <% include Sunnysideup\Ecommerce\Includes\Order_CustomerNote %>
                        <% include Sunnysideup\Ecommerce\Includes\Order_Addresses %>
                        <% include Sunnysideup\Ecommerce\Includes\Order_Content %>
                        <% include Sunnysideup\Ecommerce\Includes\Order_Payments %>
                        <% include Sunnysideup\Ecommerce\Includes\Order_OutstandingTotal %>
                        <% include Sunnysideup\Ecommerce\Includes\Order_FeedbackFormLink %>
                    </div>
    <% end_with %>
<% else %>
                    <p class="warning message">There was an error in retrieving this order. Please contact the store.</p>
<% end_if %>
                </td>
            </tr>

        </tbody>
    </table>

</div>
</body>
</html>
