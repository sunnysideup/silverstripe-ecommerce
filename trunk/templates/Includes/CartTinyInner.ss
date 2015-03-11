<!--
NOTE:
Any element with the following classname: $AJAXDefinitions.TinyCartClassName
will be set to the contents of this file when the cart is updated using AJAX
If you are not using this snippet then theme it and remove its content to speed up your AJAX cart.
-->
<div class="cartTinyInner">
<% if Items %>
	<p class="thereAreItems">
		<% _t("Order.YOUHAVE", "You have") %>
		<!-- note: the hardcoded URL below points to an ajax pop-up alternatively you can use: EcomConfig.CheckoutLink (add dollar sign) -->
		<a href="/shoppingcart/showcart/" class="simpledialog" rel="SimpleDialogueCart">
			 $TotalItems <% if MoreThanOneItemInCart %><% _t("Order.ITEMS", "Items") %><% else %><% _t("Order.ITEM", "Item") %><% end_if %>
		</a>
		<% _t("Order.INYOURCART", "in your cart.") %>
	</p>
<% else %>
	<p class="noItems"><% _t("Order.NOITEMS","There are <strong>no</strong> items in your cart.") %></p>
<% end_if %>
</div>
