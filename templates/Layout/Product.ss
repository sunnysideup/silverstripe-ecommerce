<div id="Product" class="mainSection content-container withSidebar <% if IsOlderVersion %>olderVersion<% end_if %>">
<% include LayoutProductInner %>
</div>

<aside>
	<div id="Sidebar">
		<div class="sidebarTop"></div>
		<% include Sidebar_PreviousAndNextProduct %>
		<% include Sidebar_Cart %>
		<% include Sidebar_Currency %>
		<% include Sidebar_UserAccount %>
		<div class="sidebarBottom"></div>
	</div>
</aside>
<% include Sidebar %>


