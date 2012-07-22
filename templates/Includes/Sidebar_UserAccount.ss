<div class="sidebarBox userAccount">
	<h3><% _t("SideBar.YOURACCOUNT","Your Account") %></h3>
	<p>
<% if EcomConfig.Customer %>
	<% _t("SideBar.LOGGEDINAS","Your are logged in as") %> <a href="$EcomConfig.AccountPageLink">$EcomConfig.Customer.Title</a>.
<% else %>
	<% _t("SideBar.YOUARENOT","Your are not") %> <a href="$EcommerceLogInLink"><% _t("SideBar.LOGGEDIN","logged in") %></a>.
<% end_if %>
	</p>
</div>
