<div class="ProductGroupSearchFormHolder">
	<% if SearchResultLink %><h3><a href="$SearchResultLink">Last Search Results</a></h3>
	<h3><% _t("Product.Search_Form_Header", "New Search") %></h3>
	$ProductSearchForm
	<% include SearchResultsChildGroups %>
</div>
