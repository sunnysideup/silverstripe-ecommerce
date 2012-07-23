	<ul class="ecommerceCurrencies">
	<% control Currencies %>
		<li class="$FirstLast $LinkingMode"><% if IsCurrent %>&raquo; <% else %><a href="$Link"><% end_if %>$Name <% if IsDefault %> (<% _t("SideBar.DEFAULTCURRENCY","Default Currency") %>)<% end_if %><% if IsCurrent %></a><% end_if %></li>
	<% end_control %>
	</ul>
