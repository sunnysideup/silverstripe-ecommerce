<div class="productActions">
<% if HasVariations %>
    <% if VariationForm %>$VariationForm<% end_if %>
<% else %>
    <% if canPurchase %>
        <% include ProductGroupItemPrice %>
        <% if Quantifier %><span class="mainQuantifier">$Quantifier</span><% end_if %>
        <% include ProductActionsInner %>
    <% else %>
        <% if IsOlderVersion %>
        <p class="message warning">
            <%t ProductGroup.VIEWINGOLDERVERSION 'You are viewing an older version of {name}' name=$Title %>
            <% _t("ProductGroup.YOUMAYALSOVIEW","You may also view") %>
            <a href="$Link"><% _t("ProductGroup.CURRENTVERSION","the current version") %></a>.
        </p>
        <% else %>
        <div class="notForSale message">$EcomConfig.NotForSaleMessage</div>
        <% end_if %>
    <% end_if %>
<% end_if %>
</div>
