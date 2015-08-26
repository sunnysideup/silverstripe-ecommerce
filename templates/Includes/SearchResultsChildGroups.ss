
<% if SearchResultsChildGroups %>
	<div class="searchResultsChildGroups">
		<h3><% _t("Product.Search_Results_Child_Groups", "Matching Categories") %></h3>
		<ul class="searchResultsChildGroupList filterOptions">
	<% loop SearchResultsChildGroups %>
			<li class="standardFilters">
				<a href="$Link">$Title</a>
			</li>
	<% end_loop %>
		</ul>
	</div>
<% end_if %>
