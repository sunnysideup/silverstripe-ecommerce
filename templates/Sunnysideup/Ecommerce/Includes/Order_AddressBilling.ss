<% if BillingAddressID %>
    <% with BillingAddress %>
<address class="addressSection  <% if $Order.IsSeparateShippingAddress %>separate-shipping-address<% else %>no-separate-shipping-address<% end_if %>" id="BillingAddressSection">
    <% include Sunnysideup\Ecommerce\Includes\Order_AddressBillingInner %>
</address>
    <% end_with %>
<% else %>
<p>No billing address available.</p>
<% end_if %>
