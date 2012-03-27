<div class="productActionsHolder">
<% if HasVariations %>
	<% include ProductActionsInner %>
<% else %>
	<% if canPurchase %>
	<% if Price != 0 %>
		<p class="priceDisplay">
			<% if Quantifier %><span class="mainQuantifier">$Quantifier: </span><% end_if %>
		<% if HasDiscount %>
			<del>$Price.Nice</del> $CalculatedPrice.Nice
		<% else %>
			$CalculatedPrice.Nice
		<% end_if %>
			<% if Currency %><span class="currencyQuantifier">$Currency</span><% end_if %>
		</p>
	<% end_if %>
	<% include ProductActionsInner %>
	<% end_if %>
<% end_if %>
</div>

