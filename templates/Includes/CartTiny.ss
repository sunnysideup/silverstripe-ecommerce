<!-- used to represent a the cart in tiny format -->
<% if Cart %><% control Cart %>
<div class="$AJAXDefinitions.TinyCartClassName">
	<% include CartTinyInner %>
</div>
<% end_control %><% end_if %>

