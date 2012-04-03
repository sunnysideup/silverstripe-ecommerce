<!-- used to represent a the cart in tiny format -->
<% if Cart %><% control Cart %>
<div class="$AJAXDefinitions.TinyCartClass">
	<% include CartTinyInner %>
</div>
<% end_control %><% end_if %>

