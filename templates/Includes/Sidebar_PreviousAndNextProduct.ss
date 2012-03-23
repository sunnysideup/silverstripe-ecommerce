<% if HasPreviousOrNextProduct %>
<div class="sidebarBox previousNext">
	<!-- to be redone -->
	<ul>
	<% if PreviousProduct %>
		<li class="previous"><span>Previous:</span> <a href="$PreviousProduct.Link">$PreviousProduct.MenuTitle</a></li>
	<% end_if %>
	<% if NextProduct %>
		<li class="next"><span>Next:</span> <a href="$NextProduct.Link">$NextProduct.MenuTitle</a></li>
	<% end_if %>
	</ul>
	<div class="clear"></div>
</div>
<% end_if %>
