<div class="sidebarBox userAccount">
	<h3><% _t("SideBar.YOUR_ACCOUNT","Your Account") %></h3>
	<p>
<% if EcomConfig.Customer %>
	<% _t("SideBar.LOGGED_IN_AS","Your are logged in as") %> <% if EcomConfig.AccountPageLink %><a href="$EcomConfig.AccountPageLink"><% end_if %>$EcomConfig.Customer.Title<% if EcomConfig.AccountPageLink %></a><% end_if %>.
	<% _t("SideBar.YOU_CAN","You can") %>
	<a href="Security/logout/"><% _t("SideBar.LOG_OUT","log-out") %></a>
	<% _t("SideBar.AT_ANY_TIME_YOUR_ORDER_IS_SAVE","at any time; your order information will be retained for when you next log in.") %>
<% else %>
	<% _t("SideBar.YOU_ARE_NOT","You are not") %> <a href="$EcommerceLogInLink"><% _t("SideBar.LOGGED_IN","logged in") %></a>.
	<% if EcomConfig.AccountPageLink %>
	<% _t("SideBar.YOU_CAN","You can") %>
	<a href="{$EcomConfig.AccountPageLink}"><% _t("SideBar.CREATE_AN_ACCOUNT","create an account") %></a>
	<% _t("SideBar.SAVE_YOUR_ORDER_DETAILS","to save your order details.") %>
	<% end_if %>
<% end_if %>
	</p>
</div>

