<h1 class="pageTitle">$Title</h1>

<% if Content %><div id="ContentHolder">$Content</div><% end_if %>
<% include ProductGroupChildGroups %>
<% if Products %>
<div id="Products" class="category">
	<div class="resultsBar">
		<small>
			<% if TotalCountGreaterThanOne %><span class="totalCout">$TotalCount <% _t('ProductGroup.PRODUCTSFOUND','products found.') %></span><% end_if %>
			<% if SortLinks %><span class="sortOptions"><% _t('ProductGroup.SORTBY','Sort by') %> <% loop SortLinks %><a href="$Link" class="sortlink $Current">$Name</a> <% end_loop %></span><% end_if %>
		</small>
	</div>
	<ul class="productList displayStyle$MyDefaultDisplayStyle">
	<% if MyDefaultDisplayStyle = Short %><% loop Products %><% include ProductGroupItemShort %><% end_loop %>
	<% else %><% if MyDefaultDisplayStyle = MoreDetail %><% loop Products %><% include ProductGroupItemMoreDetail %><% end_loop %>
	<% else %><% loop Products %><% include ProductGroupItem %><% end_loop %>
	<% end_if %><% end_if %>
	</ul>
</div>
<% include ProductGroupPagination %>
<% else %>
<p class="noProductsFound"><% _t("Product.NOPRODUCTSFOUND", "No products are listed here.") %></p>
<% end_if %>
<% if Form %><div id="FormHolder">$Form</div><% end_if %>
<% if PageComments %><div id="PageCommentsHolder">$PageComments</div><% end_if %>

