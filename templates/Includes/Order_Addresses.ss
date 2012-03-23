<table id="AddressesTable" class="infotable">
	<tr>
		<th scope="col" width="50%"><% _t("Order.PURCHASER","Purchaser") %></th>
		<% if CanHaveShippingAddress %><th scope="col"><% _t("Order.DELIVERTO","Deliver To") %></th><% end_if %>
	</tr>
	<tr>
		<td width="50%"><% include Order_AddressBilling %></td>
		<% if CanHaveShippingAddress %><td><% include Order_AddressShipping %></td><% end_if %>
	</tr>
</table>
