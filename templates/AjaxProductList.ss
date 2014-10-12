<% if ProductGroupListAreCacheable %>
	<% cached ProductGroupListCachingKey %>
		<% include LayoutProductGroupInner %>
	<% end_cached %>
	<span data-current-security-id="$AjaxDefinitions.SecurityID"></span>
<% else %>
	<% include LayoutProductGroupInner %>
<% end_if %>
