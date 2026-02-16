<% if UseShippingAddress %>
    <% if ShippingAddress %>
        <% with ShippingAddress %>
<address class="addressSection <% if $Order.IsSeparateShippingAddress %>separate-shipping-address<% else %>no-separate-shipping-address<% end_if %>" id="ShippingAddressSection">
    <% include Sunnysideup\Ecommerce\Includes\Order_AddressShippingInner %>
</address>
        <% end_with %>
    <% else  %>
        <p>
            <% _t("NOSHIPPINGADDRESSAVAILABLE", "No shipping address available.") %>
        </p>
    <% end_if %>
<% else %>
    <% if BillingAddressID %>
        <% with BillingAddress %>
<address class="addressSection <% if $Order.IsSeparateShippingAddress %>separate-shipping-address<% else %>no-separate-shipping-address<% end_if %>" id="BillingAddressSection">
    <% include Sunnysideup\Ecommerce\Includes\Order_AddressBillingContactInner %>
    <% include Sunnysideup\Ecommerce\Includes\Order_AddressBillingAddressInner %>
</address>
        <% end_with %>
    <% end_if %>
<% end_if %>
