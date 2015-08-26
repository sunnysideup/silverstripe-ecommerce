<table id="AddressesTable" class="infotable">
	<tr>
		<th scope="col"><% _t("Order.CUSTOMER","Customer") %></th>
		<% if CanHaveShippingAddress %><th scope="col"><% _t("Order.DELIVERTO","Deliver To") %></th><% end_if %>
	</tr>
	<tr>
		<td><% include Order_AddressBilling %></td>
		<% if CanHaveShippingAddress %><td><% include Order_AddressShipping %></td><% end_if %>
	</tr>
</table>
