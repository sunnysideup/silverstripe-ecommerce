<% if ShowFiltersAndDisplayLinks %>
<div class="filterAndDisplayLinksHolder">

<% if HasFilters %>
	<div class="filterForSection">
		<h3>
			<a href="#FilterForList" class="openCloseMySectionLink"><% _t('ProductGroup.FILTERFOR','Filter for') %><% if CurrentFilterTitle %> ($CurrentFilterTitle)<% end_if %></a>
		</h3>
		<ul id="FilterForList" class="filterOptions filterSortOptions">
			<% if FilterLinks %><% loop FilterLinks %><li class="$FirstLast standardFilters"><a href="$Link" class="$LinkingMode">$Name<% if First %><% else %> ($Count)<% end_if %></a></li><% end_loop %><% end_if %>
			<% if ProductGroupFilterLinks %><% loop ProductGroupFilterLinks %><li class="$FirstLast alsoShowFilters"><a href="$FilterLink" class="$MyLinkingMode">$Title ($Count)</a></li><% end_loop %><% end_if %>
		</ul>
	</div>
<% end_if %>

<% if DisplayLinks %>
	<div class="displayStyleSection">
		<h3>
			<a href="#DisplayOptionsForList" class="openCloseMySectionLink"><% _t('ProductGroup.DISPLAYSTYLE','Views') %></a>
		</h3>
		<ul class="displayOptions displayStyleOptions" id="DisplayOptionsForList">
			<% loop DisplayLinks %><li class="$FirstLast displayStyles"><a href="$Link" class="$LinkingMode">$Name</a></li><% end_loop %>
		</ul>
	</div>
<% end_if %>

</div>
<% end_if %>
