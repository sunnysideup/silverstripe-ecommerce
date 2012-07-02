<div class="sidebarBox userAccount">
	<h3><% _t("Cart.YOURACCOUNT","Your Account") %></h3>
	<p>
<% if EcomConfig.Customer %>
	<% _t("Cart.LOGGEDINAS","Your are logged in as") %> <a href="$EcomConfig.AccountPageLink">$EcomConfig.Customer.Title</a>.
<% else %>
	<% _t("Cart.YOUARENOT","Your are not") %>You are not <a href="$EcommerceLogInLink"><% _t("Cart.LOGGEDIN","logged in") %></a>.
<% end_if %>
	</p>
</div>
