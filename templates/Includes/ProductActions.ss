<div class="productActionsHolder">
<% if HasVariations %>
	<% if VariationForm %>$VariationForm<% end_if %>
<% else %>
	<% if canPurchase %>
		<% if Price != 0 %>
		<p class="priceDisplay">
			<% if HasDiscount %>
			<del>$Price.Nice</del> $CalculatedPrice.Nice
			<% else %>
			$CalculatedPrice.Nice
			<% end_if %>
			<% if Currency %><span class="currencyQuantifier">$Currency</span><% end_if %>
			<% if Quantifier %><span class="mainQuantifier">$Quantifier</span><% end_if %>
		</p>
		<% end_if %>
		<% include ProductActionsInner %>
	<% else %>
	<p class="notForSale message">Not for sale.</p>
	<% end_if %>
<% end_if %>
</div>

