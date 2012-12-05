<% if CustomerOrderNote %>
<table id="NotesTable" class="infotable">
	<thead>
		<tr class="gap mainHeader">
			<th class="left" scope="col"><% _t("Order.CUSTOMER_ORDER_NOTE","Customer Note") %></th>
		</tr>
	</thead>
	<tbody>
		<tr class="summary odd first">
			<td class="left">$CustomerOrderNote.XML</td>
		</tr>
	</tbody>
</table>
<% end_if %>



