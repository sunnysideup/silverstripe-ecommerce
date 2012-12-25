<% if CustomerViewableOrderStatusLogs %>
<table id="StatusLogs" class="infotable">
	<thead>
		<tr class="gap mainHeader">
			<th class="left" colspan="4" scope="col"><% _t("Order.UPDATES","Updates") %></th>
		</tr>
	</thead>
	<tbody>
	<% control CustomerViewableOrderStatusLogs %>
		<tr>
			<th class="left" scope="row">$Title.XML</th>
			<td class="left"><% if CustomerNote %>$CustomerNote<% else %><% _t("Order.NO_FURTHER_INFORMATION", "No further information.") %><% end_if %></td>
		</tr>
	<% end_control %>
	</tbody>
</table>
<% end_if %>

