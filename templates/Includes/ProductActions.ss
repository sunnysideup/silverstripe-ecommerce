<div class="productActionsHolder">
<% if HasVariations %>
	<% if VariationForm %>$VariationForm<% end_if %>
<% else %>
	<% if canPurchase %>
		<% include ProductGroupItemPrice %>
		<% if Quantifier %><span class="mainQuantifier">$Quantifier</span><% end_if %>
		<% include ProductActionsInner %>
	<% else %>
	<p class="notForSale message">Not for sale.</p>
	<% end_if %>
<% end_if %>
</div>

