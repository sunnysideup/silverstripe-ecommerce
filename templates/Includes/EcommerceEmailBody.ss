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
                    <% include Order_ShopInfo %>
                </th>
            </tr>

            <tr class="message">
                <td class="left">
                    <h1 class="title">$Subject</h1>
                    <% if OrderStepMessage %><div class="orderStepMessage">$OrderStepMessage</div><% end_if %>
                </td>
            </tr>

        </thead>
        <tbody>

            <tr>
                <td>
<% if Order %>
    <% with Order %>
                    <div id="OrderInformation">
                        <h2 class="orderHeading"><% if RetrieveLink %><a href="$RetrieveLink"><% end_if %>$Title<% if RetrieveLink %></a><% end_if %></h2>
                        <% include Order_OrderStatusLogs %>
                        <% include Order_CustomerNote %>
                        <% include Order_Addresses %>
                        <% include Order_Content %>
                        <% include Order_Payments %>
                        <% include Order_OutstandingTotal %>
                        <% include Order_FeedbackFormLink %>
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
