<% if ShowFiltersAndDisplayLinks %>
<div class="filterAndDisplayLinksHolder">

<% if HasFilters %>
	<div class="filterForSection">
		<h3><% _t('ProductGroup.FILTERFOR','Filter for') %></h3>
		<ul class="filterOptions filterSortOptions">
			<% if FilterLinks %><% loop FilterLinks %><li class="$FirstLast standardFilters"><a href="$Link" class="$LinkingMode">$Name</a></li><% end_loop %><% end_if %>
			<% if ProductGroupsFromAlsoShowProductsLinks %><% loop ProductGroupsFromAlsoShowProductsLinks %><li class="$FirstLast alsoShowFilters"><a href="$FilterLink" class="$MyLinkingMode">$Title</a></li><% end_loop %><% end_if %>
		</ul>
	</div>
<% end_if %>

<% if DisplayLinks %>
	<div class="displayStyleSection">
		<h3><% _t('ProductGroup.DISPLAYSTYLE','Views') %></h3>
		<ul class="displayOptions displayStyleOptions">
			<% loop DisplayLinks %><li class="$FirstLast displayStyles"><a href="$Link" class="$LinkingMode">$Name</a></li><% end_loop %>
		</ul>
	</div>
<% end_if %>

</div>
<% end_if %>
