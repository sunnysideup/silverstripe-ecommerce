<div class="productActions">
<% if HasVariations %>
        <% include ProductGroupItemPrice %>
        <% include ProductActionsInner %>
<% else %>
    <% if canPurchase %>
        <% include ProductGroupItemPrice %>
        <% include ProductActionsInner %>
    <% else %>
        <div class="notForSale message">$EcomConfig.NotForSaleMessage</div>
    <% end_if %>
<% end_if %>
</div>
