<% if EcomConfig.Currencies %>
<div class="sidebarBox currency">
	<h3><% _t("SideBar.CURRENCIES","Currencies") %></h3>
	<% control EcomConfig %>
		<% if CurrenciesExplanation %><div class="explanation">$CurrenciesExplanation</div><% end_if %>
		<% include Sidebar_Currency_Inner %>
	<% end_control %>
</div>
<% end_if %>
