<% if Currencies %>
<div class="ecommerceWidget currencyWidget">
	<% loop Currencies %>
		<% include Sidebar_Currency_Inner %>
	<% end_loop %>
</div>
<% end_if %>
