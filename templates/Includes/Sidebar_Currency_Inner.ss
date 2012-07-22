	<ul class="ecommerceCurrencies">
	<% control Currencies %>
		<li class="$FirstLast $LinkingMode"><a href="$Link"><% if IsCurrent %>&raquo; <% end_if %>$Name <% if IsDefault %> (<% _t("SideBar.DEFAULTCURRENCY","Default Currency") %>)<% end_if %></a></li>
	<% end_control %>
	</ul>
