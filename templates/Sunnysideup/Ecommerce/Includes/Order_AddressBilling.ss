<% if BillingAddressID %>
    <% with BillingAddress %>
<address class="addressSection" id="BillingAddressSection">
    <% include Sunnysideup\Ecommerce\Includes\Order_AddressBillingInner %>
</address>
    <% end_with %>
<% else %>
<p>No billing address available.</p>
<% end_if %>
