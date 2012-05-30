<div class="cartTinyInner">
<% if Items %>
	<p class="thereAreItems">
		<% _t("order.YOUHAVE", "You have") %>
		<a href="/shoppingcart/showcart/" class="simpledialog" rel="SimpleDialogueCart">
			 $TotalItems <% if MoreThanOneItemInCart %><% _t("Order.ITEMS", "Items") %><% else %><% _t("Order.ITEM", "Item") %><% end_if %>
		</a>
		<% _t("order.INYOURCART", "in your cart.") %>
	</p>
<% else %>
	<p class="noItems"><% _t("Order.NOITEMS","There are <strong>no</strong> items in your cart.") %></p>
<% end_if %>
</div>
