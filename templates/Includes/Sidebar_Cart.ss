<div class="sidebarBox cart">
<% if Cart %><% control Cart %>
		<div id="ShoppingCart">
			<h3 id="CartHeader"><% _t("CART","Cart") %></h3>
			<div  id="$AJAXDefinitions.SideBarCartID"><% include Sidebar_Cart_Inner %></div>
		</div>
<% end_control %><% end_if %>
</div>
<% include ShoppingCartRequirements %>


