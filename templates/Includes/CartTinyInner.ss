<% if Items %>
	<p class="thereAreItems">You have $TotalItems in your cart.</p>
<% else %>
	<p class="noItems"><% _t("Cart.NOITEMS","There are no items in your cart.") %>.</p>
<% end_if %>
