<div class="sidebarBox products">
	<!-- to be redone -->
	<% if SidebarProducts %>
	<ul>
		<% control SidebarProducts %><li><a href="$Link" class="$LinkingMode">$MenuTitle</a></li><% end_control %>
	</ul>
	<% end_if %>
	<div class="clear"></div>
</div>
