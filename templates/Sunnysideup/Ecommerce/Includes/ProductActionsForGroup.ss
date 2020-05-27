<div class="productActions">
<% if HasVariations %>
        <% include Sunnysideup\Ecommerce\Includes\ProductGroupItemPrice %>
        <% include Sunnysideup\Ecommerce\Includes\ProductActionsInner %>
<% else %>
    <% if canPurchase %>
        <% include Sunnysideup\Ecommerce\Includes\ProductGroupItemPrice %>
        <% include Sunnysideup\Ecommerce\Includes\ProductActionsInner %>
    <% else %>
        <div class="notForSale message">$EcomConfig.NotForSaleMessage</div>
    <% end_if %>
<% end_if %>
</div>
