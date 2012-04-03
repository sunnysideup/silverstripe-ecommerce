<!--- short representation of the cart -->
<% if Cart %><% control Cart %>
<div id="$AJAXDefinitions.SmallCartID">
	<% include CartShortInner %>
</div>
<% end_control %><% end_if %>

