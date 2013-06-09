<!--- short representation of the cart -->
<% if Cart %><% with Cart %>
<div id="$AJAXDefinitions.SmallCartID">
	<% include CartShortInner %>
</div>
<% end_with %><% end_if %>

