<p class="priceDisplay">
<% if Price != 0 %>
	<% if Quantifier %><span class="mainQuantifier">$Quantifier: </span><% end_if %>
	<% if HasDiscount %>
	<del>$Price.Nice</del> $CalculatedPrice.Nice
	<% else %>
	$CalculatedPrice.Nice
	<% end_if %>
	<% if Currency %><span class="currencyQuantifier">$Currency</span><% end_if %>
<% else %>
<% _t("Product.FREE", "FREE") %>
<% end_if %>
</p>
