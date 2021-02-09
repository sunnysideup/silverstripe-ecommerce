<% if Currencies %>
<div class="ecommerceWidget currencyWidget">
    <% loop Currencies %>
        <% include Sunnysideup\Ecommerce\Includes\Sidebar_Currency_Inner %>
    <% end_loop %>
</div>
<% end_if %>
