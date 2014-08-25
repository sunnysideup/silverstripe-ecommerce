<div class="ProductGroupSearchFormHolder">
	<h3>
	<% if HasSearchResults %><% if SearchResultLink %>
		<a href="$SearchResultLink"><% _t("Product.LAST_SEARCH_RESULTS", "Last Search Results") %></a> |
	<% end_if %><% end_if %>
		<a href="#ProductSearchFormOuter" class="openCloseMySectionLink"><% _t("Product.Search_Form_Header", "New Search") %></a>
	</h3>
	<div id="ProductSearchFormOuter">$ProductSearchForm</div>
	<% include SearchResultsChildGroups %>
</div>
