<% if Cart %><% with Cart %>
<div id="ShoppingCart">
	<h3 id="CartHeader"><% _t("CART","Cart") %></h3>
	<% include Sidebar_Cart_Inner %>
</div>
<% end_with %><% end_if %>



<% include ShoppingCartRequirements %>
