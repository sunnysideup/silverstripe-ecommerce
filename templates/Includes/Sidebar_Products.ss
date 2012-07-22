<% if SidebarProducts %>
<div class="sidebarBox products">
	<h3><% _t("SideBar.ALSOSEE","Also see ...") %></h3>
	<ul>
		<% control SidebarProducts %><li><a href="$Link" class="$LinkingMode">$MenuTitle</a></li><% end_control %>
	</ul>
</div>
<% end_if %>
