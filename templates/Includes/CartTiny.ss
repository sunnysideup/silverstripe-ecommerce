<!-- used to represent a the cart in tiny format -->
<% if Cart %><% with Cart %>
<div class="$AJAXDefinitions.TinyCartClassName">
	<% include CartTinyInner %>
</div>
<% end_with %><% end_if %>

