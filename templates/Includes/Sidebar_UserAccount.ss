<div class="sidebarBox userAccount">
	<h3><% _t("SideBar.YOURACCOUNT","Your Account") %></h3>
	<p>
<% if EcomConfig.Customer %>
	<% _t("SideBar.LOGGEDINAS","Your are logged in as") %> <a href="$EcomConfig.AccountPageLink">$EcomConfig.Customer.Title</a>.
	<% _t("SideBar.YOUCAN","You can") %>
	<a href="Security/logout/"><% _t("SideBar.LOGOUT","log-out") %></a>
	<% _t("SideBar.ATANYTIMEYOURORDERISAVE","at any time; your order information will be retained for when you next log in.") %>
<% else %>
	<% _t("SideBar.YOUARENOT","Your are not") %> <a href="$EcommerceLogInLink"><% _t("SideBar.LOGGEDIN","logged in") %></a>.
	<% _t("SideBar.YOUCAN","You can") %>
	<a href="{$EcomConfig.CartPageLink}#CreateAccountForm"><% _t("SideBar.CREATEANACCOUNT","create an account") %></a>
	<% _t("SideBar.SAVE_YOUR_ORDER_DETAILS","to save your order details.") %>
<% end_if %>
	</p>
</div>
