<div class="sidebarBox cart">
<% if Cart %><% with Cart %>
		<div id="ShoppingCart">
			<h3 id="CartHeader"><% _t("CART","Cart") %></h3>
			<p>There are several types of (side) carts... We show one here... (CartShortInner, CartTinyInner, Sidebar_Cart_Inner (shown)</p>
			<div id="$AJAXDefinitions.SideBarCartID"><% include Sidebar_Cart_Inner %></div>
		</div>
<% end_with %><% end_if %>
</div>
<% include ShoppingCartRequirements %>


