<div class="priceDisplay">
<% if Price != 0 %>
	<% if Quantifier %><span class="mainQuantifier">$Quantifier: </span><% end_if %>
	<% if HasDiscount %><del>$Price.Nice</del><% end_if %>
	<% if HasVariations %>
	<span class="calculatedPrice"><% _t("Product.FROM", "From") %> $LowestVariationPriceAsMoney.NiceDefaultFormat</span>
	<% else %>
	<span class="calculatedPrice">$CalculatedPriceAsMoney.NiceDefaultFormat</span>
	<% end_if %>
<% else %>
	<span class="calculatedPrice free"><% _t("Product.FREE", "FREE") %></span>
<% end_if %>
</div>
