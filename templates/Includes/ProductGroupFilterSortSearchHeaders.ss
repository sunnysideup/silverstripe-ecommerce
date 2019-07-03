<ul class="filterSortSearchHeaders">
<% if DisplayLinks %>
	<li>
		<a href="#DisplayOptionsForList" class="openCloseSectionLink close"><% _t('ProductGroup.DISPLAY','Display Options') %><% if CurrentDisplayTitle %> ($CurrentDisplayTitle)<% end_if %></a>
	</li>
<% end_if %>
<% if HasFilters %>
	<li>
		<a href="#FilterForList" class="openCloseSectionLink close"><% _t('ProductGroup.SUB_CATEGORIES','Sub-categories') %><% if CurrentFilterTitle %> ($CurrentFilterTitle)<% end_if %></a>
	</li>
<% end_if %>
<% if MenuChildGroups %>
	<li>
		<a href="#MenuChildGroupsList" class="openCloseSectionLink close"><% _t("Product.INTHISSECTION", "In This Section") %></a>
	</li>
<% end_if %>
	<li>
		<% if SearchResultLink %><a href="$SearchResultLink"><% _t("Product.LAST_SEARCH_RESULTS", "Last Search Results") %></a> | <% end_if %>
		<a href="#ProductSearchFormOuter" class="openCloseSectionLink close"><% _t("Product.Search_Form_Header", "New Search") %></a>
	</li>
</ul>
