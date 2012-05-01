<div class="productActionsHolder">
<% if HasVariations %>
	<% include ProductActionsInner %>
<% else %>
	<% if canPurchase %>
		<% include ProductGroupItemPrice %>
		<% include ProductActionsInner %>
	<% end_if %>
<% end_if %>
</div>

