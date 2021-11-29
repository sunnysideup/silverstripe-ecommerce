<div id="OrderInformation">

    <h2 class="orderHeading"><a href="$RetrieveLink">$Title</a></h2>

    <% include Sunnysideup\Ecommerce\Includes\Order_Addresses %>

    <% include Sunnysideup\Ecommerce\Includes\Order_Content %>

    <% include Sunnysideup\Ecommerce\Includes\Order_CustomerNote %>

</div>


<% require themedCSS("client/css/Order.css") %>
<% require themedCSS("client/css/Order_Print.css", print) %>
