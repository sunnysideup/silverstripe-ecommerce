<% if EcomConfig.Currencies %>
<div class="sidebarBox currency">
	<h3><% _t("SideBar.CURRENCIES","Currencies") %></h3>
	<% with EcomConfig %>
		<% if CurrenciesExplanation %><div class="explanation">$CurrenciesExplanation</div><% end_if %>
		<% include Sidebar_Currency_Inner %>
	<% end_with %>
</div>
<% end_if %>
