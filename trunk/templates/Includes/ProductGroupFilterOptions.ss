<% if HasFilters %>
<div id="FilterForList" class="close openCloseSection $AjaxDefinitions.ProductListAjaxifiedLinkClassName">
	<ul class="filterOptions">
		<% if FilterLinks %><% loop FilterLinks %><li class="$FirstLast standardFilters"><a href="$Link" class="$LinkingMode">$Name<% if First %><% else %> ($Count)<% end_if %></a></li><% end_loop %><% end_if %>
		<% if ProductGroupFilterLinks %><% loop ProductGroupFilterLinks %><li class="$FirstLast alsoShowFilters"><a href="$FilterLink" class="$MyLinkingMode">$Title ($Count)</a></li><% end_loop %><% end_if %>
	</ul>
</div>
<% end_if %>


