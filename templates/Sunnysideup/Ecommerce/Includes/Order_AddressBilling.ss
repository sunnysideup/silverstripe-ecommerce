<% if BillingAddressID %>
    <% with BillingAddress %>

    <% include Sunnysideup\Ecommerce\Includes\Order_AddressBillingContact %>
    <% include Sunnysideup\Ecommerce\Includes\Order_AddressBillingAddress %>

    <% end_with %>
<% else %>
<p>No billing address available.</p>
<% end_if %>
