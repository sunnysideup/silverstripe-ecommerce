<h1 class="pageTitle">$Title</h1>

<% if Content %><div id="ContentHolder">$Content</div><% end_if %>
<% include ProductGroupChildGroups %>

<div class="filterAndDisplayLinksHolder">
<% if HasFilters %>
	<h3><% _t('ProductGroup.FILTERFOR','Filter for') %></h3>
	<ul class="filterOptions filterSortOptions">
		<% if FilterLinks %><% loop FilterLinks %><li class="$FirstLast standardFilters"><a href="$Link" class="$LinkingMode">$Name</a></li><% end_loop %><% end_if %>
		<% if ProductGroupsFromAlsoShowProductsLinks %><% loop ProductGroupsFromAlsoShowProductsLinks %><li class="$FirstLast alsoShowFilters"><a href="$FilterLink" class="$MyLinkingMode">$Title</a></li><% end_loop %><% end_if %>
	</ul>
<% end_if %>
<h3><% _t('ProductGroup.DISPLAYSTYLE','Views') %></h3>
	<ul class="displayOptions displayStyleOptions">
<% if DisplayLinks %>
		<% loop DisplayLinks %><li class="$FirstLast displayStyles"><a href="$Link" class="$LinkingMode">$Name</a></li><% end_loop %>
<% end_if %>
		<li class="last listAllLink"><a href="$ListAllLink"><% _t('ProductGroup.LIST_ALL','List All') %></a></li>
	</ul>
</div>


<% if Products %>
<div id="Products" class="category">
	<div class="resultsBar">
		<small>
			<% if TotalCountGreaterThanOne %><span class="totalCout">$TotalCount <% _t('ProductGroup.PRODUCTSFOUND','products found.') %></span><% end_if %>
			<% if SortLinks %><span class="sortOptions filterSortOptions"><% _t('ProductGroup.SORTBY','Sort by') %> <% loop SortLinks %><a href="$Link" class="sortlink $LinkingMode">$Name</a> <% end_loop %></span><% end_if %>
		</small>
	</div>
	<ul class="productList displayStyle$MyDefaultDisplayStyle">
	<% if IsShowFullList %>
		<% loop Products %><li><a href="$Link">$Title</a></li><% end_loop %>
	<% else_if MyDefaultDisplayStyle = Short %><% loop Products %><% include ProductGroupItemShort %><% end_loop %>
	<% else_if MyDefaultDisplayStyle = MoreDetail %><% loop Products %><% include ProductGroupItemMoreDetail %><% end_loop %>
	<% else %><% loop Products %><% include ProductGroupItem %><% end_loop %>
	<% end_if %>
<% end_if %>
	</ul>
</div>
<% include ProductGroupPagination %>
<% else %>
<p class="noProductsFound"><% _t("Product.NOPRODUCTSFOUND", "No products are listed here.") %></p>
<% end_if %>
<% if Form %><div id="FormHolder">$Form</div><% end_if %>
<% if PageComments %><div id="PageCommentsHolder">$PageComments</div><% end_if %>

