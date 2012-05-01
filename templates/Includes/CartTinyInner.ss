<% if Items %>
	<p class="thereAreItems">You have <a href="/shoppingcart/showcart/" class="simpledialog" rel="SimpleDialogueCart">$TotalItems item<% if MoreThanOneItemInCart %>s<% end_if %> in your cart.</a></p>
<% else %>
	<p class="noItems"><% _t("Cart.NOITEMS","There are no items in your cart.") %>.</p>
<% end_if %>
