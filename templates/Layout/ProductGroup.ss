<div id="$AjaxDefinitions.ProductListHolderID"  class="mainSection content-container withSidebar">
<% if ProductGroupListAreCacheable %>
	<% cached ProductGroupListCachingKey %>
		<% include LayoutProductGroupInner %>
	<% end_cached %>
	<span data-current-security-id="$AjaxDefinitions.SecurityID"></span>
<% else %>
	<% include LayoutProductGroupInner %>
<% end_if %>


</div>

<aside>
	<div id="Sidebar">
		<div class="sidebarTop"></div>
		<% include Sidebar_Cart %>
		<% include Sidebar_Currency %>
		<% include Sidebar_UserAccount %>
		<% include Sidebar %>
		<div class="sidebarBottom"></div>
	</div>
</aside>



