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
