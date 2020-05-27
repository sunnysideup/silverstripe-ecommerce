<div id="OrderInformation">

    <h2 class="orderHeading"><a href="$RetrieveLink">$Title</a></h2>

    <% include Sunnysideup\Ecommerce\Includes\Order_OrderStatusLogs %>

    <% include Sunnysideup\Ecommerce\Includes\Order_Addresses %>

    <% include Sunnysideup\Ecommerce\Includes\Order_Content %>

    <% include Sunnysideup\Ecommerce\Includes\Order_Payments %>

    <% include Sunnysideup\Ecommerce\Includes\Order_OutstandingTotal %>

    <% include Sunnysideup\Ecommerce\Includes\Order_CustomerNote %>

    <% include Sunnysideup\Ecommerce\Includes\Order_PrintAndMail %>


</div>


<% require themedCSS(Order, ecommerce) %>
<% require themedCSS(Order_Print, ecommerce, print) %>
